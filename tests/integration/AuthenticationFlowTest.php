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
                    'id'                 => 3, 'username' => 'Seboubeach',
                    'is_email_confirmed' => 1, 'email' => 'Seboubeach1@machine.local',
                    'joined_at'          => '2021-01-01 00:00:00',
                ],
                [
                    'id'                 => 4, 'username' => 'Hephoica',
                    'is_email_confirmed' => 1, 'email' => 'Hephoica@machine.local',
                    'joined_at'          => '2021-01-01 00:00:00',
                ],
            ],
            'login_providers' => [
                ['id' => 1, 'user_id' => 3, 'provider' => 'gitlab', 'identifier' => '123456'],
            ],
            'group_permission' => [
                ['permission' => 'user.editOwnNickname', 'group_id' => 4],
            ],
        ]);

        $this->setting('fof-oauth.gitlab.client_id', 'test');
        $this->setting('fof-oauth.gitlab.client_secret', 'test');
        $this->setting('fof-oauth.gitlab', 1);
    }

    public function test_loginProvider_is_set_with_correct_value_after_oauth_login(): void
    {
        $this->mockProvider('123456', 'Seboubeach1@machine.local');

        $response = $this->send($this->request('GET', '/auth/gitlab'));

        // get query params from location url in the header
        $location = $response->getHeaderLine('location');
        parse_str(parse_url($location, PHP_URL_QUERY), $query);

        $request = $this->request('GET', '/auth/gitlab')
            ->withQueryParams([
                'code'  => 'code:123456',
                'state' => $query['state'],
            ])
            ->withCookieParams($this->toRequestCookies($response));

        $response = $this->send($request);
        $content = $response->getBody()->getContents();

        // check if the content contains is_loggedIn
        $this->assertStringContainsString(
            'window.opener.app.authenticationComplete(',
            $content
        );

        preg_match('/window.opener.app.authenticationComplete\((.*)\)/', $content, $matches);
        $json = json_decode($matches[1], true);

        $this->assertArrayHasKey('loggedIn', $json);

        $response = $this->send($this->request('GET', '/')->withCookieParams($this->toRequestCookies($response)));
        $this->assertEquals(200, $response->getStatusCode());

        $this->checkOauthProviderIsSerialized($this->toRequestCookies($response), 'gitlab');
    }

    protected function checkOauthProviderIsSerialized(array $cookies, ?string $value = null): void
    {
        $response = $this->send(
            $this->request('GET', '/api')->withCookieParams($cookies)
        );

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);

        // get user from included
        $user = array_filter($body['included'], function ($item) {
            return $item['type'] === 'users';
        });

        $user = array_values($user)[0];
        $this->assertArrayHasKey('loginProvider', $user['attributes']);
        $this->assertEquals($value, $user['attributes']['loginProvider']);
    }

    private function mockProvider(string $identifier, string $email): void
    {
        $container = $this->app()->getContainer();

        $mockProvider = $this->getMockBuilder(Gitlab::class)
            ->setConstructorArgs([
                'options' => [
                    'clientId'     => 'test',
                    'clientSecret' => 'test',
                    'redirectUri'  => 'http://localhost/auth/gitlab',
                ],
            ])
            ->onlyMethods(['getAccessToken', 'getResourceOwner'])
            ->getMock();

        $accessToken = new AccessToken(['access_token' => '123456', 'expires' => time() + 3600]);
        $mockProvider->method('getAccessToken')->willReturn(
            $accessToken
        );
        $mockProvider->method('getResourceOwner')->willReturn(
            new GitlabResourceOwner(['id' => $identifier, 'email' => $email], $accessToken)
        );

        $mockFofProvider = $this->getMockBuilder(\FoF\OAuth\Providers\GitLab::class)
            ->setConstructorArgs([
                'settings' => $container->make(SettingsRepositoryInterface::class),
            ])
            ->onlyMethods(['provider'])
            ->getMock();
        $mockFofProvider->method('provider')->willReturn($mockProvider);

        $this->app()->getContainer()->instance(\FoF\OAuth\Providers\GitLab::class, $mockFofProvider);
    }

    protected function toRequestCookies(ResponseInterface $response): array
    {
        $responseCookies = [];
        foreach (SetCookies::fromResponse($response)->getAll() as $cookie) {
            $responseCookies[$cookie->getName()] = $cookie->getValue();
        }

        return $responseCookies;
    }
}
