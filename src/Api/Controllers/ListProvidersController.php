<?php

/*
 * This file is part of fof/oauth.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\OAuth\Api\Controllers;

use Flarum\Api\Controller\AbstractListController;
use Flarum\Http\RequestUtil;
use Flarum\User\LoginProvider;
use Flarum\User\User;
use Flarum\User\UserRepository;
use FoF\OAuth\Api\Serializers\ProviderSerializer;
use FoF\OAuth\LoginProviderStatus;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ListProvidersController extends AbstractListController
{
    /**
     * {@inheritdoc}
     */
    public $serializer = ProviderSerializer::class;

    /**
     * @var UserRepository
     */
    protected $users;

    public function __construct(UserRepository $users)
    {
        $this->users = $users;
    }

    /**
     * {@inheritdoc}
     */
    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertRegistered();

        // If no id is provided, we're looking at the current user.
        $user = $this->users->findOrFail(Arr::get($request->getQueryParams(), 'id', $actor->id));

        if ($actor->id !== $user->id) {
            $actor->assertCan('moderateUserProviders');
        }

        $providers = $this->getProviders();

        $loginProviders = $this->getUserProviders($user, $providers);

        $data = new Collection();

        $providers->each(function (array $provider) use ($loginProviders, &$data, $user) {
            $loginProvider = $loginProviders->where('provider', Arr::get($provider, 'name'))->first();
            $data->add(LoginProviderStatus::build(
                Arr::get($provider, 'name'),
                Arr::get($provider, 'icon'),
                Arr::get($provider, 'priority'),
                $user,
                $loginProvider
            ));
        });

        $this->getOrphanedUserProviders($user, $providers)->each(function (LoginProvider $loginProvider) use (&$data) {
            $data->add(LoginProviderStatus::build(
                $loginProvider->provider,
                'fas fa-question',
                -100,
                $loginProvider->user,
                $loginProvider,
                true
            ));
        });

        return $data;
    }

    private function getUserProviders(User $user, Collection $providers): Collection
    {
        return LoginProvider::query()
            ->whereIn('provider', $this->getProviderKeys($providers))
            ->where('user_id', $user->id)
            ->get();
    }

    private function getOrphanedUserProviders(User $user, Collection $providers): Collection
    {
        return LoginProvider::query()
            ->where('user_id', $user->id)
            ->whereNotIn('provider', $this->getProviderKeys($providers))
            ->get();
    }

    private function getProviders(): Collection
    {
        /** @var Collection $providers */
        $providers = collect(resolve('fof-oauth.providers.forum'))->reject(function ($provider) {
            return $provider === null;
        });

        return $providers;
    }

    private function getProviderKeys(Collection $providers): array
    {
        return $providers->map(function ($provider) {
            return Arr::get($provider, 'name');
        })->toArray();
    }
}
