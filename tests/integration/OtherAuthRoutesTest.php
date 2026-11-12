<?php

/*
 * This file is part of fof/oauth.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\OAuth\Tests\integration;

use Flarum\Extend;
use Flarum\Testing\integration\TestCase;
use FoF\OAuth\Extend\RegisterProvider;
use FoF\OAuth\Tests\fixtures\ThirdParty\ThirdPartyProvider;
use Laminas\Diactoros\Response\TextResponse;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Another extension's static /auth/<name> route, registered after fof/oauth's, as an SSO extension's would be.
 */
class OtherAuthRoutesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('fof-oauth');
        $this->extend(
            new RegisterProvider(ThirdPartyProvider::class),
            (new Extend\Routes('forum'))->get('/auth/other', 'other.auth', fn () => new class() implements RequestHandlerInterface {
                public function handle(ServerRequestInterface $request): ResponseInterface
                {
                    return new TextResponse('other');
                }
            })
        );

        $this->setting('fof-oauth.thirdparty', 1);
    }

    #[Test]
    public function another_extensions_auth_route_is_served(): void
    {
        $response = $this->send($this->request('GET', '/auth/other'));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('other', (string) $response->getBody());
    }

    #[Test]
    public function a_registered_provider_still_starts_its_login(): void
    {
        $response = $this->send($this->request('GET', '/auth/thirdparty'));

        $this->assertEquals(302, $response->getStatusCode());
    }

    #[Test]
    public function an_unknown_provider_is_not_found(): void
    {
        $this->assertEquals(404, $this->send($this->request('GET', '/auth/unknown'))->getStatusCode());
    }
}
