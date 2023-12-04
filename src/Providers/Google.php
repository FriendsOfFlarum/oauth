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
use League\OAuth2\Client\Provider\Google as GoogleProvider;

class Google extends Provider
{
    public function name(): string
    {
        return 'google';
    }

    public function link(): string
    {
        return 'https://console.developers.google.com/apis/credentials';
    }

    public function fields(): array
    {
        return [
            'client_id'     => 'required',
            'client_secret' => 'required',
            'hosted_domain' => '',
        ];
    }

    public function provider(string $redirectUri): AbstractProvider
    {
        return new GoogleProvider([
            'clientId'       => $this->getSetting('client_id'),
            'clientSecret'   => $this->getSetting('client_secret'),
            'redirectUri'    => $redirectUri,
            'approvalPrompt' => 'force',
            'hostedDomain'   => $this->getHostedDomain(),
            'accessType'     => 'offline',
        ]);
    }

    /**
     * @return string|null
     */
    protected function getHostedDomain()
    {
        $hostedDomain = $this->getSetting('hosted_domain');

        // Return null if $hostedDomain is an empty string
        return $hostedDomain !== '' ? $hostedDomain : null;
    }

    public function suggestions(Registration $registration, $user, string $token)
    {
        $this->verifyEmail($email = $user->getEmail());

        $registration
            ->provideTrustedEmail($email)
            ->suggestUsername($user->getName())
            ->provideAvatar($user->getAvatar())
            ->setPayload($user->toArray());
    }
}
