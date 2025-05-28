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
use FoF\OAuth\Api\Resource\ProviderResource;
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

            Schema\Relationship\ToMany::make('linkedAccounts')
                ->includable()
                ->type('linked-accounts')
                ->get(function (User $user, Context $context) {
                    // User's providers
                    $providers = $user->loginProviders;

                    // All available OAuth providers
                    $availableProviders = ProviderResource::getProviders();

                    // Add fake providers for those that are not linked
                    $availableProviders->each(function ($provider) use ($user, &$providers) {
                        if (!$providers->contains('provider', $provider['name'])) {
                            $fakeProvider = new LoginProvider([
                                'provider' => $provider['name'],
                            ]);
                            $fakeProvider->icon = $provider['icon'] ?? null;
                            $fakeProvider->priority = $provider['priority'] ?? 0;
                            $fakeProvider->user_id = $user->id;

                            $providers->push($fakeProvider);
                        }
                    });

                    return $providers->all();
                }),
        ];
    }
}
