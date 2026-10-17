<?php

namespace App\Services;

use App\Models\SocialAccount;
use App\Models\User;
use DomainException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\AbstractUser;
use Laravel\Socialite\Contracts\User as ProviderUser;

class SocialAccountService
{
    public function __construct(protected ImageProcessor $imageProcessor) {}

    public function findOrCreateUser(ProviderUser $providerUser, string $provider, ?User $existingUser = null): User
    {
        $user = $this->resolveUser($providerUser, $provider, $existingUser);

        $this->importAvatarIfMissing($user, $providerUser);

        return $user;
    }

    /**
     * Copy the provider's profile photo (resized by us) when the user has none yet.
     * Never blocks sign-in: any failure leaves the user on the initials avatar.
     */
    protected function importAvatarIfMissing(User $user, ProviderUser $providerUser): void
    {
        $url = $providerUser->getAvatar();

        if ($user->profile_image_url || ! $url) {
            return;
        }

        $url = preg_replace('/=s\d+-c$/', '=s400-c', $url);

        try {
            $response = Http::timeout(5)->get($url);

            if (! $response->successful() || ! str_starts_with((string) $response->header('Content-Type'), 'image/')) {
                return;
            }

            $path = $this->imageProcessor->store($response->body(), 'profile-images', 'avatar', 's3');
            $user->forceFill(['profile_image_url' => $path])->save();
        } catch (\Throwable $exception) {
            Log::warning('Social avatar import failed', ['message' => $exception->getMessage()]);
        }
    }

    protected function resolveUser(ProviderUser $providerUser, string $provider, ?User $existingUser = null): User
    {
        if ($existingUser) {
            $this->linkAccount($existingUser, $providerUser, $provider);

            return $existingUser;
        }

        $socialAccount = SocialAccount::where('provider', $provider)
            ->where('provider_id', $providerUser->getId())
            ->first();

        if ($socialAccount) {
            $this->syncTokens($socialAccount, $providerUser);

            return User::findOrFail($socialAccount->user_id);
        }

        $user = $this->findMatchingUser($providerUser);

        if ($user && ! $this->hasVerifiedEmail($providerUser, $provider)) {
            throw new DomainException('An account with this email already exists.');
        }

        if (! $user) {
            $user = $this->createUserFromProvider($providerUser);
        }

        $this->linkAccount($user, $providerUser, $provider);

        return $user;
    }

    protected function findMatchingUser(ProviderUser $providerUser): ?User
    {
        $email = $providerUser->getEmail();

        if (! $email) {
            return null;
        }

        return User::where('email', $email)->first();
    }

    /**
     * Only Google reports whether the email is verified; other providers are never trusted
     * to take over an existing account by email alone.
     */
    protected function hasVerifiedEmail(ProviderUser $providerUser, string $provider): bool
    {
        if ($provider !== 'google' || ! $providerUser instanceof AbstractUser) {
            return false;
        }

        $raw = $providerUser->getRaw();

        return filter_var($raw['email_verified'] ?? $raw['verified_email'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    protected function createUserFromProvider(ProviderUser $providerUser): User
    {
        $email = $providerUser->getEmail() ?? sprintf('%s@%s', Str::random(12), 'funlynk.social');

        return User::create([
            'email' => $email,
            'username' => $this->generateUniqueUsername($providerUser),
            'display_name' => $providerUser->getName() ?? ($providerUser->getNickname() ?: 'New User'),
            'password' => Hash::make(Str::random(32)),
            'bio' => null,
            'profile_image_url' => null, // Imported afterwards by importAvatarIfMissing()
            'location_name' => null,
            'location_coordinates' => null,
            'interests' => [],
            'is_host' => false,
            'stripe_account_id' => null,
            'stripe_onboarding_complete' => false,
            'follower_count' => 0,
            'following_count' => 0,
            'activity_count' => 0,
            'is_verified' => true,
            'is_active' => true,
            'privacy_level' => 'public',
            'email_verified_at' => now(),
        ]);
    }

    protected function linkAccount(User $user, ProviderUser $providerUser, string $provider): SocialAccount
    {
        $socialAccount = $user->socialAccounts()->updateOrCreate(
            [
                'provider' => $provider,
                'provider_id' => $providerUser->getId(),
            ],
            $this->socialAccountPayload($providerUser)
        );

        $this->syncTokens($socialAccount, $providerUser);

        return $socialAccount;
    }

    protected function socialAccountPayload(ProviderUser $providerUser): array
    {
        return [
            'provider_email' => $providerUser->getEmail(),
            'name' => $providerUser->getName(),
            'nickname' => $providerUser->getNickname(),
            'avatar_url' => $providerUser->getAvatar(),
            'meta' => $providerUser->getRaw(),
        ];
    }

    protected function syncTokens(SocialAccount $account, ProviderUser $providerUser): void
    {
        $account->forceFill([
            'token' => $this->encryptValue($providerUser->token),
            'refresh_token' => $this->encryptValue($providerUser->refreshToken ?? null),
            'token_expires_at' => $this->resolveExpiry($providerUser->expiresIn ?? null),
        ])->save();
    }

    protected function encryptValue(?string $value): ?string
    {
        return $value ? Crypt::encryptString($value) : null;
    }

    protected function resolveExpiry(?int $expiresIn): ?Carbon
    {
        return $expiresIn ? now()->addSeconds($expiresIn) : null;
    }

    protected function generateUniqueUsername(ProviderUser $providerUser): string
    {
        $base = Str::slug(
            $providerUser->getNickname() ?: ($providerUser->getName() ?: Str::random(8))
        );
        $candidate = $base ?: 'user';
        $suffix = 1;

        while (User::where('username', $candidate)->exists()) {
            $candidate = "{$base}-{$suffix}";
            $suffix++;
        }

        return $candidate;
    }
}
