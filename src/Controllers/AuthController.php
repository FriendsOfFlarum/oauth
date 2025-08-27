<?php

/*
 * This file is part of fof/oauth.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\OAuth\Controllers;

use Flarum\Forum\Auth\Registration;
use Flarum\Http\Exception\RouteNotFoundException;
use FoF\OAuth\Controller;
use FoF\OAuth\Events\SettingSuggestions;
use FoF\OAuth\Provider;
use Illuminate\Support\Arr;
use League\OAuth2\Client\Provider\AbstractProvider;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class AuthController extends Controller
{
    /**
     * @var ?Provider
     */
    protected $provider;

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $name = Arr::get($request->getQueryParams(), 'provider');
        $providers = resolve('container')->tagged('fof-oauth.providers');

        foreach ($providers as $provider) {
            if ($provider->name() === $name) {
                if ($provider->enabled()) {
                    $this->provider = $provider;
                }

                break;
            }
        }

        if (!$this->provider) {
            throw new RouteNotFoundException();
        }

        return parent::handle($request);
    }

    protected function getRouteName(): string
    {
        // Errors are thrown if we return 'fof-oauth' because no options are passed.
        return 'auth.twitter';
    }

    protected function getProvider(string $redirectUri): AbstractProvider
    {
        return $this->provider->provider(
            $this->url->to('forum')->route(
                'fof-oauth',
                ['provider' => $this->getProviderName()]
            )
        );
    }

    protected function getProviderName(): string
    {
        return $this->provider->name();
    }

    protected function getAuthorizationUrlOptions(): array
    {
        return $this->provider->options();
    }

    protected function setSuggestions(Registration $registration, $user, string $token)
    {
        $this->provider->suggestions($registration, $user, $token);

        $this->events->dispatch(
            new SettingSuggestions($this->getProviderName(), $registration, $user, $token)
        );
    }
}
