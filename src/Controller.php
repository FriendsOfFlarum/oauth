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
use Flarum\Forum\Auth\ResponseFactory;
use Flarum\Http\Exception\RouteNotFoundException;
use Flarum\Http\UrlGenerator;
use Flarum\Settings\SettingsRepositoryInterface;
use FoF\Extend\Controllers\AbstractOAuthController;
use FoF\OAuth\Errors\AuthenticationException;
use Illuminate\Contracts\Cache\Store as CacheStore;
use Illuminate\Contracts\Events\Dispatcher;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

abstract class Controller extends AbstractOAuthController
{
    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    public function __construct(
        ResponseFactory $response,
        SettingsRepositoryInterface $settings,
        UrlGenerator $url,
        Dispatcher $events,
        CacheStore $cache
    ) {
        parent::__construct($response, $settings, $url, $events, $cache);

        $this->settings = $settings;
    }

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

            if ($e->getMessage() === 'Invalid state' || $e instanceof IdentityProviderException) {
                throw new AuthenticationException($e->getMessage());
            }

            throw $e;
        }
    }
}
