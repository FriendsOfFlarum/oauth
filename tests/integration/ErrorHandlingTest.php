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

use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Psr\Http\Message\ResponseInterface;

/**
 * Covers `FoF\OAuth\Middleware\ErrorHandler`, which is bypassed in debug mode.
 */
class ErrorHandlingTest extends TestCase
{
    use RetrievesAuthorizedUsers;
    use PerformsOAuthLogin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config('debug', false);
        $this->extension('fof-oauth');

        $this->prepareDatabase([
            'users' => [$this->normalUser()],
        ]);

        $this->setting('fof-oauth.gitlab', 1);
        $this->setting('fof-oauth.gitlab.client_id', 'test');
        $this->setting('fof-oauth.gitlab.client_secret', 'test');
    }

    private function callbackWithState(string $state): ResponseInterface
    {
        $init = $this->send($this->request('GET', '/auth/gitlab'));

        $this->assertEquals(302, $init->getStatusCode());

        return $this->send(
            $this->request('GET', '/auth/gitlab')
                ->withQueryParams(['code' => 'code', 'state' => $state])
                ->withCookieParams($this->cookiesFrom($init))
        );
    }

    public function test_invalid_state_returns_401_html_page(): void
    {
        $response = $this->callbackWithState('TAMPERED');

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertStringContainsString('text/html', $response->getHeaderLine('Content-Type'));
        $this->assertStringNotContainsString('authenticationComplete', $response->getBody()->getContents());
    }

    public function test_error_page_does_not_expose_exception_details(): void
    {
        $body = $this->callbackWithState('TAMPERED')->getBody()->getContents();

        $this->assertStringNotContainsString('FoF\\OAuth\\Errors\\AuthenticationException', $body);
        $this->assertStringNotContainsString('Stack trace', $body);
    }

    public function test_missing_state_returns_401(): void
    {
        $this->mockGitlab(['id' => 1, 'username' => 'user', 'email' => 'user@example.com']);

        $init = $this->send($this->request('GET', '/auth/gitlab'));

        $response = $this->send(
            $this->request('GET', '/auth/gitlab')
                ->withQueryParams(['code' => 'code'])
                ->withCookieParams($this->cookiesFrom($init))
        );

        $this->assertEquals(401, $response->getStatusCode());
    }
}
