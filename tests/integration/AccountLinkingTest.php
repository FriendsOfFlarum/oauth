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
use Flarum\User\LoginProvider;
use League\OAuth2\Client\Token\AccessToken as OAuthToken;
use Omines\OAuth2\Client\Provider\Gitlab;
use Omines\OAuth2\Client\Provider\GitlabResourceOwner;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;

class AccountLinkingTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('fof-oauth');

        $this->prepareDatabase([
            'users' => [
                $this->normalUser(),
                [
                    'id'                 => 3,
                    'username'           => 'UserA',
                    'is_email_confirmed' => 1,
                    'email'              => 'usera@machine.local',
                    'password'           => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim',
                    'joined_at'          => '2021-01-01 00:00:00',
                ],
                [
                    'id'                 => 4,
                    'username'           => 'UserB',
                    'is_email_confirmed' => 1,
                    'email'              => 'userb@machine.local',
                    'password'           => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim',
                    'joined_at'          => '2021-01-01 00:00:00',
                ],
            ],
            LoginProvider::class => [
                // user 4 already has gitlab linked to a different identifier
                ['id' => 1, 'user_id' => 4, 'provider' => 'gitlab', 'identifier' => '55555', 'created_at' => '2021-01-01 00:00:00'],
            ],
        ]);

        $this->setting('fof-oauth.gitlab.client_id', 'test');
        $this->setting('fof-oauth.gitlab.client_secret', 'test');
        $this->setting('fof-oauth.gitlab', 1);
    }

    // -------------------------------------------------------------------------
    // Successful link: authenticated user links a new provider account
    // -------------------------------------------------------------------------

    #[Test]
    public function authenticated_user_can_link_provider_account(): void
    {
        $this->mockGitlabProvider(44444, 'usera@machine.local');

        [$location] = $this->runLinkFlow(userId: 3, providerId: 44444, returnTo: '/settings');

        $this->assertStringStartsWith('/settings', $location);

        $this->assertTrue(
            LoginProvider::where('user_id', 3)
                ->where('provider', 'gitlab')
                ->where('identifier', '44444')
                ->exists()
        );
    }

    #[Test]
    public function link_redirects_to_returnTo(): void
    {
        $this->mockGitlabProvider(66666, 'usera@machine.local');

        [$location] = $this->runLinkFlow(userId: 3, providerId: 66666, returnTo: '/u/UserA/security');

        $this->assertStringStartsWith('/u/UserA/security', $location);
        $this->assertStringContainsString('_flarum_linked=gitlab', $location);
    }

    // -------------------------------------------------------------------------
    // Mismatch: linkTo ID doesn't match authenticated user
    // -------------------------------------------------------------------------

    #[Test]
    public function link_fails_when_linkTo_user_id_does_not_match_actor(): void
    {
        $this->mockGitlabProvider(77777, 'usera@machine.local');

        // User 3 tries to link as user 4 — mismatch.
        $initRequest = $this->requestAsUser(
            $this->request('GET', '/auth/gitlab')->withQueryParams(['linkTo' => 4, 'returnTo' => '/settings']),
            3
        );

        $init = $this->send($initRequest);
        $this->assertEquals(302, $init->getStatusCode());

        $location = $init->getHeaderLine('Location');
        parse_str(parse_url($location, PHP_URL_QUERY) ?? '', $qs);

        $callbackCookies = array_merge($initRequest->getCookieParams(), $this->toRequestCookies($init));
        $callback = $this->send(
            $this->request('GET', '/auth/gitlab')
                ->withQueryParams(['code' => 'code:abc', 'state' => $qs['state']])
                ->withCookieParams($callbackCookies)
                ->withAttribute('bypassCsrfToken', true)
        );

        // Expect an error response (401) due to user mismatch.
        $this->assertEquals(401, $callback->getStatusCode());
    }

    // -------------------------------------------------------------------------
    // Already linked to another user
    // -------------------------------------------------------------------------

    #[Test]
    public function link_fails_when_provider_already_linked_to_another_user(): void
    {
        // 55555 is already linked to user 4. Try to link it to user 3.
        $this->mockGitlabProvider(55555, 'usera@machine.local');

        $initRequest = $this->requestAsUser(
            $this->request('GET', '/auth/gitlab')->withQueryParams(['linkTo' => 3, 'returnTo' => '/settings']),
            3
        );

        $init = $this->send($initRequest);
        $location = $init->getHeaderLine('Location');
        parse_str(parse_url($location, PHP_URL_QUERY) ?? '', $qs);

        $callbackCookies = array_merge($initRequest->getCookieParams(), $this->toRequestCookies($init));
        $callback = $this->send(
            $this->request('GET', '/auth/gitlab')
                ->withQueryParams(['code' => 'code:abc', 'state' => $qs['state']])
                ->withCookieParams($callbackCookies)
                ->withAttribute('bypassCsrfToken', true)
        );

        $this->assertEquals(401, $callback->getStatusCode());
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Run a full account-linking OAuth flow as the given user.
     * Returns [redirectLocation, cookies].
     */
    private function runLinkFlow(int $userId, int $providerId, string $returnTo): array
    {
        $this->mockGitlabProvider($providerId, 'usera@machine.local');

        // The init request must be authenticated so linkTo validation passes.
        $initRequest = $this->requestAsUser(
            $this->request('GET', '/auth/gitlab')
                ->withQueryParams(['linkTo' => $userId, 'returnTo' => $returnTo]),
            $userId
        );

        $init = $this->send($initRequest);
        $this->assertEquals(302, $init->getStatusCode());

        $location = $init->getHeaderLine('Location');
        parse_str(parse_url($location, PHP_URL_QUERY) ?? '', $qs);

        // The callback must carry:
        //   - The flarum_session cookie from the init response (so the OAuth state and
        //     linkTo values stored in the cache can be retrieved using the same session ID).
        //   - The SAME flarum_remember cookie that was used in the init request (token_A),
        //     NOT a new one — because RememberFromCookie compares session.access_token against
        //     the cookie token and invalidates the session (regenerating its ID) when they differ.
        //     The init request wrote token_A into the session; a new token_B would cause a mismatch.
        $sessionCookies = $this->toRequestCookies($init);          // flarum_session=<id>
        $initRequestCookies = $initRequest->getCookieParams();      // flarum_remember=<token_A>
        $callbackCookies = array_merge($initRequestCookies, $sessionCookies);

        $callback = $this->send(
            $this->request('GET', '/auth/gitlab')
                ->withQueryParams(['code' => 'code:abc', 'state' => $qs['state']])
                ->withCookieParams($callbackCookies)
                ->withAttribute('bypassCsrfToken', true)
        );

        $body = $callback->getBody()->getContents();
        $this->assertEquals(302, $callback->getStatusCode(), 'body: '.strip_tags($body));

        return [
            $callback->getHeaderLine('Location'),
            $this->toRequestCookies($callback),
        ];
    }

    private function mockGitlabProvider(int $id, string $email): void
    {
        $container = $this->app()->getContainer();

        $mockLeague = $this->getMockBuilder(Gitlab::class)
            ->setConstructorArgs([[
                'options' => [
                    'clientId'     => 'test',
                    'clientSecret' => 'test',
                    'redirectUri'  => 'http://localhost/auth/gitlab',
                ],
            ]])
            ->onlyMethods(['getAccessToken', 'getResourceOwner'])
            ->getMock();

        $token = new OAuthToken(['access_token' => 'tok', 'expires' => time() + 3600]);
        $mockLeague->method('getAccessToken')->willReturn($token);
        $mockLeague->method('getResourceOwner')->willReturn(
            new GitlabResourceOwner([
                'id'           => $id,
                'email'        => $email,
                'username'     => 'testuser',
                'confirmed_at' => '2026-01-01T00:00:00Z',
            ], $token)
        );

        $mockFofProvider = $this->getMockBuilder(\FoF\OAuth\Providers\GitLab::class)
            ->setConstructorArgs(['settings' => $container->make(SettingsRepositoryInterface::class)])
            ->onlyMethods(['provider'])
            ->getMock();
        $mockFofProvider->method('provider')->willReturn($mockLeague);

        $container->instance(\FoF\OAuth\Providers\GitLab::class, $mockFofProvider);
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
