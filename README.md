# OAuth by FriendsOfFlarum

![License](https://img.shields.io/badge/license-MIT-blue.svg) [![Latest Stable Version](https://img.shields.io/packagist/v/fof/oauth.svg)](https://packagist.org/packages/fof/oauth) [![Total Downloads](https://img.shields.io/packagist/dt/fof/oauth.svg)](https://packagist.org/packages/fof/oauth)  [![OpenCollective](https://img.shields.io/badge/opencollective-fof-blue.svg)](https://opencollective.com/fof/donate)

A [Flarum](http://flarum.org) extension. Allow users to log in and register using OAuth 2.0 providers. Supports account linking, automatic email-match linking, and a security-settings page where users can manage their connected providers.

### Bundled providers

By default these providers are included:

- Discord
- Facebook
- GitHub
- GitLab
- Google
- LinkedIn

### Features

- **Login & registration** via any enabled OAuth 2.0 provider
- **Account linking** — authenticated users can connect a provider from their security settings page
- **Email-match auto-link** — if a provider returns a *verified* email address that matches an existing account, the provider is linked automatically and the user is logged in (see [Email trust](#email-trust))
- **Account linked confirmation** — a modal is shown the first time a provider is linked, confirming the connection
- **PKCE support** — providers that support Proof Key for Code Exchange can opt in per-provider
- **Group assignment** — automatically assign users to a forum group when they register via a specific provider
- **Admin moderation** — view and manage users' linked provider accounts from the admin panel

### Permissions

This extension provides the ability to view the status of linked OAuth providers (intended for admin and/or moderator use). In order for this to function correctly, you must also set the permission `Moderate Access Tokens` to at least the same group as you require for `Moderate user's linked accounts`.

### Group Assignment

You can configure each OAuth provider to automatically assign users to a specific group when they register. This is useful for tracking which provider users signed up with or for granting specific permissions based on the authentication method.

To configure group assignment:
1. Go to the extension settings
2. Enable the desired OAuth provider
3. Click the settings icon for that provider
4. Select a group from the "Assign Group" dropdown
5. Save your changes

Users who register through that provider will automatically be assigned to the selected group.

### Additional providers

Additional OAuth providers are available for this extension. Here's a handy list of known extensions:

- [Amazon](https://discuss.flarum.org/d/29191-login-with-amazon)
- [Apple](https://discuss.flarum.org/d/31938-blomstra-sign-in-with-apple)
- [Auth0](https://extiverse.com/extension/lodge104/flarum-ext-oauth-auth0)
- [Line](https://discuss.flarum.org/d/31860-sign-in-with-line)
- [Microsoft](https://flarum.org/extension/xrh0905/oauth-microsoft)
- [Slack](https://discuss.flarum.org/d/31039-blomstra-sign-in-with-slack)
- [Twitch](https://github.com/imorland/flarum-ext-oauth-twitch)

If you know of others, please open a PR to add them to this list.

### Screenshots

Default provider settings example
![provider setup example](https://user-images.githubusercontent.com/16573496/201470744-ca8be058-f79c-4fc4-8c19-3ac5af2bd44b.png)

Login/signup example with `Github`, `Twitter`, `Twitch` and `Google` enabled.
![example login](https://user-images.githubusercontent.com/16573496/201470704-91874f67-284a-4fb2-967c-fd9d0eff2d9f.png)

### Installation

```sh
composer require fof/oauth
```

### Updating

```sh
composer update fof/oauth
php flarum cache:clear
```

### Configuration

#### Translation

You can replace the text for the forum sign in buttons in two ways:
- Use `fof-oauth.forum.providers.<name>` to replace the name of the provider on the forum side
- Use `fof-oauth.forum.log_in.with_<name>_button` to replace the entire button "Log In with \<name\>" text

#### Updating email addresses from the provider

With **Update email address from provider** enabled, logging in with an OAuth provider whose email differs from the forum account's starts an email change, but only when the provider confirms the address is verified:

- A confirmation link is sent to the new address, using Flarum's normal email change flow. The account's email only changes once the link is followed.
- A notice is sent to the current address, in the user's preferred language, saying a change was requested and through which provider. If the user didn't do this, their provider account may be compromised.
- While a change to the same address is waiting for confirmation (up to 24 hours), further logins do not send these emails again.

Unverified addresses never start a change, and an address already used by another account is skipped without affecting the login.

| Provider | Starts a change when |
|---|---|
| Discord | the email is marked verified |
| Facebook | always (Facebook does not return unconfirmed addresses) |
| GitHub | the account has a primary, verified email (the public profile email is not used) |
| GitLab | the account's email is confirmed |
| Google | the email is marked verified (see below) |
| LinkedIn | the email is marked verified |

**Google and personal accounts:** Google marks an address verified once the user has proved they control that mailbox, including a personal Google account registered with a non-Gmail address such as `you@yourdomain.com`. Google's own guidance only treats Gmail and Google Workspace addresses as authoritative, because for other domains the mailbox may since have changed hands. This extension also trusts Google's `email_verified` flag for those addresses, since control of the mailbox is already what Flarum relies on for password resets. Without it, such users could never link Google to their existing forum account.

Providers from other extensions only sync once they support this (see [Email trust](#email-trust)). Until then their emails are not synced, and a warning naming the provider is written to the forum log once a day.

#### Signing in when the provider's email isn't confirmed

If a provider reports an email address it hasn't confirmed, and an existing forum account already uses that address, the user is not offered the sign-up form (which could only fail with "email already taken"). Instead they're returned to the forum and the Log In form opens with that provider's button hidden, explaining that the provider hasn't confirmed the address and that, if they already have an account, they can log in and link the provider from their account settings. The email address is not put in the URL, so the form isn't pre-filled. An unconfirmed address can never sign in to or link an existing account automatically.

### Extending

It is possible to add additional providers using an extender. See [UPGRADE.md](UPGRADE.md) for a full guide, including a complete example provider class and notes on breaking changes between versions.

In short:

- In your new extension, require `fof/oauth` as a dependency
- Define a new `Provider` class which extends `FoF\OAuth\Provider`
- From your new extension's `extend.php`, register the provider: `(new FoF\OAuth\Extend\RegisterProvider(MyNewProvider::class))`
- Provide the required translations under the `fof-oauth` namespace (see [UPGRADE.md](UPGRADE.md) for required keys)
- Implement `getProviderVerifiedEmail()` so the provider's email can be trusted. See [Email trust](#email-trust).

> **Upgrading an existing provider extension to Flarum 2.x?** See [UPGRADE.md](UPGRADE.md) for the full list of breaking changes.

### Email trust

A provider's email address is only as trustworthy as the provider's own verification of it. Many providers let a user put an address on their account without proving they own it. Your provider decides whether to trust an email in two places.

**1. `suggestions()`, for registration and login.** Call `provideTrustedEmail()` only for an address the provider confirms the user owns. Flarum treats a trusted email as proof of ownership: if it matches an existing account, that account is logged in and linked to the provider identity. Anything else must go through `suggestEmail()`, which only pre-fills the sign-up form.

**2. `getProviderVerifiedEmail()`, for the email sync.** When `Update email address from provider` is enabled, an OAuth login starts a confirmed email change, but only to the address this method returns. Return the email if the provider confirms it is verified, otherwise `null`.

The default implementation returns `null`. A provider that does not implement it never has its email synced, and the forum log records a warning (at most once a day per provider) pointing here. Login and registration are unaffected.

Use one check for both, so they can never disagree:

```php
use Flarum\Forum\Auth\Registration;
use FoF\OAuth\Provider;

class MyProvider extends Provider
{
    // ...

    public function suggestions(Registration $registration, mixed $user, string $token): void
    {
        $this->verifyEmail($email = $user->getEmail());

        if ($this->getProviderVerifiedEmail($user, $token) !== null) {
            $registration->provideTrustedEmail($email);
        } else {
            $registration->suggestEmail($email);
        }

        $registration
            ->suggestUsername($user->getNickname() ?: '')
            ->setPayload($user->toArray());
    }

    public function getProviderVerifiedEmail(mixed $user, string $token): ?string
    {
        // Compare against `true` exactly, so a string such as "false" is not treated as verified.
        return ($user->toArray()['verified'] ?? false) === true ? $user->getEmail() : null;
    }
}
```

What to check depends on the provider:

- **OpenID Connect providers:** the standard `email_verified` claim, e.g. `($user->toArray()['email_verified'] ?? false) === true`. Some providers publish stricter guidance (Google, for instance, only considers itself authoritative for Gmail and Workspace addresses); the bundled Google provider explains in its code why it trusts `email_verified` anyway.
- **Providers with their own flag:** check that flag. The bundled Discord provider uses `verified` and GitLab uses `confirmed_at`.
- **Providers that only return verified addresses** but expose no flag, such as Facebook: return the email, and add a comment stating what the assumption rests on (documentation, or observed behaviour) so it is visible and can be revisited.
- **Providers with no verification signal:** keep the default `null`, and only ever call `suggestEmail()`.

The bundled providers in `src/Providers` are working examples of each case.

### Links

[![OpenCollective](https://img.shields.io/badge/donate-friendsofflarum-44AEE5?style=for-the-badge&logo=open-collective)](https://opencollective.com/fof/donate)

- [Discuss](https://discuss.flarum.org/d/25182)
- [Packagist](https://packagist.org/packages/fof/oauth)
- [GitHub](https://github.com/FriendsOfFlarum/oauth)
- [Flarum 2.0 Upgrade guide for provider extensions](UPGRADE.md)

An extension by [FriendsOfFlarum](https://github.com/FriendsOfFlarum).
