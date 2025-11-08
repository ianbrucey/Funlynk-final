<?php

namespace App\Services;

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as ProviderUser;

class SocialAccountService
{
    public function findOrCreateUser(ProviderUser $providerUser, string $provider, ?User $existingUser = null): User
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

            return $socialAccount->user;
        }

        $user = $this->findMatchingUser($providerUser);

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

    protected function createUserFromProvider(ProviderUser $providerUser): User
    {
        $email = $providerUser->getEmail() ?? sprintf('%s@%s', Str::random(12), 'funlynk.social');

        return User::create([
            'email' => $email,
            'username' => $this->generateUniqueUsername($providerUser),
            'display_name' => $providerUser->getName() ?? ($providerUser->getNickname() ?: 'New User'),
            'password' => Hash::make(Str::random(32)),
            'bio' => null,
            'profile_image_url' => $providerUser->getAvatar(),
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
