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

class LinkingToProvider
{
    public function __construct(
        /**
         * The OAuth provider name, as stored in the `login_providers` table `provider` column.
         */
        public readonly string $providerName,

        /**
         * The provider's unique identifier for this user, as stored in the `login_providers` table `identifier` column.
         */
        public readonly string $identifier,

        /**
         * The authenticated user linking their account to this OAuth provider.
         */
        public readonly User $actor
    ) {
    }
}
