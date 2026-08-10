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
use Flarum\User\RegistrationToken;
use League\OAuth2\Client\Token\AccessToken;
use Omines\OAuth2\Client\Provider\Gitlab;
use Omines\OAuth2\Client\Provider\GitlabResourceOwner;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;

class AuthenticationFlowTest extends TestCase
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
                    'username'           => 'ExistingOAuthUser',
                    'is_email_confirmed' => 1,
                    'email'              => 'existing@machine.local',
                    'password'           => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim',
                    'joined_at'          => '2021-01-01 00:00:00',
                ],
                [
                    'id'                 => 4,
                    'username'           => 'EmailMatchUser',
                    'is_email_confirmed' => 1,
                    'email'              => 'emailmatch@machine.local',
                    'password'           => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim',
                    'joined_at'          => '2021-01-01 00:00:00',
                ],
            ],
            LoginProvider::class => [
                ['id' => 1, 'user_id' => 3, 'provider' => 'gitlab', 'identifier' => '12345', 'created_at' => '2021-01-01 00:00:00'],
            ],
        ]);

        $this->setting('fof-oauth.gitlab.client_id', 'test');
        $this->setting('fof-oauth.gitlab.client_secret', 'test');
        $this->setting('fof-oauth.gitlab', 1);
    }

    // -------------------------------------------------------------------------
    // Existing user with provider link → redirect + remember cookie
    // -------------------------------------------------------------------------

    #[Test]
    public function existing_user_is_redirected_and_receives_remember_cookie(): void
    {
        $this->mockGitlabProvider(12345, 'existing@machine.local');

        [$redirectUrl, $cookies] = $this->runOAuthFlow('/auth/gitlab', '/');

        $this->assertStringStartsWith('/', $redirectUrl);
        $this->assertNotEmpty($cookies);

        $rememberCookie = array_filter($cookies, fn ($n) => str_ends_with($n, 'remember'), ARRAY_FILTER_USE_KEY);
        $this->assertNotEmpty($rememberCookie);
    }

    #[Test]
    public function existing_user_is_redirected_to_returnTo(): void
    {
        $this->mockGitlabProvider(12345, 'existing@machine.local');

        [$redirectUrl] = $this->runOAuthFlow('/auth/gitlab', '/d/42-some-discussion');

        $this->assertEquals('/d/42-some-discussion', $redirectUrl);
    }

    #[Test]
    public function last_login_at_is_updated_on_login(): void
    {
        $this->mockGitlabProvider(12345, 'existing@machine.local');

        $before = LoginProvider::find(1)->last_login_at;

        $this->runOAuthFlow('/auth/gitlab', '/');

        $after = LoginProvider::find(1)->fresh()->last_login_at;
        $this->assertGreaterThanOrEqual($before, $after);
    }

    // -------------------------------------------------------------------------
    // Email match → auto-link + redirect
    // -------------------------------------------------------------------------

    #[Test]
    public function email_match_auto_links_and_redirects(): void
    {
        $this->mockGitlabProvider(99999, 'emailmatch@machine.local');

        [$redirectUrl, $cookies] = $this->runOAuthFlow('/auth/gitlab', '/settings');

        $this->assertStringStartsWith('/settings', $redirectUrl);
        $this->assertStringContainsString('_flarum_linked=gitlab', $redirectUrl);
        $this->assertNotEmpty(array_filter($cookies, fn ($n) => str_ends_with($n, 'remember'), ARRAY_FILTER_USE_KEY));

        // Login provider should now exist for user 4
        $this->assertTrue(
            LoginProvider::where('user_id', 4)
                ->where('provider', 'gitlab')
                ->where('identifier', '99999')
                ->exists()
        );
    }

    // -------------------------------------------------------------------------
    // New user → redirect with _flarum_auth token
    // -------------------------------------------------------------------------

    #[Test]
    public function new_user_redirect_contains_flarum_auth_param(): void
    {
        $this->mockGitlabProvider(11111, 'brandnew@example.com');

        [$redirectUrl, $cookies] = $this->runOAuthFlow('/auth/gitlab', '/');

        $this->assertStringContainsString('_flarum_auth=', $redirectUrl);
        // No remember cookie for new (unregistered) users
        $this->assertEmpty(array_filter($cookies, fn ($n) => $n === 'remember', ARRAY_FILTER_USE_KEY));
    }

    #[Test]
    public function new_user_registration_token_is_persisted(): void
    {
        $this->mockGitlabProvider(22222, 'another@example.com');

        [$redirectUrl] = $this->runOAuthFlow('/auth/gitlab', '/');

        parse_str(parse_url($redirectUrl, PHP_URL_QUERY) ?? '', $qs);
        $token = urldecode($qs['_flarum_auth']);

        $this->assertNotEmpty(RegistrationToken::find($token));
    }

    #[Test]
    public function new_user_flarum_auth_param_appended_to_returnTo(): void
    {
        $this->mockGitlabProvider(33333, 'third@example.com');

        [$redirectUrl] = $this->runOAuthFlow('/auth/gitlab', '/d/99-thread');

        $this->assertStringStartsWith('/d/99-thread', $redirectUrl);
        $this->assertStringContainsString('_flarum_auth=', $redirectUrl);
    }

    // -------------------------------------------------------------------------
    // Disabled / unknown provider → 404
    // -------------------------------------------------------------------------

    #[Test]
    public function disabled_provider_returns_404(): void
    {
        // GitHub is not enabled in setUp
        $response = $this->send($this->request('GET', '/auth/github'));
        $this->assertEquals(404, $response->getStatusCode());
    }

    #[Test]
    public function unknown_provider_returns_404(): void
    {
        $response = $this->send($this->request('GET', '/auth/doesnotexist'));
        $this->assertEquals(404, $response->getStatusCode());
    }

    // -------------------------------------------------------------------------
    // Invalid state → AuthenticationException → error page
    // -------------------------------------------------------------------------

    #[Test]
    public function invalid_state_returns_401_error_page(): void
    {
        $this->mockGitlabProvider(99991, 'any@example.com');

        // Get the initial redirect to capture session cookies, but use a wrong state.
        $init = $this->send($this->request('GET', '/auth/gitlab'));
        $this->assertEquals(302, $init->getStatusCode());

        $response = $this->send(
            $this->request('GET', '/auth/gitlab')
                ->withQueryParams(['code' => 'some-code', 'state' => 'WRONG_STATE'])
                ->withCookieParams($this->toRequestCookies($init))
        );

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertStringNotContainsString('window.close()', (string) $response->getBody());
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Run a complete OAuth flow (initial redirect → callback with code+state).
     * Returns [redirectLocation, responseCookies].
     */
    private function runOAuthFlow(string $authPath, string $returnTo): array
    {
        $initRequest = $this->request('GET', $authPath)
            ->withQueryParams(['returnTo' => $returnTo]);

        $init = $this->send($initRequest);
        $this->assertEquals(302, $init->getStatusCode(), 'Expected initial redirect to provider');

        $location = $init->getHeaderLine('Location');
        parse_str(parse_url($location, PHP_URL_QUERY) ?? '', $qs);

        $callbackRequest = $this->request('GET', $authPath)
            ->withQueryParams(['code' => 'code:abc', 'state' => $qs['state']])
            ->withCookieParams($this->toRequestCookies($init));

        $callback = $this->send($callbackRequest);
        $this->assertEquals(302, $callback->getStatusCode(), 'Expected redirect after callback');

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

        $accessToken = new AccessToken(['access_token' => 'tok', 'expires' => time() + 3600]);
        $mockLeague->method('getAccessToken')->willReturn($accessToken);
        $mockLeague->method('getResourceOwner')->willReturn(
            new GitlabResourceOwner([
                'id'           => $id,
                'email'        => $email,
                'username'     => 'testuser',
                'confirmed_at' => '2026-01-01T00:00:00Z',
            ], $accessToken)
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
