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

use Flarum\Foundation\Config;
use Flarum\Foundation\ErrorHandling\Reporter;
use Flarum\Http\UrlGenerator;
use Flarum\Locale\TranslatorInterface;
use FoF\OAuth\Controllers\AbstractOAuthController;
use FoF\OAuth\Errors\AuthenticationException;
use Illuminate\Contracts\Cache\Store as CacheStore;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ErrorHandler implements MiddlewareInterface
{
    protected bool $debugMode;

    protected iterable $reporters;

    public function __construct(
        protected ViewFactory $view,
        protected TranslatorInterface $translator,
        protected UrlGenerator $url,
        protected CacheStore $cache,
        Config $config,
        Container $container
    ) {
        $this->debugMode = (bool) Arr::get($config, 'debug', true);
        $this->reporters = $container->tagged(Reporter::class);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (AuthenticationException $exception) {
            $this->report($exception);

            $returnTo = $this->resolveReturnTo($request);

            $view = $this->view->make('flarum.forum::error.default')
                ->with('message', $this->getMessage($exception))
                ->with('returnUrl', $returnTo);

            return new HtmlResponse($view->render(), 401);
        }
    }

    /**
     * Attempt to retrieve the returnTo URL from the session cache so we can
     * offer the user a sensible "back to forum" link on the error page.
     */
    protected function resolveReturnTo(ServerRequestInterface $request): string
    {
        /** @var \Illuminate\Session\Store|null $session */
        $session = $request->getAttribute('session');

        if ($session) {
            $cacheKey = AbstractOAuthController::SESSION_RETURN_TO . '_' . $session->getId();
            $returnTo = $this->cache->get($cacheKey);
            if (!empty($returnTo)) {
                return $returnTo;
            }
        }

        return $this->url->to('forum')->base();
    }

    protected function getMessage(AuthenticationException $exception): string
    {
        $code = $exception->getShortCode();
        $key = "fof-oauth.forum.error.$code";
        $translation = $this->translator->trans($key);

        // Fall back to the short code if no translation exists, never expose raw exception messages.
        return $key === $translation ? $code : $translation;
    }

    protected function report(AuthenticationException $e): void
    {
        if ($e->shouldBeReported()) {
            foreach ($this->reporters as $reporter) {
                $reporter->report($e);
            }
        }
    }
}
