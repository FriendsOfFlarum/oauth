<?php

/*
 * This file is part of fof/oauth.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\OAuth\Tests\fixtures\ThirdParty;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * OAuth client that never makes HTTP calls: the token exchange and resource owner lookup are canned.
 */
class ThirdPartyClient extends AbstractProvider
{
    public function __construct(private array $resourceOwner, array $options = [])
    {
        parent::__construct($options);
    }

    public function getAccessToken($grant, array $options = []): AccessTokenInterface
    {
        return new AccessToken(['access_token' => 'token', 'expires' => time() + 3600]);
    }

    public function getResourceOwner(AccessToken $token): ThirdPartyResourceOwner
    {
        return $this->createResourceOwner($this->resourceOwner, $token);
    }

    public function getBaseAuthorizationUrl(): string
    {
        return 'https://thirdparty.example/authorize';
    }

    public function getBaseAccessTokenUrl(array $params): string
    {
        return 'https://thirdparty.example/token';
    }

    public function getResourceOwnerDetailsUrl(AccessToken $token): string
    {
        return 'https://thirdparty.example/me';
    }

    protected function getDefaultScopes(): array
    {
        return [];
    }

    protected function checkResponse(ResponseInterface $response, $data): void
    {
    }

    protected function createResourceOwner(array $response, AccessToken $token): ThirdPartyResourceOwner
    {
        return new ThirdPartyResourceOwner($response);
    }
}
