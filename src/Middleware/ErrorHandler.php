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

use Flarum\Foundation\ErrorHandling\Reporter;
use FoF\OAuth\Errors\AuthenticationException;
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

    public function __construct(ViewFactory $view, TranslatorInterface $translator)
    {
        $this->view = $view;
        $this->translator = $translator;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (Arr::get(resolve('flarum.config'), 'debug', true)) {
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
        $reporters = resolve('container')->tagged(Reporter::class);

        if ($e->shouldBeReported()) {
            foreach ($reporters as $reporter) {
                $reporter->report($e);
            }
        }
    }
}
