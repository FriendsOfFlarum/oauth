<?php

/*
 * This file is part of fof/oauth.
 *
 * Copyright (c) 2020 FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace FoF\OAuth\Providers;

use Flarum\Forum\Auth\Registration;
use FoF\OAuth\Provider;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\LinkedIn as LinkedInProvider;

class LinkedIn extends Provider
{
    /**
     * @var LinkedInProvider
     */
    protected $provider;

    public function name(): string
    {
        return 'linkedin';
    }

    public function link(): string
    {
        return 'https://linkedin.com/developers/apps/new';
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
        return $this->provider = new LinkedInProvider([
            'clientId'        => $this->getSetting('client_id'),
            'clientSecret'    => $this->getSetting('client_secret'),
            'redirectUri'     => $redirectUri,
        ]);
    }

    public function suggestions(Registration $registration, $user, string $token)
    {
        $this->verifyEmail($email = $user->getEmail());

        $profileUrl = $user->getImageUrl();
        if ($profileUrl) {
            $registration->provideAvatar($profileUrl);
        }

        $registration
            ->provideTrustedEmail($email)
            ->suggestUsername($user->getFirstName())
            ->setPayload($user->toArray());
    }
}