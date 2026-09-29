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
use Illuminate\Support\Arr;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Github as GitHubProvider;
use League\OAuth2\Client\Provider\GithubResourceOwner;

class GitHub extends Provider
{
    /**
     * @var GitHubProvider|null
     */
    protected $provider;

    public function name(): string
    {
        return 'github';
    }

    public function link(): string
    {
        return 'https://github.com/settings/developers';
    }

    public function fields(): array
    {
        return [
            'client_id'     => 'required',
            'client_secret' => 'required',
        ];
    }

    public function provider(string $redirectUri): AbstractProvider
    {
        return $this->provider = new GitHubProvider([
            'clientId'     => $this->getSetting('client_id'),
            'clientSecret' => $this->getSetting('client_secret'),
            'redirectUri'  => $redirectUri,
        ]);
    }

    public function options(): array
    {
        return ['scope' => ['user:email']];
    }

    /**
     * @param GithubResourceOwner $user
     */
    public function suggestions(Registration $registration, $user, string $token)
    {
        $verified = $this->getProviderVerifiedEmail($user, $token);

        if ($verified !== null) {
            $registration->provideTrustedEmail($verified);
        } else {
            // The public profile email carries no verification flag, so it is only ever suggested.
            $this->verifyEmail($email = $user->getEmail());

            $registration->suggestEmail($email);
        }

        $registration
            ->suggestUsername($user->getNickname() ?: '')
            ->setPayload($user->toArray());

        $this->provideAvatar($registration, Arr::get($user->toArray(), 'avatar_url'));
    }

    /**
     * The `email` from `GET /user` is the public profile email and has no verification flag, so it is never
     * trusted. Only the primary, verified address from `GET /user/emails` (scope `user:email`) is returned.
     *
     * @param GithubResourceOwner $user
     */
    public function getProviderVerifiedEmail($user, string $token): ?string
    {
        return $this->getEmailFromApi($token);
    }

    private function getEmailFromApi(string $token): ?string
    {
        // Provider instances are resolved fresh from the container, so the client may not be built yet
        // (e.g. when called from the email sync listener).
        /** @var GitHubProvider $client */
        $client = $this->provider ?? $this->provider('');

        $url = $client->apiDomain.'/user/emails';

        $response = $client->getResponse(
            $client->getAuthenticatedRequest('GET', $url, $token)
        );

        $emails = json_decode((string) $response->getBody(), true);

        if (!is_array($emails)) {
            return null;
        }

        foreach ($emails as $email) {
            if (is_array($email) && ($email['primary'] ?? false) === true && ($email['verified'] ?? false) === true && !empty($email['email'])) {
                return $email['email'];
            }
        }

        return null;
    }
}
