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
- **Email-match auto-link** — if a provider returns an email address that matches an existing account, the provider is linked automatically and the user is logged in
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

### Extending

It is possible to add additional providers using an extender. See [UPGRADE.md](UPGRADE.md) for a full guide, including a complete example provider class and notes on breaking changes between versions.

In short:

- In your new extension, require `fof/oauth` as a dependency
- Define a new `Provider` class which extends `FoF\OAuth\Provider`
- From your new extension's `extend.php`, register the provider: `(new FoF\OAuth\Extend\RegisterProvider(MyNewProvider::class))`
- Provide the required translations under the `fof-oauth` namespace (see [UPGRADE.md](UPGRADE.md) for required keys)

> **Upgrading an existing provider extension to Flarum 2.x?** See [UPGRADE.md](UPGRADE.md) for the full list of breaking changes.

### Links

[![OpenCollective](https://img.shields.io/badge/donate-friendsofflarum-44AEE5?style=for-the-badge&logo=open-collective)](https://opencollective.com/fof/donate)

- [Discuss](https://discuss.flarum.org/d/25182)
- [Packagist](https://packagist.org/packages/fof/oauth)
- [GitHub](https://github.com/FriendsOfFlarum/oauth)
- [Flarum 2.0 Upgrade guide for provider extensions](UPGRADE.md)

An extension by [FriendsOfFlarum](https://github.com/FriendsOfFlarum).
