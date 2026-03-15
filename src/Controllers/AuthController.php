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
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class AuthController extends Controller
{
    protected ?Provider $oauthProvider = null;

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $name = $request->getQueryParams()['provider'] ?? null;

        foreach ($this->app()->tagged('fof-oauth.providers') as $provider) {
            if ($provider->name() === $name) {
                if ($provider->enabled()) {
                    $this->oauthProvider = $provider;
                }
                break;
            }
        }

        if ($this->oauthProvider === null) {
            throw new RouteNotFoundException();
        }

        return parent::handle($request);
    }

    protected function getRouteName(): string
    {
        return 'fof-oauth';
    }

    protected function getProvider(string $redirectUri): AbstractProvider
    {
        return $this->oauthProvider->provider($redirectUri);
    }

    protected function getProviderName(): string
    {
        return $this->oauthProvider->name();
    }

    protected function getAuthorizationUrlOptions(): array
    {
        return $this->oauthProvider->options();
    }

    protected function isPkceEnabled(): bool
    {
        return $this->oauthProvider->pkceEnabled();
    }

    protected function getIdentifier(ResourceOwnerInterface $user): string
    {
        return (string) $user->getId();
    }

    protected function setSuggestions(Registration $registration, ResourceOwnerInterface $user, string $token): void
    {
        $this->oauthProvider->suggestions($registration, $user, $token);

        $this->events->dispatch(
            new SettingSuggestions($this->getProviderName(), $registration, $user, $token)
        );
    }

    private function app(): \Illuminate\Contracts\Container\Container
    {
        return resolve('container');
    }
}
