<?php

/*
 * This file is part of fof/oauth.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\OAuth\Providers\Custom\LinkedIn\Token;

use InvalidArgumentException;

class LinkedInAccessToken extends \League\OAuth2\Client\Token\AccessToken
{
    /**
     * @var int
     */
    protected $refreshTokenExpires;

    /**
     * Constructs an access token.
     *
     * @param array $options An array of options returned by the service provider
     *                       in the access token request. The `access_token` option is required.
     *
     * @throws InvalidArgumentException if `access_token` is not provided in `$options`.
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);

        if (isset($options['refresh_token_expires_in'])) {
            $expires = $options['refresh_token_expires_in'];
            if (!$this->isExpirationTimestamp($expires)) {
                $expires += time();
            }
            $this->refreshTokenExpires = $expires;
        }
    }

    /**
     * Returns the refresh token expiration timestamp, if defined.
     *
     * @return int|null
     */
    public function getRefreshTokenExpires()
    {
        return $this->refreshTokenExpires;
    }
}
