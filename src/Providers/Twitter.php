<?php

/*
 * This file is part of fof/oauth.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\OAuth\Providers;

use Flarum\Forum\Auth\Registration;
use FoF\OAuth\Errors\AuthenticationException;
use FoF\OAuth\Provider;
use League\OAuth2\Client\Provider\AbstractProvider;

class Twitter extends Provider
{
    public function name(): string
    {
        return 'twitter';
    }

    public function link(): string
    {
        return 'https://developer.twitter.com/en/apps';
    }

    public function fields(): array
    {
        return [
            'api_key'    => 'required',
            'api_secret' => 'required',
        ];
    }

    public function excludeFromRoutePattern(): bool
    {
        return true;
    }

    public function provider(string $redirectUri): ?AbstractProvider
    {
        return null;
    }

    public function server(string $redirectUri): \League\OAuth1\Client\Server\Twitter
    {
        return new \League\OAuth1\Client\Server\Twitter([
            'identifier'   => $this->getSetting('api_key'),
            'secret'       => $this->getSetting('api_secret'),
            'callback_uri' => $redirectUri,
        ]);
    }

    /**
     * @throws AuthenticationException
     */
    public function suggestions(Registration $registration, $user, string $token)
    {
        $email = $user->email;

        if (empty($email)) {
            throw new AuthenticationException('invalid_email');
        }

        if ($this->getProviderVerifiedEmail($user, $token) !== null) {
            $registration->provideTrustedEmail($email);
        } else {
            $registration->suggestEmail($email);
        }

        $registration
            ->suggestUsername($user->nickname ?: '')
            ->setPayload(get_object_vars($user));

        $this->provideAvatar($registration, str_replace('_normal', '', $user->imageUrl));
    }

    /**
     * Twitter / X exposes no email verification flag, so any email it returns is trusted without a verifiable signal.
     *
     * X's (legacy v1.1) `account/verify_credentials` documentation stated that with `include_email`, the email
     * is null "if the user does not have an email address on their account, or if the email address is not
     * verified". That page has since moved to docs.x.com, where this wording could not be confirmed.
     *
     * Twitter logins do not dispatch `OAuthLoginSuccessful`, so this is not used by the email sync on 1.x.
     *
     * @param \League\OAuth1\Client\Server\User $user
     */
    public function getProviderVerifiedEmail($user, string $token): ?string
    {
        return $user->email ?: null;
    }
}
