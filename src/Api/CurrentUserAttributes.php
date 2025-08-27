<?php

/*
 * This file is part of fof/oauth.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\OAuth\Api;

use Flarum\Api\Serializer\CurrentUserSerializer;
use Flarum\User\LoginProvider;
use Flarum\User\User;
use FoF\Extend\Controllers\AbstractOAuthController;
use Illuminate\Contracts\Cache\Store as Cache;

class CurrentUserAttributes
{
    /**
     * @var Cache
     */
    protected $cache;

    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    public function __invoke(CurrentUserSerializer $serializer, User $user, array $attributes): array
    {
        $session = $serializer->getRequest()->getAttribute('session');

        if ($session !== null) {
            $loginProvider = $this->cache->get(AbstractOAuthController::SESSION_OAUTH2PROVIDER.'_'.$session->getId());

            if ($loginProvider === null) {
                // This solution is not optimal, if someone uses multiple login providers at the same time, this could be lead to wrong results
                $loginProvider = LoginProvider::query()
                    ->where('user_id', $user->id)
                    ->orderBy('last_login_at', 'desc')
                    ->value('provider');

                $loginProvider = $loginProvider ?: false;
                $this->cache->forever(AbstractOAuthController::SESSION_OAUTH2PROVIDER.'_'.$session->getId(), $loginProvider);
            }

            // We don't want return false when the provider is not set, map it back to null
            $attributes['loginProvider'] = $loginProvider ?: null;
        }

        return $attributes;
    }
}
