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
use Flarum\Settings\SettingsRepositoryInterface;
use FoF\OAuth\Provider;
use FoF\OAuth\Providers\Custom\LinkedIn\Provider\LinkedIn as LinkedInProvider;
use League\OAuth2\Client\Provider\AbstractProvider;

class LinkedIn extends Provider
{
    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    public function __construct(SettingsRepositoryInterface $settings)
    {
        $this->settings = $settings;
    }

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

        $registration
        ->provideTrustedEmail($email)
        ->suggestUsername($user->getFirstName())
        ->setPayload($user->toArray());

        $avatar = $user->getImageUrl();
        if ($avatar) {
            $registration->provideAvatar($avatar);
        }
    }
}
