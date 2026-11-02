<?php

/*
 * This file is part of fof/oauth.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\OAuth\Tests\fixtures\Flarum;

use Firebase\JWT\JWT;
use GuzzleHttp\Psr7\Response;

/**
 * Plays the flarum/oauth-provider side of a login: RS256 key pairs, their JWKS, ID tokens and endpoint responses.
 */
trait IssuesFlarumIdTokens
{
    /** @var array<int, array{private: string, public: string, jwk: array<string, string>}> */
    private static array $signingKeys = [];

    /**
     * @return array{private: string, public: string, jwk: array<string, string>}
     */
    protected static function signingKey(int $index): array
    {
        if (!isset(self::$signingKeys[$index])) {
            $resource = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
            openssl_pkey_export($resource, $private);
            $details = openssl_pkey_get_details($resource);

            self::$signingKeys[$index] = [
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

        return self::$signingKeys[$index];
    }

    /**
     * Like flarum/oauth-provider, the header has no `kid` unless asked for. A claim set to null is left out.
     */
    protected function issueIdToken(string $issuer, string $audience, array $claims = [], int $key = 0, bool $withKid = false): string
    {
        $claims = array_filter([
            'iss' => $issuer,
            'sub' => '42',
            'aud' => $audience,
            'iat' => time(),
            'exp' => time() + 3600,
            ...$claims,
        ], fn ($value) => $value !== null);

        return JWT::encode($claims, self::signingKey($key)['private'], 'RS256', $withKid ? "key-$key" : null);
    }

    protected function jwksResponse(int ...$keys): Response
    {
        return $this->jsonResponse(['keys' => array_map(fn (int $key) => self::signingKey($key)['jwk'], $keys)]);
    }

    protected function tokenResponse(?string $idToken): Response
    {
        return $this->jsonResponse(array_filter([
            'access_token' => 'access-token',
            'token_type'   => 'Bearer',
            'expires_in'   => 3600,
            'id_token'     => $idToken,
        ]));
    }

    protected function jsonResponse(array $body, int $status = 200): Response
    {
        return new Response($status, ['Content-Type' => 'application/json'], (string) json_encode($body));
    }
}
