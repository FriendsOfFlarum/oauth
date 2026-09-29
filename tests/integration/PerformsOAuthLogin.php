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
use FoF\OAuth\Providers\GitHub;
use FoF\OAuth\Providers\GitLab;
use GuzzleHttp\Psr7\Response;
use League\OAuth2\Client\Provider\Github as GithubClient;
use League\OAuth2\Client\Provider\GithubResourceOwner;
use League\OAuth2\Client\Token\AccessToken;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Omines\OAuth2\Client\Provider\Gitlab as GitlabClient;
use Omines\OAuth2\Client\Provider\GitlabResourceOwner;
use Psr\Http\Message\ResponseInterface;

trait PerformsOAuthLogin
{
    /**
     * Replace the GitLab provider's client so the OAuth round trip returns the given resource owner.
     */
    protected function mockGitlab(array $resourceOwner): void
    {
        $container = $this->app()->getContainer();

        $client = $this->getMockBuilder(GitlabClient::class)
            ->setConstructorArgs([[
                'clientId'     => 'test',
                'clientSecret' => 'test',
                'redirectUri'  => 'http://localhost/auth/gitlab',
            ]])
            ->onlyMethods(['getAccessToken', 'getResourceOwner'])
            ->getMock();

        $accessToken = new AccessToken(['access_token' => 'token', 'expires' => time() + 3600]);
        $client->method('getAccessToken')->willReturn($accessToken);
        $client->method('getResourceOwner')->willReturn(
            new GitlabResourceOwner(array_merge(['avatar_url' => 'https://gitlab.example/avatar.png'], $resourceOwner), $accessToken)
        );

        $provider = $this->getMockBuilder(GitLab::class)
            ->setConstructorArgs([$container->make(SettingsRepositoryInterface::class)])
            ->onlyMethods(['provider'])
            ->getMock();
        $provider->method('provider')->willReturn($client);

        $container->instance(GitLab::class, $provider);
    }

    /**
     * Replace the GitHub provider's client: the round trip returns `$resourceOwner` from `GET /user`,
     * and `GET /user/emails` returns `$emails`.
     */
    protected function mockGithub(array $resourceOwner, array $emails): void
    {
        $container = $this->app()->getContainer();

        $client = $this->getMockBuilder(GithubClient::class)
            ->setConstructorArgs([[
                'clientId'     => 'test',
                'clientSecret' => 'test',
                'redirectUri'  => 'http://localhost/auth/github',
            ]])
            ->onlyMethods(['getAccessToken', 'getResourceOwner', 'getResponse'])
            ->getMock();

        $accessToken = new AccessToken(['access_token' => 'token', 'expires' => time() + 3600]);
        $client->method('getAccessToken')->willReturn($accessToken);
        $client->method('getResourceOwner')->willReturn(new GithubResourceOwner($resourceOwner));
        $client->method('getResponse')->willReturn(new Response(200, [], (string) json_encode($emails)));

        $provider = $this->getMockBuilder(GitHub::class)
            ->setConstructorArgs([$container->make(SettingsRepositoryInterface::class)])
            ->onlyMethods(['provider'])
            ->getMock();
        $provider->method('provider')->willReturn($client);

        $container->instance(GitHub::class, $provider);
    }

    /**
     * Capture everything logged to Flarum's logger from now on.
     */
    protected function captureLogs(): TestHandler
    {
        $handler = new TestHandler();

        /** @var Logger $logger */
        $logger = $this->app()->getContainer()->make('log');
        $logger->pushHandler($handler);

        return $handler;
    }

    /**
     * Raw MIME messages sent through the `log` mail driver (set `mail_driver` to `log` before booting),
     * optionally only those addressed to `$to`.
     *
     * @return string[]
     */
    protected function sentMail(TestHandler $logs, ?string $to = null): array
    {
        $messages = [];

        foreach ($logs->getRecords() as $record) {
            $message = (string) $record['message'];

            if (strpos($message, 'Subject:') === false || !preg_match('/^To: (.+)$/m', $message, $matches)) {
                continue;
            }

            if ($to === null || strpos($matches[1], $to) !== false) {
                $messages[] = $message;
            }
        }

        return $messages;
    }

    /**
     * Run the redirect + callback round trip and return the callback response.
     */
    protected function oauthCallback(string $provider): ResponseInterface
    {
        $response = $this->send($this->request('GET', "/auth/$provider"));

        $this->assertEquals(302, $response->getStatusCode(), 'OAuth redirect expected');

        parse_str((string) parse_url($response->getHeaderLine('location'), PHP_URL_QUERY), $query);

        return $this->send(
            $this->request('GET', "/auth/$provider")
                ->withQueryParams(['code' => 'code', 'state' => $query['state']])
                ->withCookieParams($this->cookiesFrom($response))
        );
    }

    /**
     * Run the account-linking round trip as `$actorId`, asking to link to `$linkTo`, and return the callback response.
     *
     * @param int|string $linkTo
     */
    protected function oauthLinkCallback(string $provider, int $actorId, $linkTo): ResponseInterface
    {
        $init = $this->requestAsUser(
            $this->request('GET', "/auth/$provider")->withQueryParams(['linkTo' => $linkTo]),
            $actorId
        );

        $response = $this->send($init);

        $this->assertEquals(302, $response->getStatusCode(), 'OAuth redirect expected');

        parse_str((string) parse_url($response->getHeaderLine('location'), PHP_URL_QUERY), $query);

        // Keep the same remember cookie as the init request: a different token would invalidate the session
        // holding the OAuth state and linkTo value.
        return $this->send(
            $this->request('GET', "/auth/$provider")
                ->withQueryParams(['code' => 'code', 'state' => $query['state']])
                ->withCookieParams(array_merge($init->getCookieParams(), $this->cookiesFrom($response)))
                ->withAttribute('bypassCsrfToken', true)
        );
    }

    /**
     * Run the OAuth round trip and return the payload passed to `authenticationComplete()`.
     */
    protected function oauthLogin(string $provider): array
    {
        $response = $this->oauthCallback($provider);
        $content = $response->getBody()->getContents();

        $this->assertEquals(200, $response->getStatusCode(), $content);
        $this->assertEquals(1, preg_match('/authenticationComplete\((.*)\)/', $content, $matches), $content);

        return json_decode($matches[1], true);
    }

    protected function cookiesFrom(ResponseInterface $response): array
    {
        $cookies = [];

        foreach (SetCookies::fromResponse($response)->getAll() as $cookie) {
            $cookies[$cookie->getName()] = $cookie->getValue();
        }

        return $cookies;
    }
}
