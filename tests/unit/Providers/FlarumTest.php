<?php

/*
 * This file is part of fof/oauth.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\OAuth\Tests\unit\Providers;

use Firebase\JWT\JWT;
use Flarum\Forum\Auth\Registration;
use Flarum\Foundation\Config;
use Flarum\Settings\SettingsRepositoryInterface;
use FoF\OAuth\Errors\AuthenticationException;
use FoF\OAuth\Providers\Custom\Flarum\FlarumProvider;
use FoF\OAuth\Providers\Flarum;
use FoF\OAuth\Tests\fixtures\Flarum\IssuesFlarumIdTokens;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Container\Container;
use Illuminate\Contracts\Cache\Repository as Cache;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\GenericResourceOwner;
use League\OAuth2\Client\Token\AccessToken;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;

class FlarumTest extends ProviderTestCase
{
    use IssuesFlarumIdTokens;

    private const ISSUER = 'https://provider.example';
    private const CLIENT_ID = 'client-id';

    private Cache $cache;

    private TestHandler $logs;

    /** @var array<int, array{request: \Psr\Http\Message\RequestInterface}> */
    private array $history = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->cache = new CacheRepository(new ArrayStore());
        $this->logs = new TestHandler();
        $this->history = [];
    }

    // Registration suggestions and email trust

    private function flarum(array $settings = [], bool $debug = false): Flarum
    {
        $stub = $this->createStub(SettingsRepositoryInterface::class);
        $stub->method('get')->willReturnCallback(fn (string $key) => $settings[$key] ?? null);

        $container = new Container();
        $container->instance(Cache::class, $this->cache);
        $container->instance(LoggerInterface::class, new NullLogger());
        $container->instance(Config::class, new Config(['url' => 'https://consumer.example', 'debug' => $debug]));

        return new Flarum($stub, $container);
    }

    private function suggest(array $userinfo): Registration
    {
        $registration = new Registration();

        $this->flarum()->suggestions($registration, new GenericResourceOwner($userinfo, 'sub'), 'token');

        return $registration;
    }

    #[Test]
    public function confirmed_email_is_trusted(): void
    {
        $this->assertTrustedEmail('user@example.com', $this->suggest(['sub' => '1', 'name' => 'user', 'email' => 'user@example.com', 'email_verified' => true]));
    }

    #[Test]
    public function unconfirmed_email_is_only_suggested(): void
    {
        $this->assertSuggestedEmail('user@example.com', $this->suggest(['sub' => '1', 'name' => 'user', 'email' => 'user@example.com', 'email_verified' => false]));
        $this->assertSuggestedEmail('user@example.com', $this->suggest(['sub' => '1', 'name' => 'user', 'email' => 'user@example.com']));
        $this->assertSuggestedEmail('user@example.com', $this->suggest(['sub' => '1', 'name' => 'user', 'email' => 'user@example.com', 'email_verified' => 'true']));
    }

    #[Test]
    public function missing_email_is_rejected(): void
    {
        $this->expectException(AuthenticationException::class);

        $this->suggest(['sub' => '1', 'name' => 'user']);
    }

    #[Test]
    public function name_is_suggested_as_username(): void
    {
        $this->assertSame('user', $this->suggest(['sub' => '1', 'name' => 'user', 'email' => 'user@example.com'])->getSuggested()['username'] ?? null);
    }

    #[Test]
    public function highest_density_avatar_is_provided(): void
    {
        $registration = $this->suggest([
            'sub'            => '1',
            'email'          => 'user@example.com',
            'picture'        => 'https://provider.example/a.png',
            'picture_srcset' => 'https://provider.example/a.png 1x, https://provider.example/a@3x.png 3x, https://provider.example/a@2x.png 2x',
        ]);

        $this->assertSame('https://provider.example/a@3x.png', $registration->getProvided()['avatar_url'] ?? null);
    }

    #[Test]
    public function picture_is_provided_without_srcset(): void
    {
        $registration = $this->suggest(['sub' => '1', 'email' => 'user@example.com', 'picture' => 'https://provider.example/a.png']);

        $this->assertSame('https://provider.example/a.png', $registration->getProvided()['avatar_url'] ?? null);
    }

    #[Test]
    public function verified_email_requires_email_verified_true(): void
    {
        $verified = fn (array $data) => $this->flarum()->getProviderVerifiedEmail(new GenericResourceOwner($data, 'sub'), 'token');

        $this->assertSame('user@example.com', $verified(['sub' => '1', 'email' => 'user@example.com', 'email_verified' => true]));
        $this->assertNull($verified(['sub' => '1', 'email' => 'user@example.com', 'email_verified' => false]));
        $this->assertNull($verified(['sub' => '1', 'email' => 'user@example.com']));
        $this->assertNull($verified(['sub' => '1', 'email_verified' => true]));
    }

    // Client configuration

    private function clientFor(string $forumUrl, bool $debug = false): FlarumProvider
    {
        return $this->flarum([
            'fof-oauth.flarum.forum_url'     => $forumUrl,
            'fof-oauth.flarum.client_id'     => self::CLIENT_ID,
            'fof-oauth.flarum.client_secret' => 'secret',
        ], $debug)->provider('https://consumer.example/auth/flarum');
    }

    #[Test]
    public function client_uses_the_forum_endpoints_and_space_separated_scopes(): void
    {
        $client = $this->clientFor(' https://provider.example/forum/ ');
        $url = $client->getAuthorizationUrl($this->flarum()->options());

        $this->assertStringStartsWith('https://provider.example/forum/oauth/authorize?', $url);
        $this->assertStringContainsString('scope=openid%20profile%20email', $url);
        $this->assertSame('https://provider.example/forum/oauth/token', $client->getBaseAccessTokenUrl([]));
    }

    #[Test]
    #[DataProvider('acceptedForumUrls')]
    public function forum_url_is_accepted(string $url, bool $debug): void
    {
        $this->assertInstanceOf(FlarumProvider::class, $this->clientFor($url, $debug));
    }

    public static function acceptedForumUrls(): array
    {
        return [
            'https'                => ['https://provider.example', false],
            'http on 127.0.0.1'    => ['http://127.0.0.1:8080', false],
            'http on localhost'    => ['http://localhost/forum', false],
            'http on ::1'          => ['http://[::1]:8080', false],
            'http in debug mode'   => ['http://provider.test', true],
            'uppercase https'      => ['HTTPS://provider.example', false],
        ];
    }

    #[Test]
    #[DataProvider('rejectedForumUrls')]
    public function forum_url_is_rejected(string $url, bool $debug): void
    {
        $this->expectException(RuntimeException::class);

        $this->clientFor($url, $debug);
    }

    public static function rejectedForumUrls(): array
    {
        return [
            'empty'                       => ['', false],
            'no scheme'                   => ['provider.example', false],
            'http'                        => ['http://provider.example', false],
            'http on a 127. host name'    => ['http://127.provider.example', false],
            'other scheme in debug mode'  => ['ftp://provider.example', true],
        ];
    }

    // ID token verification

    private function idToken(array $claims = [], int $key = 0, bool $withKid = false): string
    {
        return $this->issueIdToken(self::ISSUER, self::CLIENT_ID, $claims, $key, $withKid);
    }

    private function client(Response ...$responses): FlarumProvider
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($this->history));

        return new FlarumProvider([
            'clientId'                => self::CLIENT_ID,
            'clientSecret'            => 'secret',
            'redirectUri'             => 'https://consumer.example/auth/flarum',
            'issuer'                  => self::ISSUER,
            'jwksUri'                 => self::ISSUER.'/.well-known/jwks.json',
            'urlAuthorize'            => self::ISSUER.'/oauth/authorize',
            'urlAccessToken'          => self::ISSUER.'/oauth/token',
            'urlResourceOwnerDetails' => self::ISSUER.'/oauth/userinfo',
            'responseResourceOwnerId' => 'sub',
        ], [
            'httpClient' => new Client(['handler' => $stack]),
            'cache'      => $this->cache,
            'logger'     => new Logger('test', [$this->logs]),
        ]);
    }

    private function jwksRequests(): int
    {
        return count(array_filter($this->history, fn ($entry) => str_ends_with($entry['request']->getUri()->getPath(), '/jwks.json')));
    }

    private function login(FlarumProvider $client): GenericResourceOwner
    {
        /** @var AccessToken $token */
        $token = $client->getAccessToken('authorization_code', ['code' => 'code']);

        return $client->getResourceOwner($token);
    }

    private function assertRejected(FlarumProvider $client, string $message): void
    {
        try {
            $this->login($client);
            $this->fail('The login should have been rejected.');
        } catch (IdentityProviderException $e) {
            $this->assertStringContainsString($message, $e->getMessage());
        }
    }

    #[Test]
    public function valid_id_token_and_matching_userinfo_are_accepted(): void
    {
        $owner = $this->login($this->client(
            $this->tokenResponse($this->idToken()),
            $this->jwksResponse(0),
            $this->jsonResponse(['sub' => '42', 'email' => 'user@example.com']),
        ));

        $this->assertSame('42', $owner->getId());
        $this->assertSame(1, $this->jwksRequests());
    }

    #[Test]
    public function key_set_is_cached(): void
    {
        $this->login($this->client($this->tokenResponse($this->idToken()), $this->jwksResponse(0), $this->jsonResponse(['sub' => '42'])));
        $this->login($this->client($this->tokenResponse($this->idToken()), $this->jsonResponse(['sub' => '42'])));

        $this->assertSame(1, $this->jwksRequests());
    }

    #[Test]
    public function token_with_a_kid_is_verified_with_that_key(): void
    {
        $owner = $this->login($this->client(
            $this->tokenResponse($this->idToken(key: 1, withKid: true)),
            $this->jwksResponse(0, 1),
            $this->jsonResponse(['sub' => '42']),
        ));

        $this->assertSame('42', $owner->getId());
    }

    #[Test]
    public function rotated_key_is_fetched_again(): void
    {
        $this->login($this->client($this->tokenResponse($this->idToken()), $this->jwksResponse(0), $this->jsonResponse(['sub' => '42'])));

        $owner = $this->login($this->client(
            $this->tokenResponse($this->idToken(key: 1)),
            $this->jwksResponse(1),
            $this->jsonResponse(['sub' => '42']),
        ));

        $this->assertSame('42', $owner->getId());
        $this->assertSame(2, $this->jwksRequests());
    }

    #[Test]
    public function token_signed_by_another_key_is_rejected(): void
    {
        $this->assertRejected($this->client($this->tokenResponse($this->idToken(key: 1)), $this->jwksResponse(0)), 'no signing key');

        $this->assertSame(1, $this->jwksRequests(), 'A freshly fetched key set should not be fetched again');
    }

    #[Test]
    public function token_naming_a_key_the_refreshed_set_lacks_is_rejected(): void
    {
        $this->login($this->client($this->tokenResponse($this->idToken()), $this->jwksResponse(0), $this->jsonResponse(['sub' => '42'])));

        $this->assertRejected($this->client($this->tokenResponse($this->idToken(key: 1, withKid: true)), $this->jwksResponse(0)), 'no signing key');
        $this->assertSame(2, $this->jwksRequests());
    }

    #[Test]
    public function token_signed_with_the_public_key_as_an_hmac_secret_is_rejected(): void
    {
        $forged = JWT::encode(['iss' => self::ISSUER, 'sub' => '42', 'aud' => self::CLIENT_ID, 'exp' => time() + 3600], self::signingKey(0)['public'], 'HS256');

        $this->assertRejected($this->client($this->tokenResponse($forged), $this->jwksResponse(0)), 'Invalid ID token');
    }

    #[Test]
    public function token_from_another_issuer_is_rejected_and_the_mismatch_logged(): void
    {
        $this->assertRejected($this->client($this->tokenResponse($this->idToken(['iss' => 'http://provider.example'])), $this->jwksResponse(0)), 'unexpected issuer');

        $this->assertTrue($this->logs->hasWarningThatContains('issued by "http://provider.example", but the forum URL is set to "https://provider.example"'));
    }

    #[Test]
    public function token_for_another_client_is_rejected(): void
    {
        $this->assertRejected($this->client($this->tokenResponse($this->idToken(['aud' => 'other'])), $this->jwksResponse(0)), 'not issued to this client');
    }

    #[Test]
    public function audience_may_be_a_list(): void
    {
        $owner = $this->login($this->client(
            $this->tokenResponse($this->idToken(['aud' => ['other', self::CLIENT_ID]])),
            $this->jwksResponse(0),
            $this->jsonResponse(['sub' => '42']),
        ));

        $this->assertSame('42', $owner->getId());
    }

    #[Test]
    public function expired_token_is_rejected(): void
    {
        $this->assertRejected($this->client($this->tokenResponse($this->idToken(['iat' => time() - 7200, 'exp' => time() - 3600])), $this->jwksResponse(0)), 'Expired');
    }

    #[Test]
    public function token_not_yet_valid_is_rejected(): void
    {
        $this->assertRejected($this->client($this->tokenResponse($this->idToken(['nbf' => time() + 3600])), $this->jwksResponse(0)), 'nbf');
    }

    #[Test]
    public function token_without_expiry_is_rejected(): void
    {
        $this->assertRejected($this->client($this->tokenResponse($this->idToken(['exp' => null])), $this->jwksResponse(0)), 'no expiry');
    }

    #[Test]
    public function token_without_a_string_subject_is_rejected(): void
    {
        $this->assertRejected($this->client($this->tokenResponse($this->idToken(['sub' => null])), $this->jwksResponse(0)), 'no subject');
        $this->assertRejected($this->client($this->tokenResponse($this->idToken(['sub' => 42]))), 'no subject');
    }

    #[Test]
    public function small_clock_difference_is_tolerated(): void
    {
        $owner = $this->login($this->client(
            $this->tokenResponse($this->idToken(['iat' => time() + 30])),
            $this->jwksResponse(0),
            $this->jsonResponse(['sub' => '42']),
        ));

        $this->assertSame('42', $owner->getId());
        $this->assertSame(0, JWT::$leeway, 'The global leeway should be restored');
    }

    #[Test]
    public function token_response_without_id_token_is_rejected(): void
    {
        $this->assertRejected($this->client($this->tokenResponse(null)), 'no ID token');
    }

    #[Test]
    public function userinfo_for_another_subject_is_rejected(): void
    {
        $this->assertRejected($this->client($this->tokenResponse($this->idToken()), $this->jwksResponse(0), $this->jsonResponse(['sub' => '43'])), 'does not match');
    }

    #[Test]
    public function unverified_access_token_cannot_fetch_userinfo(): void
    {
        $this->expectException(IdentityProviderException::class);

        $this->client($this->jsonResponse(['sub' => '42']))->getResourceOwner(new AccessToken(['access_token' => 'access-token']));
    }

    #[Test]
    public function unusable_key_set_is_rejected(): void
    {
        $this->assertRejected($this->client($this->tokenResponse($this->idToken()), $this->jsonResponse(['error' => 'server_error'], 500)), 'JWKS');
        $this->assertRejected($this->client($this->tokenResponse($this->idToken()), new Response(200, [], 'not json')), 'JWKS');
        $this->assertRejected($this->client($this->tokenResponse($this->idToken()), $this->jsonResponse(['keys' => [['kty' => 'EC', 'crv' => 'P-256']]])), 'no RS256 signing key');
    }
}
