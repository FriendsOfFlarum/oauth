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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Laminas\Diactoros\Response\EmptyResponse;
use Tobyz\JsonApiServer\Exception\NotFoundException;
use Tobyz\JsonApiServer\Pagination\Pagination;

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
                }),
            Endpoint\Index::make('users.provider.index')
                ->authenticated()
                ->query(function ($query, ?Pagination $pagination, Context $context, array $filters, ?array $sort, int $offset, ?int $limit): Context {
                    $actor = $context->getActor();
                    $userId = (int) Arr::get($filters, 'user', $actor->id);

                    if ($userId !== $actor->id && !$actor->can('moderateUserProviders')) {
                        throw new NotFoundException();
                    }

                    return $context
                        ->withQuery(
                            $query->where('user_id', $userId)
                        )
                        ->withInternal('user_id', $userId);
                })
        ];
    }

    public function results($query, \Tobyz\JsonApiServer\Context $context): iterable
    {
        /** @var Builder $query */
        /** @var Context $context */

        $userId = $context->internal('user_id');
        $providers = $query->get();

        return static::addFakeProviders($providers, $userId);
    }

    public static function addFakeProviders(Collection $providers, int $userId): Collection
    {
        // All available OAuth providers
        $availableProviders = static::getProviders();

        // Add fake providers for those that are not linked
        $availableProviders->each(function ($provider) use ($userId, &$providers) {
            if (!$providers->contains('provider', $provider['name'])) {
                $fakeProvider = new LoginProvider([
                    'provider' => $provider['name'],
                ]);
                $fakeProvider->icon = $provider['icon'] ?? null;
                $fakeProvider->priority = $provider['priority'] ?? 0;
                $fakeProvider->user_id = $userId;

                $providers->push($fakeProvider);
            }
        });

        return $providers;
    }

    public function deleted($model, \Tobyz\JsonApiServer\Context $context): void
    {
        /** @var LoginProvider $model */

        $this->events->dispatch(
            new UnlinkingFromProvider($model->user, $model, $context->getActor())
        );

        parent::deleted($model, $context);
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
