<?php

/*
 * This file is part of fof/oauth.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\OAuth\Tests\unit\Controllers;

use Flarum\Testing\unit\TestCase;
use FoF\OAuth\Controllers\AbstractOAuthController;
use FoF\OAuth\Errors\AuthenticationException;
use Illuminate\Session\Store;
use Mockery as m;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;

class AbstractOAuthControllerTest extends TestCase
{
    private Store $session;
    private ServerRequestInterface $request;

    protected function setUp(): void
    {
        parent::setUp();

        $this->session = m::mock(Store::class);
        $this->request = m::mock(ServerRequestInterface::class);
    }

    /**
     * Access the protected validateReturnTo method via a concrete stub.
     */
    private function controller(): object
    {
        return new class extends AbstractOAuthController {
            public function __construct()
            {
                // No-op: we only test the concrete methods, not the full flow.
            }

            public function callValidateReturnTo(string $returnTo, ServerRequestInterface $request): string
            {
                return $this->validateReturnTo($returnTo, $request);
            }

            protected function getRouteName(): string { return 'fof-oauth'; }
            protected function getProvider(string $r): \League\OAuth2\Client\Provider\AbstractProvider { return m::mock(\League\OAuth2\Client\Provider\AbstractProvider::class); }
            protected function getProviderName(): string { return 'test'; }
            protected function getAuthorizationUrlOptions(): array { return []; }
            protected function isPkceEnabled(): bool { return false; }
            protected function getIdentifier($user): string { return ''; }
            protected function setSuggestions(\Flarum\Forum\Auth\Registration $r, $user, string $token): void {}
        };
    }

    // -------------------------------------------------------------------------
    // validateReturnTo
    // -------------------------------------------------------------------------

    #[Test]
    public function valid_relative_path_is_accepted(): void
    {
        $request = m::mock(ServerRequestInterface::class);
        $request->shouldReceive('getHeaderLine')->with('Referer')->andReturn('')->byDefault();

        $result = $this->controller()->callValidateReturnTo('/d/42-some-discussion', $request);

        $this->assertEquals('/d/42-some-discussion', $result);
    }

    #[Test]
    public function external_url_is_rejected_and_replaced_with_slash(): void
    {
        $request = m::mock(ServerRequestInterface::class);

        $result = $this->controller()->callValidateReturnTo('https://evil.com/steal', $request);

        $this->assertEquals('/', $result);
    }

    #[Test]
    public function protocol_relative_url_is_rejected(): void
    {
        $request = m::mock(ServerRequestInterface::class);

        $result = $this->controller()->callValidateReturnTo('//evil.com/steal', $request);

        $this->assertEquals('/', $result);
    }

    #[Test]
    public function non_slash_prefixed_path_is_rejected(): void
    {
        $request = m::mock(ServerRequestInterface::class);

        $result = $this->controller()->callValidateReturnTo('evil.com/path', $request);

        $this->assertEquals('/', $result);
    }

    #[Test]
    public function empty_returnTo_falls_back_to_referer_path(): void
    {
        $request = m::mock(ServerRequestInterface::class);
        $request->shouldReceive('getHeaderLine')->with('Referer')->andReturn('https://forum.example.com/d/42-discussion');

        $result = $this->controller()->callValidateReturnTo('', $request);

        $this->assertEquals('/d/42-discussion', $result);
    }

    #[Test]
    public function empty_returnTo_with_no_referer_falls_back_to_slash(): void
    {
        $request = m::mock(ServerRequestInterface::class);
        $request->shouldReceive('getHeaderLine')->with('Referer')->andReturn('');

        $result = $this->controller()->callValidateReturnTo('', $request);

        $this->assertEquals('/', $result);
    }

    #[Test]
    public function returnTo_with_query_string_is_preserved(): void
    {
        $request = m::mock(ServerRequestInterface::class);

        $result = $this->controller()->callValidateReturnTo('/d/42?page=2', $request);

        $this->assertEquals('/d/42?page=2', $result);
    }
}
