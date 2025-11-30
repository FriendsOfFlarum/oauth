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
use Flarum\Locale\TranslatorInterface;
use FoF\OAuth\Errors\AuthenticationException;
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
        Config $config,
        Container $container
    )
    {
        $this->debugMode = (bool) Arr::get($config, 'debug', true);
        $this->reporters = $container->tagged(Reporter::class);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (AuthenticationException $exception) {
            // Handle service and validation exceptions with proper error page.
            $view = $this->view->make('flarum.forum::error.default')
                ->with('message', $this->getMessage($exception));

            // This will log the error if it needs to be reported.
            $this->report($exception);

            return new HtmlResponse($view->render(), 401);
        }
    }

    protected function getMessage(AuthenticationException $exception): string
    {
        $code = $exception->getShortCode();
        $key = "fof-oauth.forum.error.$code";
        $translation = $this->translator->trans($key);

        return $key === $translation
            ? $exception->getMessage()
            : $translation;
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
