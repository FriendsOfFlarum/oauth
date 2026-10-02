<?php

/*
 * This file is part of fof/oauth.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\OAuth\Middleware;

use Flarum\User\Exception\PermissionDeniedException;
use Flarum\User\PasswordToken;
use Flarum\User\UserRepository;
use FoF\OAuth\OAuthOnly;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Password reset logs the user in without a password check, so OAuth-only mode has to gate it separately:
 * the `forgot` API route, and the forum `resetPassword` / `savePassword` routes for tokens issued earlier.
 */
class RestrictPasswordReset implements MiddlewareInterface
{
    public function __construct(
        protected OAuthOnly $oauthOnly,
        protected UserRepository $users
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $routeName = $request->getAttribute('routeName');

        if (!in_array($routeName, ['forgot', 'resetPassword', 'savePassword'], true) || !$this->oauthOnly->active()) {
            return $handler->handle($request);
        }

        if ($routeName === 'forgot') {
            $email = Arr::get((array) $request->getParsedBody(), 'email');
            $user = is_string($email) ? $this->users->findByEmail($email) : null;

            // Same empty response core gives for an unknown email, so this doesn't reveal which accounts exist.
            if (!$this->oauthOnly->allowsPasswordAuth($user)) {
                return new EmptyResponse();
            }

            return $handler->handle($request);
        }

        // Route parameters only reach the query params inside the controller wrapper, after middleware.
        $token = $routeName === 'resetPassword'
            ? Arr::get((array) $request->getAttribute('routeParameters'), 'token')
            : Arr::get((array) $request->getParsedBody(), 'passwordToken');

        $passwordToken = is_string($token) ? PasswordToken::query()->find($token) : null;

        if ($passwordToken && !$this->oauthOnly->allowsPasswordAuth($passwordToken->user)) {
            throw new PermissionDeniedException();
        }

        return $handler->handle($request);
    }
}
