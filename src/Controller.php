<?php

/*
 * This file is part of fof/oauth.
 *
 * Copyright (c) 2020 FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace FoF\OAuth;

use Exception;
use Flarum\Http\Exception\RouteNotFoundException;
use FoF\Extend\Controllers\AbstractOAuthController;
use FoF\OAuth\Errors\AuthenticationException;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

abstract class Controller extends AbstractOAuthController
{
    protected function getRouteName(): string
    {
        return 'auth.'.$this->getProviderName();
    }

    protected function getIdentifier($user): string
    {
        return $user->getId();
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @throws Exception
     *
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (!(bool) (int) $this->settings->get('fof-oauth.'.$this->getProviderName())) {
            throw new RouteNotFoundException();
        }

        try {
            return parent::handle($request);
        } catch (Exception $e) {
            if ($e->getMessage() === 'Invalid state' || $e instanceof IdentityProviderException) {
                throw new AuthenticationException($e->getMessage());
            }

            throw $e;
        }
    }
}
