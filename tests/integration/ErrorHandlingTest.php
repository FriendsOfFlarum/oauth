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

use Dflydev\FigCookies\SetCookies;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use League\OAuth2\Client\Token\AccessToken;
use Omines\OAuth2\Client\Provider\Gitlab;
use Omines\OAuth2\Client\Provider\GitlabResourceOwner;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;

class ErrorHandlingTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('fof-oauth');

        $this->prepareDatabase([
            'users' => [$this->normalUser()],
        ]);

        $this->setting('fof-oauth.gitlab.client_id', 'test');
        $this->setting('fof-oauth.gitlab.client_secret', 'test');
        $this->setting('fof-oauth.gitlab', 1);
    }

    #[Test]
    public function invalid_state_returns_401_html_page_not_js(): void
    {
        $init = $this->send($this->request('GET', '/auth/gitlab'));
        $this->assertEquals(302, $init->getStatusCode());

        // Submit a callback with a wrong state.
        $response = $this->send(
            $this->request('GET', '/auth/gitlab')
                ->withQueryParams(['code' => 'some-code', 'state' => 'TAMPERED'])
                ->withCookieParams($this->toRequestCookies($init))
        );

        $this->assertEquals(401, $response->getStatusCode());

        $body = (string) $response->getBody();

        // Must not contain any JS window manipulation.
        $this->assertStringNotContainsString('window.close()', $body);
        $this->assertStringNotContainsString('window.opener', $body);

        // Must be HTML.
        $contentType = $response->getHeaderLine('Content-Type');
        $this->assertStringContainsString('text/html', $contentType);
    }

    #[Test]
    public function error_page_does_not_expose_raw_exception_message(): void
    {
        $init = $this->send($this->request('GET', '/auth/gitlab'));
        $response = $this->send(
            $this->request('GET', '/auth/gitlab')
                ->withQueryParams(['code' => 'code', 'state' => 'bad'])
                ->withCookieParams($this->toRequestCookies($init))
        );

        $body = (string) $response->getBody();
        // Raw PHP exception messages should not appear verbatim in the response.
        $this->assertStringNotContainsString('FoF\\OAuth\\Errors\\AuthenticationException', $body);
        $this->assertStringNotContainsString('Throwable', $body);
    }

    private function toRequestCookies(ResponseInterface $response): array
    {
        $cookies = [];
        foreach (SetCookies::fromResponse($response)->getAll() as $cookie) {
            $cookies[$cookie->getName()] = $cookie->getValue();
        }
        return $cookies;
    }
}
