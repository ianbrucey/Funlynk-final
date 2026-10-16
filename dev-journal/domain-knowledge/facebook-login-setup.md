# Facebook Login — Meta app setup checklist

Written 2026-10-16 for the ~2026-10-21 pilot launch.

## Decisions

- **[D] 2026-10-16** Launch social login with **Google + Facebook** only.
- **[D] 2026-10-16** No "Continue with Instagram". Meta shut down the Instagram Basic Display API on
  2024-12-04. The remaining Instagram login (Business Login for Instagram) only serves
  business/creator accounts, grants management permissions, and needs App Review +
  Business Verification for non-testers. Revisit only as a host "connect Instagram" feature.
- **[D] 2026-10-16** An existing account is auto-linked by email only when **Google reports the
  email as verified**. Facebook never auto-links (it exposes no verified flag). Facebook users
  whose email matches an existing account are sent to `/login` with a "sign in with your
  password" error. Code: `SocialAccountService::hasVerifiedEmail()`.

## What the code already does

| Piece | Where |
| --- | --- |
| Routes | `routes/web.php` — `social.redirect`, `social.redirect.event`, `social.callback` (providers: `google`, `facebook`) |
| Controller | `app/Http/Controllers/Auth/SocialLoginController.php` |
| Account logic | `app/Services/SocialAccountService.php` (tokens stored encrypted in `social_accounts`) |
| Buttons | `login.blade.php`, `register.blade.php`, `event-auth-modal.blade.php` |
| Config | `config/services.php` → `FACEBOOK_CLIENT_ID`, `FACEBOOK_CLIENT_SECRET`, `FACEBOOK_REDIRECT_URI` (defaults to `APP_URL/auth/facebook/callback`) |
| Tests | `tests/Feature/SocialLoginTest.php` (mocked Socialite) |
| Legal pages | `/privacy` and `/terms-of-service` exist (`legal.privacy`, `legal.terms`) |

## Checklist

### 1. Create the Meta app
- [ ] Sign in at https://developers.facebook.com and create an app (use case: *Authenticate and
      request data from users with Facebook Login*).
- [ ] Add the **Facebook Login** product (web platform).
- [ ] Permissions: only `public_profile` and `email`. Both are available without App Review.
      Do not request anything else for launch.
- [ ] Copy **App ID** → `FACEBOOK_CLIENT_ID`, **App Secret** → `FACEBOOK_CLIENT_SECRET`
      (Settings → Basic). Set them in Laravel Cloud environment secrets, never in the repo.

### 2. Redirect URIs
- [ ] Facebook Login → Settings → *Valid OAuth Redirect URIs*:
  - `https://<prod-domain>/auth/facebook/callback`
  - `http://127.0.0.1:8000/auth/facebook/callback` and/or `http://localhost:8000/...` for local
    testing (Development mode only).
- [ ] Production `FACEBOOK_REDIRECT_URI` must match the registered URI exactly (scheme, host, path).
      Production forces https, so `APP_URL` must be the https domain.
- [ ] Leave *Use Strict Mode for Redirect URIs* on.

### 3. App settings (Settings → Basic)
- [ ] App icon (1024×1024), display name, contact email.
- [ ] **Privacy Policy URL**: `https://<prod-domain>/privacy`
- [ ] **Terms of Service URL**: `https://<prod-domain>/terms-of-service`
- [ ] **User data deletion**: required before going Live. Choose one:
  - *Data deletion instructions URL* — a page explaining how to delete an account. Quickest.
  - *Data deletion callback URL* — an endpoint Meta POSTs a signed request to. Needs code.
  - **[O] open:** FunLink has no data-deletion page or endpoint yet. Pick one and build it
    before switching the app to Live.
- [ ] App category and Business Use (if Meta asks for it).

### 4. Test in Development mode
- [ ] Add testers (App roles → Roles). In Development mode only people with a role can sign in.
- [ ] Local: set the three env vars in `.env`, run `php artisan config:clear`, click *Facebook* on
      `/login`. Expect to land on the nearby feed.
- [ ] Test a **new** email, a **returning** Facebook user, and an **email that already exists**
      (expect the "sign in with your password" error).
- [ ] Test an account with **no email** (Facebook lets users decline the email permission):
      the service falls back to a placeholder `@funlynk.social` address.
- [ ] Test from an event page (event-aware modal) and confirm you return to the RSVP flow.

### 5. Go Live
- [ ] Complete every red item in the dashboard (usually data deletion + privacy URL).
- [ ] Toggle the app from **Development** to **Live** (top bar).
- [ ] Confirm the production env vars are set and `php artisan config:cache` was run on deploy.
- [ ] Smoke test in production with a Facebook account that is **not** a tester.

## Known limits

- Facebook accounts with no email get a placeholder address and can never be matched by email.
- A user who first signed up with Google and later uses Facebook (same email) will not be
  auto-linked. They must sign in with their password. A future "link accounts from profile
  settings" screen would remove this friction.
- Apple login is not built. It is required only if FunLink ships an iOS app offering other
  social logins.

## References

- Facebook Login: https://developers.facebook.com/docs/facebook-login/
- Instagram Platform overview: https://developers.facebook.com/docs/instagram-platform/overview
- TechCrunch on Basic Display shutdown: https://techcrunch.com/2024/12/06/instagram-locks-out-developers-of-third-party-consumer-apps/
