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
use FoF\OAuth\Tests\fixtures\ThirdParty\UrlDependentProvider;
use FoF\OAuth\Tests\fixtures\ThirdParty\VerifiedThirdPartyProvider;
use Laminas\Diactoros\Response\TextResponse;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ProviderRegistrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('fof-oauth');
        $this->extend(
            new RegisterProvider(UrlDependentProvider::class),
            new RegisterProvider(VerifiedThirdPartyProvider::class),
            (new Extend\Routes('forum'))->get('/auth/other', 'other.auth', fn () => new class() implements RequestHandlerInterface {
                public function handle(ServerRequestInterface $request): ResponseInterface
                {
                    return new TextResponse('other');
                }
            })
        );

        $this->setting('fof-oauth.thirdparty', 1);
        $this->setting('fof-oauth.verifiedthirdparty', 1);
    }

    #[Test]
    public function a_provider_depending_on_urls_does_not_drop_routes_during_registration(): void
    {
        $this->assertSame(302, $this->send($this->request('GET', '/auth/thirdparty'))->getStatusCode());
        $this->assertSame(302, $this->send($this->request('GET', '/auth/verifiedthirdparty'))->getStatusCode());

        $response = $this->send($this->request('GET', '/auth/other'));
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('other', (string) $response->getBody());
        $this->assertSame(404, $this->send($this->request('GET', '/auth/unknown'))->getStatusCode());
    }
}
