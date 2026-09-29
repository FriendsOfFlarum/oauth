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
use FoF\OAuth\Provider;
use League\OAuth2\Client\Provider\AbstractProvider;
use Omines\OAuth2\Client\Provider\Gitlab as GitlabProvider;
use Omines\OAuth2\Client\Provider\GitlabResourceOwner;

class GitLab extends Provider
{
    public function name(): string
    {
        return 'gitlab';
    }

    public function link(): string
    {
        return 'https://gitlab.com/-/profile/applications';
    }

    public function fields(): array
    {
        return [
            'client_id'     => 'required',
            'client_secret' => 'required',
            'domain'        => '',
        ];
    }

    public function provider(string $redirectUri): AbstractProvider
    {
        $options = [
            'clientId'     => $this->getSetting('client_id'),
            'clientSecret' => $this->getSetting('client_secret'),
            'redirectUri'  => $redirectUri,
        ];
        $domain = $this->getSetting('domain');

        if ($domain) {
            $options['domain'] = $domain;
        }

        return new GitlabProvider($options);
    }

    public function options(): array
    {
        return ['scope' => 'read_user'];
    }

    /**
     * @param GitlabResourceOwner $user
     */
    public function suggestions(Registration $registration, $user, string $token)
    {
        $this->verifyEmail($email = $user->getEmail());

        /** @var array<string, mixed> $payload */
        $payload = $user->toArray();

        if ($this->getProviderVerifiedEmail($user, $token) !== null) {
            $registration->provideTrustedEmail($email);
        } else {
            $registration->suggestEmail($email);
        }

        $registration
            ->suggestUsername($user->getUsername() ?: '')
            ->setPayload($payload);

        $this->provideAvatar($registration, $user->getAvatarUrl());
    }

    /**
     * @param GitlabResourceOwner $user
     */
    public function getProviderVerifiedEmail($user, string $token): ?string
    {
        /** @var array<string, mixed> $payload */
        $payload = $user->toArray();

        // `confirmed_at` is set once the account's primary email is confirmed. Self-hosted instances
        // with email confirmation disabled auto-confirm accounts, so this is only as strong as the instance's policy.
        return !empty($payload['confirmed_at']) ? ($user->getEmail() ?: null) : null;
    }
}
