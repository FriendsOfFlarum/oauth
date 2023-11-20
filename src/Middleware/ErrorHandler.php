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
use FoF\OAuth\Errors\AuthenticationException;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ErrorHandler implements MiddlewareInterface
{
    /**
     * @var ViewFactory
     */
    protected $view;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    protected $debugMode;

    protected $reporters;

    public function __construct(ViewFactory $view, TranslatorInterface $translator, Config $config, Container $container)
    {
        $this->view = $view;
        $this->translator = $translator;
        $this->debugMode = Arr::get($config, 'debug', true);
        $this->reporters = $container->tagged(Reporter::class);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->debugMode) {
            return $handler->handle($request);
        }

        try {
            return $handler->handle($request);
        } catch (AuthenticationException $exception) {
            $view = $this->view->make('flarum.forum::error.default')
                ->with('message', $this->getMessage($exception));

            $this->report($exception);

            return new HtmlResponse($view->render(), 401);
        }
    }

    protected function getMessage(AuthenticationException $exception)
    {
        $code = $exception->getShortCode();
        $key = "fof-oauth.forum.error.$code";
        $translation = $this->translator->trans($key);

        return $key === $translation
            ? $exception->getMessage()
            : $translation;
    }

    protected function report(AuthenticationException $e)
    {
        if ($e->shouldBeReported()) {
            foreach ($this->reporters as $reporter) {
                $reporter->report($e);
            }
        }
    }
}
