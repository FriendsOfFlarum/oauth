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
use pviojo\OAuth2\Client\Provider\Keycloak as KeycloakProvider;


class Keycloak extends Provider
{
    public function name(): string
    {
        return 'keycloak';
    }

    public function link(): string
    {
        return sprintf(
            '%s/auth/admin/%s/console/',
            $this->getSetting(('auth_server_url')),
            $this->getSetting(('realm'))
        );
    }

    public function fields(): array
    {
        return [
            'auth_server_url'      => 'required',
            'realm'                => 'required',
            'client_id'            => 'required',
            'client_secret'        => 'required',
            'encryption_algorithm' => '',
            'encryption_key_path'  => '',
            'encryption_key'       => '',
        ];
    }

    public function icon(): string
    {
        return 'fab fa-user-lock';
    }

    public function provider(string $redirectUri): AbstractProvider
    {
        return new KeycloakProvider([
            'authServerUrl'       => rtrim($this->getSetting('auth_server_url'), '/'),
            'clientId'            => $this->getSetting('client_id'),
            'clientSecret'        => $this->getSetting('client_secret'),
            'encryptionAlgorithm' => $this->getSetting('encryption_algorithm'),
            'encryptionKey'       => $this->getSetting('encryption_key'),
            'encryptionKeyPath'   => $this->getSetting('encryption_key_path'),
            'realm'               => $this->getSetting('realm'),
            'redirectUri'         => $redirectUri,
        ]);
    }

    public function options(): array
    {
        return ['scope' => ['openid']];
    }

    public function suggestions(Registration $registration, $user, string $token)
    {
        $this->verifyEmail($email = $user->getEmail());

        $registration
            ->provideTrustedEmail($email)
            ->suggestUsername($user->getName() ?: '')
            ->setPayload($user->toArray());
    }
}
