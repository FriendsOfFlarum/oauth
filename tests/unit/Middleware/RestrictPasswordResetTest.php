<?php

/*
 * This file is part of fof/oauth.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\OAuth\Tests\unit\Middleware;

use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Testing\unit\TestCase;
use Flarum\User\User;
use Flarum\User\UserRepository;
use FoF\OAuth\Middleware\RestrictPasswordReset;
use FoF\OAuth\OAuthOnly;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Collection;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RestrictPasswordResetTest extends TestCase
{
    private bool $handled = false;

    #[Test]
    public function an_address_shared_with_a_member_gets_no_reset_in_oauth_only_mode(): void
    {
        $response = $this->forgot([$this->user(isAdmin: true), $this->user(isAdmin: false)]);

        $this->assertInstanceOf(EmptyResponse::class, $response);
        $this->assertFalse($this->handled);
    }

    #[Test]
    public function an_address_that_only_admins_use_gets_a_reset_in_oauth_only_mode(): void
    {
        $this->forgot([$this->user(isAdmin: true), $this->user(isAdmin: true)]);

        $this->assertTrue($this->handled);
    }

    #[Test]
    public function an_unknown_address_gets_the_same_empty_response_in_oauth_only_mode(): void
    {
        $response = $this->forgot([]);

        $this->assertInstanceOf(EmptyResponse::class, $response);
        $this->assertFalse($this->handled);
    }

    /**
     * @param list<User> $matches
     */
    private function forgot(array $matches): ResponseInterface
    {
        $users = $this->createStub(UserRepository::class);
        $users->method('findAllByEmail')->willReturn(new Collection($matches));

        $settings = $this->createStub(SettingsRepositoryInterface::class);
        $settings->method('get')->willReturnCallback(fn ($key) => $key === 'fof-oauth.oauth_only' ? '1' : null);

        $container = new Container();
        $container->instance('fof-oauth.providers.forum', ['github']);

        $middleware = new RestrictPasswordReset(new OAuthOnly($settings, $container), $users);

        $request = (new ServerRequest())
            ->withAttribute('routeName', 'forgot')
            ->withParsedBody(['email' => 'shared@machine.local']);

        return $middleware->process($request, new class(fn () => $this->handled = true) implements RequestHandlerInterface {
            public function __construct(private \Closure $onHandle)
            {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                ($this->onHandle)();

                return new JsonResponse([]);
            }
        });
    }

    private function user(bool $isAdmin): User
    {
        $user = $this->createStub(User::class);
        $user->method('isAdmin')->willReturn($isAdmin);

        return $user;
    }
}
