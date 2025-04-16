# OAuth by FriendsOfFlarum

![License](https://img.shields.io/badge/license-MIT-blue.svg) [![Latest Stable Version](https://img.shields.io/packagist/v/fof/oauth.svg)](https://packagist.org/packages/fof/oauth) [![Total Downloads](https://img.shields.io/packagist/dt/fof/oauth.svg)](https://packagist.org/packages/fof/oauthh)  [![OpenCollective](https://img.shields.io/badge/opencollective-fof-blue.svg)](https://opencollective.com/fof/donate)


A [Flarum](http://flarum.org) extension. Allow users to log in with various OAuth providers

### Bundled providers

By default these providers are included:

- Discord
- Facebook
- Github
- Gitlab
- Google
- LinkedIn
- Twitter

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

Additional OAuth providers are available for this extension. Here's a handy list of known extensions, let us know if you know of any more and we'll get them added!

- [Amazon](https://extiverse.com/extension/ianm/oauth-amazon)
- [Apple](https://extiverse.com/extension/blomstra/oauth-apple)
- [Slack](https://extiverse.com/extension/blomstra/oauth-slack)
- [Line](https://extiverse.com/extension/ianm/oauth-line)
- [Microsoft](https://flarum.org/extension/xrh0905/oauth-microsoft)
- [Twitch](https://github.com/imorland/flarum-ext-oauth-twitch)
- [Auth0](https://extiverse.com/extension/lodge104/flarum-ext-oauth-auth0)

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

You can replace the text for the forum sign in buttons in two ways.
- Use `fof-oauth.forum.providers.<name>` to replace the name of the provider on the forum side
- Use `fof-oauth.forum.log_in.with_<name>_button` to replace the entire button "Log In with <name>" text

### Extending

It is possible to add additional `Providers` using an extender. See [OAuth-Amazon](https://github.com/imorland/flarum-ext-oauth-amazon) for an example of how to accomplish this but basically:

- In your new extension, require `fof/oauth` as a dependency
- Define a new `Provider` which extends `FoF\OAuth\Provider`
- From your new extensions `extend.php`, register the provider `(new FoF\OAuth\Extend\RegisterProvider(MyNewProvider::class))`
- Provide the required translations under the `fof-oauth` namespace. See the linked example extension for details on which keys are required.
- (optionally) Provide an admin panel link to `fof/oauth` for easy configuration. Again, see the linked example.
- (optionally) Provide any CSS required to style your new login button. See the linked example.

### Links

[![OpenCollective](https://img.shields.io/badge/donate-friendsofflarum-44AEE5?style=for-the-badge&logo=open-collective)](https://opencollective.com/fof/donate)

- [Discuss](https://discuss.flarum.org/d/25182)
- [Packagist](https://packagist.org/packages/fof/oauth)
- [GitHub](https://github.com/FriendsOfFlarum/oauth)

An extension by [FriendsOfFlarum](https://github.com/FriendsOfFlarum).
