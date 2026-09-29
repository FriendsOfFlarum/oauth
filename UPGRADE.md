# Upgrading Third-Party OAuth Provider Extensions

This guide is for developers who have built extensions that register additional OAuth providers using `fof/oauth`. It covers breaking changes introduced in the 2.x series and what you need to do to stay compatible.

---

## Upgrading to `fof/oauth` 2.x (Flarum 2.0)

The 2.x release is a ground-up rewrite for Flarum 2.0. The `Provider` base class API is mostly unchanged, but several things around the controller layer and response handling have changed.

### 1. `Provider::suggestions()` — no changes

The `Provider` base class still exposes `suggestions(Registration $registration, mixed $user, string $token): void`. You do not need to rename or change anything here.

### 2. `Provider::pkceEnabled()` is now required

**Before (1.x):** PKCE was not in scope. You could ignore it.

**After (2.x):** The abstract method `pkceEnabled(): bool` must be implemented on every `Provider` subclass.

```php
// Most providers that use the authorization_code flow should return false
// unless you have verified your provider supports and requires PKCE.
public function pkceEnabled(): bool
{
    return false;
}
```

Providers that do support PKCE (e.g. Apple, certain OIDC providers) should return `true`.

### 3. Twitter / OAuth 1.0 support dropped

The Twitter provider was removed entirely because Twitter/X moved to OAuth 2.0 with proprietary scopes. If you had custom code relying on a Twitter-specific path, it will no longer work. The `fof/oauth` 2.x series only supports OAuth 2.0 providers.

### 4. `authenticationComplete()` removed from `AbstractOAuthController`

**Before (1.x):** Some third-party controllers overrode `authenticationComplete()` to inject custom response logic.

**After (2.x):** This method no longer exists. Post-authentication logic belongs in a listener on `OAuthLoginSuccessful`, or — if you need to customise the response itself — by overriding `ResponseFactory::makeLoggedInResponse()` or `ResponseFactory::makeRegistrationResponse()` in your own service provider binding.

### 5. `ResponseFactory` — new behaviour and protected extension points

`ResponseFactory::make()` now internally appends `?_flarum_linked={provider}` to the redirect URL when a provider is linked to an existing account for the first time (email-match auto-link). You should not append this parameter yourself.

Two previously private methods are now `protected`, making them safe to override in subclasses:

```php
// Override to customise the logged-in redirect or the remember-me cookie
protected function makeLoggedInResponse(User $user, string $returnTo): ResponseInterface { ... }

// Override to customise the registration redirect
protected function makeRegistrationResponse(string $token, string $returnTo): ResponseInterface { ... }
```

If you previously subclassed or mocked `ResponseFactory`, update your code accordingly.

### 6. `_flarum_linked` query parameter (new in 2.x)

After a first-time account link — whether via the security settings page or via automatic email-match during login — the user is redirected to a URL that contains `?_flarum_linked={provider}` (or `&_flarum_linked={provider}` when `returnTo` already has a query string).

The forum frontend detects this parameter on boot, strips it from the URL bar (via `history.replaceState`), and shows an **AccountLinkedModal** confirming the link.

You do not need to do anything to support this — it is handled by `fof/oauth` automatically. However, if your extension inspects or manipulates redirect URLs after OAuth callbacks, be aware this parameter may now be present.

### 7. `RegisterProvider` extender — no changes

The extender API is unchanged:

```php
// extend.php
return [
    (new FoF\OAuth\Extend\RegisterProvider(MyProvider::class)),
];
```

### 8. Events

The following events are dispatched and available for listeners:

| Event | When |
|---|---|
| `FoF\OAuth\Events\OAuthLoginSuccessful` | After a successful OAuth exchange (login, register, or link). `$actor` is `null` for guest flows. |
| `FoF\OAuth\Events\LinkingToProvider` | Immediately before a provider is linked to an authenticated user's account. |
| `FoF\OAuth\Events\UnlinkingFromProvider` | When a user removes a provider link. |
| `FoF\OAuth\Events\SettingSuggestions` | During the `setSuggestions` step; allows modifying the `Registration` object. |

### 9. Translation keys

Your provider must supply translations under the `fof-oauth` namespace. The required keys are:

```yaml
fof-oauth:
  forum:
    providers:
      myprovider: My Provider      # display name shown in buttons and modals
    log_in:
      with_myprovider_button: Log In with My Provider   # optional override
  admin:
    settings:
      myprovider_client_id_label: Client ID
      myprovider_client_secret_label: Client Secret
```

The `providers.{name}` key is now also used by the `AccountLinkedModal` that appears after a first-time link, so make sure it is present and human-readable.

### 10. Email trust: `getProviderVerifiedEmail()` (new)

`Provider::getProviderVerifiedEmail(mixed $user, string $token): ?string` returns the email only when your provider confirms the user owns it, otherwise `null`. The default is `null`.

- **Email sync:** with `Update email address from provider` enabled, only the address this method returns can start an email change (a confirmation link to the new address and a notice to the current one). If you don't implement it, your provider's emails are never synced, and a warning is logged at most once a day.
- **Registration and login:** in `suggestions()`, call `provideTrustedEmail()` only for a verified address, and `suggestEmail()` otherwise. A trusted email that matches an existing account logs that account in and links it, so trusting an unverified address allows account takeover.
- **Unverified email already in use:** if your provider only suggests an email that an existing account already uses, the user is returned to the forum with `_fof_oauth_unverified=<provider>` and asked to log in and link the provider, instead of being shown a sign-up form that can only fail.

See [Email trust](README.md#email-trust) in the README for the full contract.

---

## Full example provider

A minimal 2.x-compatible provider:

```php
namespace Acme\OAuth\Providers;

use Flarum\Forum\Auth\Registration;
use FoF\OAuth\Provider;
use League\OAuth2\Client\Provider\AbstractProvider;

class MyProvider extends Provider
{
    public function name(): string
    {
        return 'myprovider';
    }

    public function link(): string
    {
        return 'https://myprovider.example.com/oauth2/authorize';
    }

    public function fields(): array
    {
        return [
            'client_id'     => 'myprovider_client_id_label',
            'client_secret' => 'myprovider_client_secret_label',
        ];
    }

    public function provider(string $redirectUri): AbstractProvider
    {
        return new \Acme\OAuth2\MyLeagueProvider([
            'clientId'     => $this->getSetting('client_id'),
            'clientSecret' => $this->getSetting('client_secret'),
            'redirectUri'  => $redirectUri,
        ]);
    }

    public function pkceEnabled(): bool
    {
        return false;
    }

    public function suggestions(Registration $registration, mixed $user, string $token): void
    {
        $this->verifyEmail($email = $user->getEmail());

        // Only trust an email your provider confirms the user owns; otherwise just pre-fill the sign-up form.
        if ($this->getProviderVerifiedEmail($user, $token) !== null) {
            $registration->provideTrustedEmail($email);
        } else {
            $registration->suggestEmail($email);
        }

        $registration->suggestUsername($user->getNickname());
    }

    public function getProviderVerifiedEmail(mixed $user, string $token): ?string
    {
        // Replace with your provider's verification signal, e.g. the OpenID Connect `email_verified` claim.
        return ($user->toArray()['email_verified'] ?? false) === true ? $user->getEmail() : null;
    }
}
```

Register it in `extend.php`:

```php
return [
    (new FoF\OAuth\Extend\RegisterProvider(\Acme\OAuth\Providers\MyProvider::class)),
];
```

---

## Known community provider extensions

If you maintain one of these extensions, the changes above apply to you:

- [Amazon](https://extiverse.com/extension/ianm/oauth-amazon)
- [Apple](https://extiverse.com/extension/blomstra/oauth-apple)
- [Auth0](https://extiverse.com/extension/lodge104/flarum-ext-oauth-auth0)
- [Line](https://extiverse.com/extension/ianm/oauth-line)
- [Microsoft](https://flarum.org/extension/xrh0905/oauth-microsoft)
- [Reddit](https://github.com/imorland/flarum-ext-oauth-reddit)
- [Slack](https://extiverse.com/extension/blomstra/oauth-slack)
- [Twitch](https://github.com/imorland/flarum-ext-oauth-twitch)

Please open an issue or PR on the [fof/oauth repository](https://github.com/FriendsOfFlarum/oauth) if anything in this guide is unclear or missing.
