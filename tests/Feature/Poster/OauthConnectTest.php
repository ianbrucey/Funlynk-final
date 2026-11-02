<?php

namespace Tests\Feature\Poster;

use App\Models\User;
use App\Poster\Enums\Platform;
use App\Poster\Models\PlatformAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Phase 2 OAuth connect flow: admin guard + callback token storage.
 * All platform calls are Http::fake'd; the tokens used here are dummy
 * test values, asserted to land in the DB only as ciphertext.
 */
class OauthConnectTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        config(['poster.admin_emails' => ['poster-admin@example.test']]);

        return User::factory()->create(['email' => 'poster-admin@example.test']);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/poster/connect/meta')->assertRedirect('/login');
        $this->get('/poster/connect/tiktok')->assertRedirect('/login');
    }

    public function test_non_admin_user_is_forbidden(): void
    {
        config(['poster.admin_emails' => ['poster-admin@example.test']]);

        $user = User::factory()->create(['email' => 'regular@example.test']);

        $this->actingAs($user)->get('/poster/connect/meta')->assertForbidden();
        $this->actingAs($user)->get('/poster/connect/tiktok')->assertForbidden();
    }

    public function test_admin_can_start_the_meta_flow(): void
    {
        config(['poster.meta.app_secret' => 'test-secret-not-real']);

        $response = $this->actingAs($this->admin())->get('/poster/connect/meta');

        $response->assertRedirect();
        $location = (string) $response->headers->get('Location');
        $this->assertStringContainsString('facebook.com', $location);
        $this->assertStringContainsString('dialog/oauth', $location);
        $this->assertStringContainsString('client_id=1420149999776735', $location);
        $this->assertStringContainsString('pages_manage_posts', $location);
    }

    public function test_admin_can_start_the_tiktok_flow(): void
    {
        config(['poster.tiktok.client_secret' => 'test-secret-not-real']);

        $response = $this->actingAs($this->admin())->get('/poster/connect/tiktok');

        $response->assertRedirect();
        $location = (string) $response->headers->get('Location');
        $this->assertStringContainsString('tiktok.com/v2/auth/authorize', $location);
        $this->assertStringContainsString('client_key=aw1a16e8hrl8w5a7', $location);
        $this->assertStringContainsString('video.upload', $location);
    }

    public function test_meta_callback_stores_page_and_instagram_tokens_encrypted(): void
    {
        config([
            'poster.meta.app_secret' => 'test-secret-not-real',
            'poster.meta.page_id' => '61563258179586',
        ]);

        Http::fake(function (Request $request) {
            $url = $request->url();
            parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

            if (str_contains($url, '/oauth/access_token')) {
                return isset($query['fb_exchange_token'])
                    ? Http::response(['access_token' => 'LONG_USER_TOKEN'], 200)
                    : Http::response(['access_token' => 'SHORT_USER_TOKEN'], 200);
            }

            if (str_contains($url, '/me/accounts')) {
                return Http::response(['data' => [
                    ['id' => '999999', 'name' => 'Some Other Page', 'access_token' => 'OTHER_PAGE_TOKEN'],
                    ['id' => '61563258179586', 'name' => 'Funlynk', 'access_token' => 'PAGE_TOKEN_TEST'],
                ]], 200);
            }

            if (($query['fields'] ?? null) === 'instagram_business_account') {
                return Http::response(['instagram_business_account' => ['id' => 'ig-777']], 200);
            }

            if (($query['fields'] ?? null) === 'username') {
                return Http::response(['username' => 'funlynk.app'], 200);
            }

            return Http::response([], 404);
        });

        $response = $this->actingAs($this->admin())
            ->withSession(['poster_meta_state' => 'state-abc'])
            ->get('/poster/connect/meta/callback?code=AUTH_CODE&state=state-abc');

        $response->assertOk();

        $facebook = PlatformAccount::where('platform', Platform::Facebook)->first();
        $this->assertNotNull($facebook);
        $this->assertSame('61563258179586', $facebook->external_id);
        $this->assertSame('PAGE_TOKEN_TEST', $facebook->access_token);
        $this->assertSame(PlatformAccount::STATUS_CONNECTED, $facebook->status);

        $raw = DB::table('poster_platform_accounts')->where('platform', 'facebook')->value('access_token');
        $this->assertNotSame('PAGE_TOKEN_TEST', $raw);
        $this->assertStringNotContainsString('PAGE_TOKEN_TEST', (string) $raw);

        $instagram = PlatformAccount::where('platform', Platform::Instagram)->first();
        $this->assertNotNull($instagram);
        $this->assertSame('ig-777', $instagram->external_id);
        $this->assertSame('@funlynk.app', $instagram->handle);
        $this->assertSame('PAGE_TOKEN_TEST', $instagram->access_token);
    }

    public function test_meta_callback_rejects_a_mismatched_state(): void
    {
        $this->actingAs($this->admin())
            ->withSession(['poster_meta_state' => 'expected-state'])
            ->get('/poster/connect/meta/callback?code=AUTH_CODE&state=wrong-state')
            ->assertForbidden();

        $this->assertSame(0, PlatformAccount::count());
    }

    public function test_tiktok_callback_stores_tokens_encrypted(): void
    {
        config(['poster.tiktok.client_secret' => 'test-secret-not-real']);

        Http::fake(function (Request $request) {
            $url = $request->url();

            if (str_contains($url, '/oauth/token/')) {
                return Http::response([
                    'access_token' => 'TT_ACCESS_TOKEN',
                    'refresh_token' => 'TT_REFRESH_TOKEN',
                    'open_id' => 'open-1',
                    'expires_in' => 86400,
                    'scope' => 'user.info.basic,video.upload',
                ], 200);
            }

            if (str_contains($url, '/user/info/')) {
                return Http::response([
                    'data' => ['user' => ['username' => 'funlynk', 'display_name' => 'Funlynk']],
                ], 200);
            }

            return Http::response([], 404);
        });

        $response = $this->actingAs($this->admin())
            ->withSession(['poster_tiktok_state' => 'state-xyz'])
            ->get('/poster/connect/tiktok/callback?code=AUTH_CODE&state=state-xyz');

        $response->assertOk();

        $account = PlatformAccount::where('platform', Platform::TikTok)->first();
        $this->assertNotNull($account);
        $this->assertSame('open-1', $account->external_id);
        $this->assertSame('@funlynk', $account->handle);
        $this->assertSame('TT_ACCESS_TOKEN', $account->access_token);
        $this->assertSame('TT_REFRESH_TOKEN', $account->refresh_token);
        $this->assertNotNull($account->token_expires_at);

        $raw = DB::table('poster_platform_accounts')->where('platform', 'tiktok')->first();
        $this->assertStringNotContainsString('TT_ACCESS_TOKEN', (string) $raw->access_token);
        $this->assertStringNotContainsString('TT_REFRESH_TOKEN', (string) $raw->refresh_token);
    }
}
