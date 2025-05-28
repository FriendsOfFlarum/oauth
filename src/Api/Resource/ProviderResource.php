<?php

/*
 * This file is part of fof/oauth.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\OAuth\Api\Resource;

use Flarum\Api\Context;
use Flarum\Api\Endpoint;
use Flarum\Api\Resource\AbstractDatabaseResource;
use Flarum\Api\Schema;
use Flarum\User\LoginProvider;
use FoF\OAuth\Events\UnlinkingFromProvider;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Laminas\Diactoros\Response\EmptyResponse;

class ProviderResource extends AbstractDatabaseResource
{
    private Collection $providers;

    public function __construct()
    {
        $this->providers = static::getProviders();
    }

    public static function getProviders(): Collection
    {
        return collect(resolve('fof-oauth.providers.forum'))->reject(function ($provider) {
            return $provider === null;
        });
    }

    public function model(): string
    {
        return LoginProvider::class;
    }

    public function type(): string
    {
        return 'linked-accounts';
    }

    public function endpoints(): array
    {
        return [
            Endpoint\Delete::make('users.provider.delete')
                ->authenticated()
                ->visible(function (LoginProvider $provider, Context $context) {
                    $actor = $context->getActor();
                    $user = $provider->user;

                    return $user !== null && ($user->id === $actor->id || $actor->can('moderateUserProviders'));
                })
                ->after(function (Context $context) {
                    $provider = $context->model;

                    $actor = $context->getActor();
                    $user = $provider->user;

                    // Dispatch the unlinking event
                    resolve('events')->dispatch(
                        new UnlinkingFromProvider($user, $provider, $actor)
                    );

                    return 'N/A';
                })
                ->response(fn () => new EmptyResponse(204)),
        ];
    }

    public function fields(): array
    {
        return [
            Schema\Str::make('name')
                ->get(fn (LoginProvider $loginProvider) => $this->getProviderAttr($loginProvider->provider, 'name') ?? $loginProvider->provider),
            Schema\Str::make('icon')
                ->get($this->useProviderAttribute('icon', 'fas fa-question')),
            Schema\Integer::make('priority')
                ->get($this->useProviderAttribute('priority', -100)),
            Schema\Boolean::make('orphaned')
                ->get(fn (LoginProvider $loginProvider) => $this->providers->firstWhere('name', $loginProvider->provider) === null),
            Schema\Boolean::make('linked')
                ->get(fn (LoginProvider $loginProvider) => $loginProvider->exists),
            Schema\Str::make('identifier')
                ->property('id'),
            Schema\Str::make('providerIdentifier')
                ->property('identifier'),
            Schema\DateTime::make('firstLogin')
                ->property('created_at'),
            Schema\DateTime::make('lastLogin')
                ->property('last_login_at'),

            Schema\Relationship\ToOne::make('user')
                ->includable()
                ->type('users')
                ->inverse('linkedAccounts'),
        ];
    }

    public function getId(object $model, $context): string
    {
        return $model->id ?? "{$model->user_id}-{$model->provider}";
    }

    protected function getProviderAttr(string $name, string $attr)
    {
        $provider = $this->providers->firstWhere('name', $name);

        if ($provider === null) {
            return null;
        }

        return Arr::get($provider, $attr);
    }

    protected function useProviderAttribute(string $attr, $default = null): callable
    {
        return function (LoginProvider $loginProvider, Context $context) use ($default, $attr) {
            return $this->getProviderAttr($loginProvider->provider, $attr) ?? $default;
        };
    }
}
