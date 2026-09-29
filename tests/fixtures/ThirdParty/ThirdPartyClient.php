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
use Psr\Http\Message\ResponseInterface;

/**
 * OAuth client that never makes HTTP calls: the token exchange and resource owner lookup are canned.
 */
class ThirdPartyClient extends AbstractProvider
{
    /**
     * @var array
     */
    private $resourceOwner;

    public function __construct(array $resourceOwner, array $options = [])
    {
        parent::__construct($options);

        $this->resourceOwner = $resourceOwner;
    }

    public function getAccessToken($grant, array $options = [])
    {
        return new AccessToken(['access_token' => 'token', 'expires' => time() + 3600]);
    }

    public function getResourceOwner(AccessToken $token)
    {
        return $this->createResourceOwner($this->resourceOwner, $token);
    }

    public function getBaseAuthorizationUrl()
    {
        return 'https://thirdparty.example/authorize';
    }

    public function getBaseAccessTokenUrl(array $params)
    {
        return 'https://thirdparty.example/token';
    }

    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        return 'https://thirdparty.example/me';
    }

    protected function getDefaultScopes()
    {
        return [];
    }

    protected function checkResponse(ResponseInterface $response, $data)
    {
    }

    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new ThirdPartyResourceOwner($response);
    }
}
