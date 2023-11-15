<?php

/*
 * This file is part of fof/oauth.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\OAuth\Listeners;

use Flarum\Http\RequestUtil;
use Flarum\User\Event\LoggedOut;
use FoF\Extend\Controllers\AbstractOAuthController;
use Illuminate\Contracts\Cache\Store as Cache;
use Illuminate\Session\Store as Session;
use Psr\Http\Message\ServerRequestInterface;

class HandleLogout
{
    /**
     * @var Cache
     */
    protected $cache;

    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    public function handle(LoggedOut $event)
    {
        $user = $event->user;

        /** @var ServerRequestInterface|null $request */
        $request = resolve('fof-oauth-request');

        if ($request) {
            $requestUser = RequestUtil::getActor($request);

            if ($requestUser->id === $user->id) {
                /** @var Session $session */
                $session = $request->getAttribute('session');

                $this->cache->forget(AbstractOAuthController::SESSION_OAUTH2PROVIDER.'_'.$session->getId());
            }
        }
    }
}
