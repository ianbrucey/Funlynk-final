<?php

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

function fakeProviderUser(string $id, ?string $email, array $raw = []): SocialiteUser
{
    $user = (new SocialiteUser)->setRaw($raw)->map([
        'id' => $id,
        'nickname' => 'someone',
        'name' => 'Some One',
        'email' => $email,
        'avatar' => 'https://example.com/a.jpg',
    ]);
    $user->token = 'token-'.$id;

    return $user;
}

function mockSocialite(string $provider, SocialiteUser $user): void
{
    $driver = Mockery::mock(Provider::class);
    $driver->shouldReceive('user')->andReturn($user);
    Socialite::shouldReceive('driver')->with($provider)->andReturn($driver);
}

function fakeJpegBytes(int $width = 1200, int $height = 800): string
{
    $image = imagecreatetruecolor($width, $height);
    ob_start();
    imagejpeg($image);

    return (string) ob_get_clean();
}

beforeEach(function () {
    Storage::fake('s3');
    Http::fake(['example.com/*' => Http::response(fakeJpegBytes(), 200, ['Content-Type' => 'image/jpeg'])]);
});

it('imports the provider photo for a new social user, resized', function () {
    mockSocialite('google', fakeProviderUser('g-img', 'img@example.com'));

    $this->get(route('social.callback', 'google'));

    $user = User::where('email', 'img@example.com')->firstOrFail();
    expect($user->profile_image_url)->not->toBeNull();
    Storage::disk('s3')->assertExists($user->profile_image_url);
    [$width, $height] = getimagesizefromstring(Storage::disk('s3')->get($user->profile_image_url));
    expect($width)->toBe(400)->and($height)->toBe(400);
});

it('still signs in when the provider photo cannot be fetched', function () {
    Http::swap(new Illuminate\Http\Client\Factory);
    Http::fake(['example.com/*' => Http::response('nope', 500)]);
    mockSocialite('google', fakeProviderUser('g-fail', 'fail@example.com'));

    $this->get(route('social.callback', 'google'));

    $user = User::where('email', 'fail@example.com')->firstOrFail();
    expect($user->profile_image_url)->toBeNull();
    $this->assertAuthenticatedAs($user);
});

it('redirects to facebook and google', function (string $provider) {
    $driver = Mockery::mock(Provider::class);
    $driver->shouldReceive('redirect')->andReturn(redirect('https://provider.test/oauth'));
    Socialite::shouldReceive('driver')->with($provider)->andReturn($driver);

    $this->get(route('social.redirect', $provider))->assertRedirect('https://provider.test/oauth');
})->with(['google', 'facebook']);

it('rejects unsupported providers', function () {
    $this->get('/auth/instagram/redirect')->assertNotFound();
});

it('creates and logs in a new user from facebook', function () {
    mockSocialite('facebook', fakeProviderUser('fb-1', 'new@example.com'));

    $this->get(route('social.callback', 'facebook'))->assertRedirect(route('feed.nearby'));

    $user = User::where('email', 'new@example.com')->firstOrFail();
    $this->assertAuthenticatedAs($user);
    expect(SocialAccount::where('provider', 'facebook')->where('provider_id', 'fb-1')->exists())->toBeTrue();
});

it('logs in a returning facebook user without creating a duplicate', function () {
    $user = User::factory()->create();
    SocialAccount::factory()->create(['user_id' => $user->id, 'provider' => 'facebook', 'provider_id' => 'fb-2']);
    mockSocialite('facebook', fakeProviderUser('fb-2', $user->email));

    $this->get(route('social.callback', 'facebook'));

    $this->assertAuthenticatedAs($user);
    expect(User::count())->toBe(1);
});

it('links google to an existing account when google verified the email', function () {
    $user = User::factory()->create(['email' => 'match@example.com']);
    mockSocialite('google', fakeProviderUser('g-1', 'match@example.com', ['email_verified' => true]));

    $this->get(route('social.callback', 'google'));

    $this->assertAuthenticatedAs($user);
    expect($user->socialAccounts()->where('provider', 'google')->exists())->toBeTrue();
});

it('refuses to take over an existing account via an unverified email', function (string $provider, array $raw) {
    $user = User::factory()->create(['email' => 'victim@example.com']);
    mockSocialite($provider, fakeProviderUser('x-1', 'victim@example.com', $raw));

    $this->get(route('social.callback', $provider))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('social');

    $this->assertGuest();
    expect($user->socialAccounts()->count())->toBe(0);
})->with([
    'facebook' => ['facebook', []],
    'google unverified' => ['google', ['email_verified' => false]],
]);
