<?php

/*
 * This file is part of fof/oauth.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\OAuth;

use Exception;
use Flarum\Foundation\ValidationException;
use FoF\OAuth\Controllers\AbstractOAuthController;
use FoF\OAuth\Errors\AuthenticationException;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

abstract class Controller extends AbstractOAuthController
{
    protected function getRouteName(): string
    {
        return 'fof-oauth';
    }

    protected function getIdentifier(ResourceOwnerInterface $user): string
    {
        return (string) $user->getId();
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            return parent::handle($request);
        } catch (Exception $e) {
            if ((bool) $this->settings->get('fof-oauth.log-oauth-errors')) {
                /** @var LoggerInterface $logger */
                $logger = resolve('log');
                $detail = json_encode([
                    'server_params' => $request->getServerParams(),
                    'request_attrs' => $request->getAttributes(),
                    'cookie_params' => $request->getCookieParams(),
                    'query_params'  => $request->getQueryParams(),
                    'parsed_body'   => $request->getParsedBody(),
                    'code'          => $e->getCode(),
                    'trace'         => $e->getTraceAsString(),
                ], JSON_PRETTY_PRINT);

                $logger->error("[OAuth][{$this->getProviderName()}] {$e->getMessage()}: {$detail}");
            }

            if ($e instanceof IdentityProviderException) {
                throw new AuthenticationException($e->getMessage());
            }

            if ($e instanceof AuthenticationException) {
                throw $e;
            }

            // Re-throw validation exceptions as authentication exceptions to avoid 500 errors
            if ($e instanceof ValidationException) {
                throw new AuthenticationException($e->getMessage(), previous: $e);
            }

            throw $e;
        }
    }
}
