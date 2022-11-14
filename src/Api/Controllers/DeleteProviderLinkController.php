<?php

namespace FoF\OAuth\Api\Controllers;

use Flarum\Api\Controller\AbstractDeleteController;
use Flarum\Foundation\ValidationException;
use Flarum\Http\RequestUtil;
use Flarum\User\LoginProvider;
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
    
    public function __construct(Dispatcher $events)
    {
        $this->events = $events;
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

        if ($provider->user_id !== $actor->id) {
            throw new ValidationException(['provider' => 'This provider does not belong to you.']);
        }

        $this->events->dispatch(new UnlinkingFromProvider($actor, $provider));

        $provider->delete();

        return new EmptyResponse(204);
    }
}
