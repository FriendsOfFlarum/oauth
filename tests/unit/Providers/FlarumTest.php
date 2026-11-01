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
use Flarum\Settings\SettingsRepositoryInterface;
use FoF\OAuth\Errors\AuthenticationException;
use FoF\OAuth\Providers\Custom\Flarum\FlarumProvider;
use FoF\OAuth\Providers\Flarum;
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
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;

class FlarumTest extends ProviderTestCase
{
    private const ISSUER = 'https://provider.example';
    private const CLIENT_ID = 'client-id';

    /** @var array<int, array{private: string, jwk: array<string, string>}> */
    private static array $keys = [];

    private Cache $cache;

    /** @var array<int, array{request: \Psr\Http\Message\RequestInterface}> */
    private array $history = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->cache = new CacheRepository(new ArrayStore());
        $this->history = [];
    }

    // Registration suggestions and email trust

    private function flarum(array $settings = []): Flarum
    {
        $stub = $this->createStub(SettingsRepositoryInterface::class);
        $stub->method('get')->willReturnCallback(fn (string $key) => $settings[$key] ?? null);

        $container = new Container();
        $container->instance(Cache::class, $this->cache);

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

    #[Test]
    public function client_uses_the_forum_endpoints_and_space_separated_scopes(): void
    {
        $provider = $this->flarum([
            'fof-oauth.flarum.forum_url'     => ' https://provider.example/forum/ ',
            'fof-oauth.flarum.client_id'     => self::CLIENT_ID,
            'fof-oauth.flarum.client_secret' => 'secret',
        ]);

        $client = $provider->provider('https://consumer.example/auth/flarum');
        $url = $client->getAuthorizationUrl($provider->options());

        $this->assertInstanceOf(FlarumProvider::class, $client);
        $this->assertStringStartsWith('https://provider.example/forum/oauth/authorize?', $url);
        $this->assertStringContainsString('scope=openid%20profile%20email', $url);
        $this->assertSame('https://provider.example/forum/oauth/token', $client->getBaseAccessTokenUrl([]));
    }

    #[Test]
    public function client_requires_a_forum_url(): void
    {
        $this->expectException(RuntimeException::class);

        $this->flarum()->provider('https://consumer.example/auth/flarum');
    }

    // ID token verification

    private static function key(int $index): array
    {
        if (!isset(self::$keys[$index])) {
            $resource = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
            openssl_pkey_export($resource, $private);
            $details = openssl_pkey_get_details($resource);

            self::$keys[$index] = [
                'private' => $private,
                'public'  => $details['key'],
                'jwk'     => [
                    'kty' => 'RSA',
                    'use' => 'sig',
                    'alg' => 'RS256',
                    'kid' => "key-$index",
                    'n'   => rtrim(strtr(base64_encode($details['rsa']['n']), '+/', '-_'), '='),
                    'e'   => rtrim(strtr(base64_encode($details['rsa']['e']), '+/', '-_'), '='),
                ],
            ];
        }

        return self::$keys[$index];
    }

    private function idToken(array $claims = [], int $key = 0, bool $withKid = false): string
    {
        $claims = array_filter([
            'iss' => self::ISSUER,
            'sub' => '42',
            'aud' => self::CLIENT_ID,
            'iat' => time(),
            'exp' => time() + 3600,
            ...$claims,
        ], fn ($value) => $value !== null);

        return JWT::encode($claims, self::key($key)['private'], 'RS256', $withKid ? "key-$key" : null);
    }

    private function jwks(int ...$keys): Response
    {
        return $this->json(['keys' => array_map(fn (int $key) => self::key($key)['jwk'], $keys)]);
    }

    private function json(array $body, int $status = 200): Response
    {
        return new Response($status, ['Content-Type' => 'application/json'], json_encode($body));
    }

    private function tokenResponse(?string $idToken): Response
    {
        return $this->json(array_filter([
            'access_token' => 'access-token',
            'token_type'   => 'Bearer',
            'expires_in'   => 3600,
            'id_token'     => $idToken,
        ]));
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
            $this->jwks(0),
            $this->json(['sub' => '42', 'email' => 'user@example.com']),
        ));

        $this->assertSame('42', $owner->getId());
        $this->assertSame(1, $this->jwksRequests());
    }

    #[Test]
    public function key_set_is_cached(): void
    {
        $this->login($this->client($this->tokenResponse($this->idToken()), $this->jwks(0), $this->json(['sub' => '42'])));
        $this->login($this->client($this->tokenResponse($this->idToken()), $this->json(['sub' => '42'])));

        $this->assertSame(1, $this->jwksRequests());
    }

    #[Test]
    public function token_with_a_kid_is_verified_with_that_key(): void
    {
        $owner = $this->login($this->client(
            $this->tokenResponse($this->idToken(key: 1, withKid: true)),
            $this->jwks(0, 1),
            $this->json(['sub' => '42']),
        ));

        $this->assertSame('42', $owner->getId());
    }

    #[Test]
    public function rotated_key_is_fetched_again(): void
    {
        $this->login($this->client($this->tokenResponse($this->idToken()), $this->jwks(0), $this->json(['sub' => '42'])));

        $owner = $this->login($this->client(
            $this->tokenResponse($this->idToken(key: 1)),
            $this->jwks(1),
            $this->json(['sub' => '42']),
        ));

        $this->assertSame('42', $owner->getId());
        $this->assertSame(2, $this->jwksRequests());
    }

    #[Test]
    public function token_signed_by_another_key_is_rejected(): void
    {
        $this->assertRejected($this->client($this->tokenResponse($this->idToken(key: 1)), $this->jwks(0)), 'no signing key');

        $this->assertSame(1, $this->jwksRequests(), 'A freshly fetched key set should not be fetched again');
    }

    #[Test]
    public function token_signed_with_the_public_key_as_an_hmac_secret_is_rejected(): void
    {
        $forged = JWT::encode(['iss' => self::ISSUER, 'sub' => '42', 'aud' => self::CLIENT_ID, 'exp' => time() + 3600], self::key(0)['public'], 'HS256');

        $this->assertRejected($this->client($this->tokenResponse($forged), $this->jwks(0)), 'Invalid ID token');
    }

    #[Test]
    public function token_from_another_issuer_is_rejected(): void
    {
        $this->assertRejected($this->client($this->tokenResponse($this->idToken(['iss' => 'https://other.example'])), $this->jwks(0)), 'unexpected issuer');
    }

    #[Test]
    public function token_for_another_client_is_rejected(): void
    {
        $this->assertRejected($this->client($this->tokenResponse($this->idToken(['aud' => 'other'])), $this->jwks(0)), 'not issued to this client');
    }

    #[Test]
    public function audience_may_be_a_list(): void
    {
        $owner = $this->login($this->client(
            $this->tokenResponse($this->idToken(['aud' => ['other', self::CLIENT_ID]])),
            $this->jwks(0),
            $this->json(['sub' => '42']),
        ));

        $this->assertSame('42', $owner->getId());
    }

    #[Test]
    public function expired_token_is_rejected(): void
    {
        $this->assertRejected($this->client($this->tokenResponse($this->idToken(['iat' => time() - 7200, 'exp' => time() - 3600])), $this->jwks(0)), 'Expired');
    }

    #[Test]
    public function token_without_expiry_is_rejected(): void
    {
        $this->assertRejected($this->client($this->tokenResponse($this->idToken(['exp' => null])), $this->jwks(0)), 'no expiry');
    }

    #[Test]
    public function small_clock_difference_is_tolerated(): void
    {
        $owner = $this->login($this->client(
            $this->tokenResponse($this->idToken(['iat' => time() + 30])),
            $this->jwks(0),
            $this->json(['sub' => '42']),
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
        $this->assertRejected($this->client($this->tokenResponse($this->idToken()), $this->jwks(0), $this->json(['sub' => '43'])), 'does not match');
    }

    #[Test]
    public function unverified_access_token_cannot_fetch_userinfo(): void
    {
        $this->expectException(IdentityProviderException::class);

        $this->client($this->json(['sub' => '42']))->getResourceOwner(new AccessToken(['access_token' => 'access-token']));
    }

    #[Test]
    public function unusable_key_set_is_rejected(): void
    {
        $this->assertRejected($this->client($this->tokenResponse($this->idToken()), $this->json(['error' => 'server_error'], 500)), 'JWKS');
        $this->assertRejected($this->client($this->tokenResponse($this->idToken()), new Response(200, [], 'not json')), 'JWKS');
        $this->assertRejected($this->client($this->tokenResponse($this->idToken()), $this->json(['keys' => [['kty' => 'EC', 'crv' => 'P-256']]])), 'no RS256 signing key');
    }
}
