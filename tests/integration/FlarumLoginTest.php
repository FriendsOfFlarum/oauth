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

use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\LoginProvider;
use Flarum\User\User;
use FoF\OAuth\Providers\Flarum;
use FoF\OAuth\Tests\fixtures\Flarum\IssuesFlarumIdTokens;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Response;
use Illuminate\Contracts\Container\Container;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\RequestInterface;

/**
 * Runs the Flarum provider's real OpenID Connect client through the login controller, against a fake provider forum.
 */
class FlarumLoginTest extends TestCase
{
    use RetrievesAuthorizedUsers;
    use PerformsOAuthLogin;
    use IssuesFlarumIdTokens;

    private const FORUM_URL = 'https://provider.example';
    private const CLIENT_ID = 'client-id';

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('fof-oauth');

        $this->prepareDatabase([
            User::class => [
                ['id' => 4, 'username' => 'existing', 'is_email_confirmed' => 1, 'email' => 'existing@machine.local', 'joined_at' => '2021-01-01 00:00:00'],
            ],
        ]);

        $this->setting('fof-oauth.flarum', 1);
        // Unique per test, so the cached key set of one test isn't used by another.
        $this->setting('fof-oauth.flarum.forum_url', self::FORUM_URL.'/'.bin2hex(random_bytes(4)));
        $this->setting('fof-oauth.flarum.client_id', self::CLIENT_ID);
        $this->setting('fof-oauth.flarum.client_secret', 'secret');
        $this->setting('fof-oauth.update_email_from_provider', 0);
    }

    /**
     * Answer the provider forum's token, JWKS and UserInfo endpoints with the given responses.
     */
    private function mockProviderForum(Response $token, Response $jwks, Response $userinfo): void
    {
        $container = $this->app()->getContainer();
        $settings = $container->make(SettingsRepositoryInterface::class);

        $http = new Client(['handler' => HandlerStack::create(fn (RequestInterface $request) => Create::promiseFor(
            match (true) {
                str_ends_with($request->getUri()->getPath(), '/oauth/token')            => $token,
                str_ends_with($request->getUri()->getPath(), '/.well-known/jwks.json')  => $jwks,
                str_ends_with($request->getUri()->getPath(), '/oauth/userinfo')         => $userinfo,
            }
        ))]);

        $real = new Flarum($settings, $container->make(Container::class));

        $provider = $this->getMockBuilder(Flarum::class)
            ->setConstructorArgs([$settings, $container->make(Container::class)])
            ->onlyMethods(['provider'])
            ->getMock();
        $provider->method('provider')->willReturnCallback(function (string $redirectUri) use ($real, $http) {
            $client = $real->provider($redirectUri);
            $client->setHttpClient($http);

            return $client;
        });

        $container->instance(Flarum::class, $provider);
    }

    private function idToken(array $claims = []): string
    {
        $issuer = $this->app()->getContainer()->make(SettingsRepositoryInterface::class)->get('fof-oauth.flarum.forum_url');

        return $this->issueIdToken($issuer, self::CLIENT_ID, $claims);
    }

    #[Test]
    public function confirmed_email_matching_an_account_logs_in_and_links_it(): void
    {
        $this->mockProviderForum(
            $this->tokenResponse($this->idToken()),
            $this->jwksResponse(0),
            $this->jsonResponse(['sub' => '42', 'name' => 'Existing', 'email' => 'existing@machine.local', 'email_verified' => true]),
        );

        $location = $this->oauthLogin('flarum');

        $this->assertStringNotContainsString('_flarum_auth', $location);
        $this->assertStringContainsString('_flarum_linked=flarum', $location);
        $this->assertSame(4, LoginProvider::where('provider', 'flarum')->where('identifier', '42')->value('user_id'));
    }

    #[Test]
    public function unconfirmed_email_matching_an_account_is_not_linked(): void
    {
        $this->mockProviderForum(
            $this->tokenResponse($this->idToken()),
            $this->jwksResponse(0),
            $this->jsonResponse(['sub' => '42', 'name' => 'Existing', 'email' => 'existing@machine.local', 'email_verified' => false]),
        );

        $this->assertStringContainsString('_fof_oauth_unverified=flarum', $this->oauthLogin('flarum'));
        $this->assertFalse(LoginProvider::where('provider', 'flarum')->exists());
    }

    #[Test]
    public function userinfo_for_another_subject_is_rejected(): void
    {
        $this->mockProviderForum(
            $this->tokenResponse($this->idToken(['sub' => '43'])),
            $this->jwksResponse(0),
            $this->jsonResponse(['sub' => '42', 'name' => 'Existing', 'email' => 'existing@machine.local', 'email_verified' => true]),
        );

        $this->setting('fof-oauth.log-oauth-errors', 1);
        $logs = $this->captureLogs();

        $response = $this->oauthCallback('flarum');

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertTrue($logs->hasErrorThatContains('The UserInfo subject does not match the ID token.'));
        $this->assertArrayNotHasKey('flarum_remember', $this->cookiesFrom($response));
        $this->assertFalse(LoginProvider::where('provider', 'flarum')->exists());
    }

    #[Test]
    public function id_token_signed_by_another_key_is_rejected(): void
    {
        $this->mockProviderForum(
            $this->tokenResponse($this->idToken()),
            $this->jwksResponse(1),
            $this->jsonResponse(['sub' => '42', 'name' => 'Existing', 'email' => 'existing@machine.local', 'email_verified' => true]),
        );

        $this->setting('fof-oauth.log-oauth-errors', 1);
        $logs = $this->captureLogs();

        $this->assertEquals(401, $this->oauthCallback('flarum')->getStatusCode());
        $this->assertTrue($logs->hasErrorThatContains('no signing key of the issuer verifies its signature'));
        $this->assertFalse(LoginProvider::where('provider', 'flarum')->exists());
    }
}
