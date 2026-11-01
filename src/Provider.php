<?php

/*
 * This file is part of fof/oauth.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\OAuth;

use Flarum\Forum\Auth\Registration;
use Flarum\Settings\SettingsRepositoryInterface;
use FoF\OAuth\Errors\AuthenticationException;
use League\OAuth2\Client\Provider\AbstractProvider;

abstract class Provider
{
    public function __construct(
        protected SettingsRepositoryInterface $settings
    ) {
    }

    // Provider data

    abstract public function name(): string;

    abstract public function link(): string;

    abstract public function fields(): array;

    public function icon(): string
    {
        return "fab fa-{$this->name()}";
    }

    public function priority(): int
    {
        return 0;
    }

    // Controller options

    abstract public function provider(string $redirectUri): ?AbstractProvider;

    public function options(): array
    {
        return [];
    }

    /**
     * Whether to use PKCE (Proof Key for Code Exchange) for this provider.
     * Providers that support PKCE should override this and return true.
     */
    abstract public function pkceEnabled(): bool;

    public function suggestions(Registration $registration, mixed $user, string $token): void
    {
        //
    }

    /**
     * Return the user's email address only if the provider confirms the user owns it, otherwise null.
     *
     * This is the provider's single decision on email trust. It is used by the post-login email sync
     * (`fof-oauth.update_email_from_provider`), and bundled providers also use it in `suggestions()` to
     * choose between `provideTrustedEmail()` and `suggestEmail()`.
     *
     * The default returns null, so providers that do not implement it never have their email synced.
     *
     * @see https://github.com/FriendsOfFlarum/oauth/blob/2.x/README.md#email-trust
     *
     * @param mixed $user The resource owner returned by the provider
     */
    public function getProviderVerifiedEmail(mixed $user, string $token): ?string
    {
        return null;
    }

    // Helpers

    public function enabled(): bool
    {
        return (bool) $this->settings->get("fof-oauth.{$this->name()}");
    }

    protected function getSetting(string $key): string
    {
        return $this->settings->get("fof-oauth.{$this->name()}.{$key}") ?? '';
    }

    /**
     * @throws AuthenticationException
     */
    protected function verifyEmail(?string $email): void
    {
        if (empty($email)) {
            throw new AuthenticationException('invalid_email');
        }
    }

    /**
     * Provide the avatar to the registration if possible.
     * Ignores input URL if avatars are disabled or if `allow_url_fopen` is off.
     *
     * @param Registration $registration
     * @param string|null  $url
     *
     * @return void
     */
    protected function provideAvatar(Registration $registration, ?string $url): void
    {
        if (
            empty($url) ||
            (int) $this->settings->get('fof-oauth.disable_avatars')
        ) {
            return;
        }

        $registration->provideAvatar($url);
    }
}
