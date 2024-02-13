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

use Flarum\Api\Controller\AbstractDeleteController;
use Flarum\Http\RequestUtil;
use Flarum\User\LoginProvider;
use Flarum\User\UserRepository;
use FoF\OAuth\Events\UnlinkingFromProvider;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ServerRequestInterface;

class DeleteProviderLinkController extends AbstractDeleteController
{
    /**
     * @var Dispatcher
     */
    protected $events;

    /**
     * @var UserRepository
     */
    protected $users;

    public function __construct(Dispatcher $events, UserRepository $users)
    {
        $this->events = $events;
        $this->users = $users;
    }

    /**
     * {@inheritdoc}
     */
    protected function delete(ServerRequestInterface $request)
    {
        $actor = RequestUtil::getActor($request);
        $id = Arr::get($request->getQueryParams(), 'id');

        $actor->assertRegistered();

        $provider = LoginProvider::findOrFail($id);

        $user = $this->users->findOrFail($provider->user_id);

        if ($user->id !== $actor->id) {
            $actor->assertCan('moderateUserProviders');
        }

        $this->events->dispatch(new UnlinkingFromProvider($user, $provider, $actor));

        $provider->delete();

        return new EmptyResponse(204);
    }
}
