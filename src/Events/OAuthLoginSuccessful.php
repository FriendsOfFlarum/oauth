<?php

/*
 * This file is part of fof/oauth.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\OAuth\Events;

use Flarum\User\User;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessTokenInterface;

class OAuthLoginSuccessful
{
    public function __construct(
        /**
         * The access token provided by the OAuth service.
         */
        public readonly AccessTokenInterface $token,

        /**
         * The complete ResourceOwner object returned by the provider.
         */
        public readonly ResourceOwnerInterface $userResource,

        /**
         * The OAuth provider name, as stored in the `login_providers` table `provider` column.
         */
        public readonly string $providerName,

        /**
         * The provider's unique identifier for this user, as stored in the `login_providers` table `identifier` column.
         */
        public readonly string $identifier,

        /**
         * The actor at the time of the OAuth callback.
         *
         * For a new login this will be the guest user (not yet authenticated).
         * For an account-linking flow this will be the authenticated user performing the link.
         */
        public readonly ?User $actor = null
    ) {
    }
}
