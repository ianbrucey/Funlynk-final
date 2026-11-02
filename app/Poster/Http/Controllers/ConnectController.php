<?php

namespace App\Poster\Http\Controllers;

use App\Poster\Enums\Platform;
use App\Poster\Models\PlatformAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * OAuth connect flow for the Poster's OWN platform accounts (the Funlynk
 * Facebook Page + linked Instagram, and the Funlynk TikTok account).
 *
 * Routes are admin-only (auth + the `poster-admin` gate — see
 * PosterServiceProvider). Tokens land on poster_platform_accounts through
 * the models' encrypted casts and are never logged or rendered.
 */
class ConnectController extends Controller
{
    public function meta(Request $request): RedirectResponse|View
    {
        if (blank(config('poster.meta.app_id')) || blank(config('poster.meta.app_secret'))) {
            return $this->error('Meta is not configured: POSTER_META_APP_ID / POSTER_META_APP_SECRET are not set.');
        }

        $state = Str::random(40);
        $request->session()->put('poster_meta_state', $state);

        $version = (string) (config('poster.meta.graph_version') ?: 'v21.0');
        $query = http_build_query([
            'client_id' => config('poster.meta.app_id'),
            'redirect_uri' => config('poster.meta.redirect_uri'),
            'scope' => implode(',', (array) config('poster.meta.scopes')),
            'state' => $state,
            'response_type' => 'code',
        ]);

        return redirect()->away("https://www.facebook.com/{$version}/dialog/oauth?{$query}");
    }

    public function metaCallback(Request $request): View
    {
        $this->assertValidState($request, 'poster_meta_state');

        $code = (string) $request->query('code', '');

        if ($code === '') {
            return $this->error('Meta did not return an authorization code'.($this->oauthErrorSuffix($request)).'.');
        }

        $version = (string) (config('poster.meta.graph_version') ?: 'v21.0');
        $base = "https://graph.facebook.com/{$version}";

        // 1. Exchange the code for a short-lived user token.
        $short = Http::get("{$base}/oauth/access_token", [
            'client_id' => config('poster.meta.app_id'),
            'client_secret' => config('poster.meta.app_secret'),
            'redirect_uri' => config('poster.meta.redirect_uri'),
            'code' => $code,
        ]);

        if ($short->failed() || blank($short->json('access_token'))) {
            return $this->error('Meta code exchange failed: '.$this->graphError($short));
        }

        // 2. Exchange it for a long-lived user token (Page tokens derived
        //    from a long-lived user token do not expire).
        $long = Http::get("{$base}/oauth/access_token", [
            'grant_type' => 'fb_exchange_token',
            'client_id' => config('poster.meta.app_id'),
            'client_secret' => config('poster.meta.app_secret'),
            'fb_exchange_token' => $short->json('access_token'),
        ]);

        $userToken = $long->successful() && filled($long->json('access_token'))
            ? (string) $long->json('access_token')
            : (string) $short->json('access_token');

        // 3. Fetch the Pages this user manages, with their Page tokens.
        $pages = Http::get("{$base}/me/accounts", ['access_token' => $userToken]);

        if ($pages->failed()) {
            return $this->error('Could not list your Facebook Pages: '.$this->graphError($pages));
        }

        $pageId = (string) config('poster.meta.page_id');
        $page = collect((array) $pages->json('data', []))
            ->first(fn (mixed $candidate): bool => is_array($candidate) && (string) ($candidate['id'] ?? '') === $pageId);

        if (! is_array($page) || blank($page['access_token'] ?? null)) {
            return $this->error(
                "The Funlynk Page (id {$pageId}) was not among the Pages returned for this Facebook account. ".
                'Connect with the account that manages the Funlynk Page.'
            );
        }

        $pageToken = (string) $page['access_token'];
        $scopes = array_values((array) config('poster.meta.scopes'));
        $connected = [];

        $facebook = PlatformAccount::updateOrCreate(
            ['platform' => Platform::Facebook, 'external_id' => $pageId],
            [
                'label' => 'Funlynk Facebook Page',
                'handle' => is_string($page['name'] ?? null) ? $page['name'] : null,
                'access_token' => $pageToken,
                'refresh_token' => null,
                'token_expires_at' => null,
                'scopes' => $scopes,
                'status' => PlatformAccount::STATUS_CONNECTED,
                'external_meta' => ['connected_via' => 'meta_oauth'],
            ]
        );
        $connected[] = $facebook;

        // 4. The Instagram account linked to the Page, if any. It publishes
        //    with the Page token via graph.facebook.com.
        $igLookup = Http::get("{$base}/{$pageId}", [
            'fields' => 'instagram_business_account',
            'access_token' => $pageToken,
        ]);

        $igId = $igLookup->json('instagram_business_account.id');

        if (is_string($igId) && $igId !== '') {
            $igProfile = Http::get("{$base}/{$igId}", [
                'fields' => 'username',
                'access_token' => $pageToken,
            ]);

            $username = $igProfile->json('username');

            $connected[] = PlatformAccount::updateOrCreate(
                ['platform' => Platform::Instagram, 'external_id' => $igId],
                [
                    'label' => 'Funlynk Instagram',
                    'handle' => is_string($username) && $username !== '' ? "@{$username}" : null,
                    'access_token' => $pageToken,
                    'refresh_token' => null,
                    'token_expires_at' => null,
                    'scopes' => $scopes,
                    'status' => PlatformAccount::STATUS_CONNECTED,
                    'external_meta' => ['connected_via' => 'meta_oauth', 'via_page_id' => $pageId],
                ]
            );
        }

        return view('poster::connected', [
            'accounts' => $connected,
            'note' => $igId === null
                ? 'No Instagram Business/Creator account is linked to the Page, so only Facebook was connected.'
                : null,
        ]);
    }

    public function tiktok(Request $request): RedirectResponse|View
    {
        if (blank(config('poster.tiktok.client_key')) || blank(config('poster.tiktok.client_secret'))) {
            return $this->error('TikTok is not configured: POSTER_TIKTOK_CLIENT_KEY / POSTER_TIKTOK_CLIENT_SECRET are not set.');
        }

        $state = Str::random(40);
        $request->session()->put('poster_tiktok_state', $state);

        $query = http_build_query([
            'client_key' => config('poster.tiktok.client_key'),
            'scope' => implode(',', (array) config('poster.tiktok.scopes')),
            'response_type' => 'code',
            'redirect_uri' => config('poster.tiktok.redirect_uri'),
            'state' => $state,
        ]);

        return redirect()->away("https://www.tiktok.com/v2/auth/authorize/?{$query}");
    }

    public function tiktokCallback(Request $request): View
    {
        $this->assertValidState($request, 'poster_tiktok_state');

        $code = (string) $request->query('code', '');

        if ($code === '') {
            return $this->error('TikTok did not return an authorization code'.($this->oauthErrorSuffix($request)).'.');
        }

        $tokenResponse = Http::asForm()->post('https://open.tiktokapis.com/v2/oauth/token/', [
            'client_key' => config('poster.tiktok.client_key'),
            'client_secret' => config('poster.tiktok.client_secret'),
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => config('poster.tiktok.redirect_uri'),
        ]);

        $accessToken = $tokenResponse->json('access_token') ?? $tokenResponse->json('data.access_token');
        $openId = $tokenResponse->json('open_id') ?? $tokenResponse->json('data.open_id');

        if ($tokenResponse->failed() || ! is_string($accessToken) || $accessToken === '' || ! is_string($openId) || $openId === '') {
            $message = $tokenResponse->json('error_description') ?? $tokenResponse->json('error.message');
            $message = is_string($message) && $message !== '' ? $message : "HTTP {$tokenResponse->status()}";

            return $this->error("TikTok code exchange failed: {$message}");
        }

        $refreshToken = $tokenResponse->json('refresh_token') ?? $tokenResponse->json('data.refresh_token');
        $expiresIn = $tokenResponse->json('expires_in') ?? $tokenResponse->json('data.expires_in');
        $scope = $tokenResponse->json('scope') ?? $tokenResponse->json('data.scope');
        $scopes = is_string($scope) && $scope !== '' ? explode(',', $scope) : array_values((array) config('poster.tiktok.scopes'));

        // Best-effort profile fetch for a friendly label/handle.
        $profile = Http::withToken($accessToken)
            ->get('https://open.tiktokapis.com/v2/user/info/', ['fields' => 'open_id,display_name,username']);
        $username = $profile->json('data.user.username');
        $displayName = $profile->json('data.user.display_name');

        $account = PlatformAccount::updateOrCreate(
            ['platform' => Platform::TikTok, 'external_id' => $openId],
            [
                'label' => 'Funlynk TikTok',
                'handle' => is_string($username) && $username !== '' ? "@{$username}" : (is_string($displayName) ? $displayName : null),
                'access_token' => $accessToken,
                'refresh_token' => is_string($refreshToken) && $refreshToken !== '' ? $refreshToken : null,
                'token_expires_at' => is_numeric($expiresIn) ? now()->addSeconds((int) $expiresIn) : null,
                'scopes' => $scopes,
                'status' => PlatformAccount::STATUS_CONNECTED,
                'external_meta' => ['connected_via' => 'tiktok_oauth'],
            ]
        );

        return view('poster::connected', ['accounts' => [$account], 'note' => null]);
    }

    private function assertValidState(Request $request, string $sessionKey): void
    {
        $expected = $request->session()->pull($sessionKey);
        $given = $request->query('state');

        if (! is_string($expected) || ! is_string($given) || ! hash_equals($expected, $given)) {
            abort(403, 'Invalid OAuth state. Start the connect flow again.');
        }
    }

    private function oauthErrorSuffix(Request $request): string
    {
        $description = $request->query('error_description');
        $error = $request->query('error');
        $detail = is_string($description) && $description !== '' ? $description : (is_string($error) ? $error : null);

        return $detail !== null && $detail !== '' ? " ({$detail})" : '';
    }

    private function graphError(mixed $response): string
    {
        $message = is_object($response) && method_exists($response, 'json')
            ? $response->json('error.message')
            : null;

        return is_string($message) && $message !== '' ? $message : 'unknown error';
    }

    private function error(string $message): View
    {
        return view('poster::error', ['message' => $message]);
    }
}
