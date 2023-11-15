<?php

namespace FoF\OAuth\Middleware;

use Illuminate\Contracts\Container\Container;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ServerRequestMiddleware implements MiddlewareInterface
{
	protected $container;

	public function __construct(Container $container)
	{
		$this->container = $container;
	}

	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		$this->container->instance('fof-oauth-request', $request);

		return $handler->handle($request);
	}
}
