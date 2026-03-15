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
            'login_providers' => [
                // user 4 already has gitlab linked to a different identifier
                ['id' => 1, 'user_id' => 4, 'provider' => 'gitlab', 'identifier' => 'userb-gitlab-id'],
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
        $this->mockGitlabProvider('new-gitlab-id-for-usera', 'usera@machine.local');

        [$location] = $this->runLinkFlow(userId: 3, providerIdentifier: 'new-gitlab-id-for-usera', returnTo: '/settings');

        $this->assertStringStartsWith('/settings', $location);

        $this->assertTrue(
            LoginProvider::where('user_id', 3)
                ->where('provider', 'gitlab')
                ->where('identifier', 'new-gitlab-id-for-usera')
                ->exists()
        );
    }

    #[Test]
    public function link_redirects_to_returnTo(): void
    {
        $this->mockGitlabProvider('another-new-id', 'usera@machine.local');

        [$location] = $this->runLinkFlow(userId: 3, providerIdentifier: 'another-new-id', returnTo: '/u/UserA/security');

        $this->assertEquals('/u/UserA/security', $location);
    }

    // -------------------------------------------------------------------------
    // Mismatch: linkTo ID doesn't match authenticated user
    // -------------------------------------------------------------------------

    #[Test]
    public function link_fails_when_linkTo_user_id_does_not_match_actor(): void
    {
        $this->mockGitlabProvider('some-new-id', 'usera@machine.local');

        // User 3 tries to link as user 4 — mismatch.
        $initRequest = $this->requestAsUser(
            $this->request('GET', '/auth/gitlab')->withQueryParams(['linkTo' => 4, 'returnTo' => '/settings']),
            3
        );

        $init = $this->send($initRequest);
        $this->assertEquals(302, $init->getStatusCode());

        $location = $init->getHeaderLine('Location');
        parse_str(parse_url($location, PHP_URL_QUERY) ?? '', $qs);

        $callbackRequest = $this->requestAsUser(
            $this->request('GET', '/auth/gitlab')
                ->withQueryParams(['code' => 'code:abc', 'state' => $qs['state']])
                ->withCookieParams($this->toRequestCookies($init)),
            3
        );

        $callback = $this->send($callbackRequest);

        // Expect an error response (401) due to user mismatch.
        $this->assertEquals(401, $callback->getStatusCode());
    }

    // -------------------------------------------------------------------------
    // Already linked to another user
    // -------------------------------------------------------------------------

    #[Test]
    public function link_fails_when_provider_already_linked_to_another_user(): void
    {
        // userb-gitlab-id is already linked to user 4. Try to link it to user 3.
        $this->mockGitlabProvider('userb-gitlab-id', 'usera@machine.local');

        $initRequest = $this->requestAsUser(
            $this->request('GET', '/auth/gitlab')->withQueryParams(['linkTo' => 3, 'returnTo' => '/settings']),
            3
        );

        $init = $this->send($initRequest);
        $location = $init->getHeaderLine('Location');
        parse_str(parse_url($location, PHP_URL_QUERY) ?? '', $qs);

        $callback = $this->send(
            $this->requestAsUser(
                $this->request('GET', '/auth/gitlab')
                    ->withQueryParams(['code' => 'code:abc', 'state' => $qs['state']])
                    ->withCookieParams($this->toRequestCookies($init)),
                3
            )
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
    private function runLinkFlow(int $userId, string $providerIdentifier, string $returnTo): array
    {
        $this->mockGitlabProvider($providerIdentifier, 'usera@machine.local');

        $initRequest = $this->requestAsUser(
            $this->request('GET', '/auth/gitlab')
                ->withQueryParams(['linkTo' => $userId, 'returnTo' => $returnTo]),
            $userId
        );

        $init = $this->send($initRequest);
        $this->assertEquals(302, $init->getStatusCode());

        $location = $init->getHeaderLine('Location');
        parse_str(parse_url($location, PHP_URL_QUERY) ?? '', $qs);

        $callback = $this->send(
            $this->requestAsUser(
                $this->request('GET', '/auth/gitlab')
                    ->withQueryParams(['code' => 'code:abc', 'state' => $qs['state']])
                    ->withCookieParams($this->toRequestCookies($init)),
                $userId
            )
        );

        $this->assertEquals(302, $callback->getStatusCode());

        return [
            $callback->getHeaderLine('Location'),
            $this->toRequestCookies($callback),
        ];
    }

    private function mockGitlabProvider(string $identifier, string $email): void
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
            new GitlabResourceOwner(['id' => $identifier, 'email' => $email], $token)
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
