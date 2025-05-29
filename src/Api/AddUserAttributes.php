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

use Flarum\Api\Context;
use Flarum\Api\Schema;
use Flarum\User\LoginProvider;
use Flarum\User\User;
use FoF\Extend\Controllers\AbstractOAuthController;
use Illuminate\Contracts\Cache\Store as Cache;

class AddUserAttributes
{
    /**
     * @var Cache
     */
    protected $cache;

    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    public function __invoke(): array
    {
        return [
            Schema\Str::make('loginProvider')
                ->hidden(fn (User $model, Context $context) => $context->getActor()->isGuest() || $model->id !== $context->getActor()->id || $context->request->getAttribute('session') === null)
                ->get(function (User $model, Context $context) {
                    $session = $context->request->getAttribute('session');
                    $loginProvider = $this->cache->get(AbstractOAuthController::SESSION_OAUTH2PROVIDER.'_'.$session->getId());

                    if ($loginProvider === null) {
                        // This solution is not optimal, if someone uses multiple login providers at the same time, this could be lead to wrong results
                        $loginProvider = LoginProvider::query()
                            ->where('user_id', $context->getActor()->id)
                            ->orderBy('last_login_at', 'desc')
                            ->value('provider');

                        $loginProvider = $loginProvider ?: false;
                        $this->cache->forever(AbstractOAuthController::SESSION_OAUTH2PROVIDER.'_'.$session->getId(), $loginProvider);
                    }

                    return $loginProvider;
                }),

            // The include relationship does NOT include unlinked login providers, unlike the resource's index route.
            Schema\Relationship\ToMany::make('linkedAccounts')
                ->includable()
                ->type('linked-accounts')
                ->visible(fn (User $user, Context $context) => !$context->getActor()->isGuest() && ($user->id === $context->getActor()->id || $context->getActor()->can('moderateUserProviders')))
                ->get(fn (User $user, Context $context) => $user->loginProviders->all()),
        ];
    }
}
