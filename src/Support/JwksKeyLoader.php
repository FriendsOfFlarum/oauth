<?php

/*
 * This file is part of fof/oauth.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\OAuth\Support;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use RuntimeException;

/**
 * Fetches a JSON Web Key Set from an OIDC provider's `/.well-known/jwks.json`
 * endpoint and exposes RS256 signing keys as PEM-encoded strings suitable for
 * passing to `lcobucci/jwt` via `OpenIDConnectClient\OpenIDConnectProvider`.
 *
 * Results are cached per issuer with a 1-day TTL. Callers can invalidate the
 * cache entry when verification fails (e.g. after provider key rotation) by
 * calling {@see forget()}.
 */
class JwksKeyLoader
{
    protected const CACHE_TTL_SECONDS = 86400;

    // Resolving Cache eagerly at construct time pulls `cache.store` into the
    // container before fof/redis has registered all its Redis connections,
    // breaking the settings cache. Keep a container reference and resolve
    // lazily on first use instead.
    public function __construct(protected Container $container)
    {
    }

    protected function cache(): Cache
    {
        return $this->container->make(Cache::class);
    }

    /**
     * Load all RS256 public keys from the given JWKS URL, PEM-encoded.
     *
     * @return string[] Array of PEM-encoded public keys.
     */
    public function load(string $jwksUrl): array
    {
        $cacheKey = $this->cacheKey($jwksUrl);
        $cache = $this->cache();

        $cached = $cache->get($cacheKey);
        if (is_array($cached) && ! empty($cached)) {
            return $cached;
        }

        $keys = $this->fetch($jwksUrl);

        $cache->put($cacheKey, $keys, self::CACHE_TTL_SECONDS);

        return $keys;
    }

    public function forget(string $jwksUrl): void
    {
        $this->cache()->forget($this->cacheKey($jwksUrl));
    }

    protected function fetch(string $jwksUrl): array
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 5,
                'header' => "Accept: application/json\r\n",
            ],
        ]);

        $body = @file_get_contents($jwksUrl, false, $context);
        if ($body === false) {
            throw new RuntimeException("Failed to fetch JWKS from $jwksUrl");
        }

        $document = json_decode($body, true);
        if (! is_array($document) || ! isset($document['keys']) || ! is_array($document['keys'])) {
            throw new RuntimeException("Malformed JWKS document at $jwksUrl");
        }

        $keys = [];
        foreach ($document['keys'] as $entry) {
            if (! is_array($entry)) continue;
            if (($entry['kty'] ?? null) !== 'RSA') continue;
            if (($entry['alg'] ?? 'RS256') !== 'RS256') continue;
            if (! isset($entry['n'], $entry['e'])) continue;

            $keys[] = $this->jwkToPem($entry['n'], $entry['e']);
        }

        if (empty($keys)) {
            throw new RuntimeException("No RS256 signing keys found in JWKS at $jwksUrl");
        }

        return $keys;
    }

    /**
     * Convert a JWK's base64url-encoded modulus + exponent into a PEM-encoded
     * RSA public key. The output format is what lcobucci/jwt's InMemory key
     * loader expects.
     */
    protected function jwkToPem(string $modulus, string $exponent): string
    {
        $modBytes = $this->base64UrlDecode($modulus);
        $expBytes = $this->base64UrlDecode($exponent);

        // DER-encode an RSA public key: SEQUENCE { modulus INTEGER, exponent INTEGER }
        $modDer = $this->derInteger($modBytes);
        $expDer = $this->derInteger($expBytes);
        $rsaDer = $this->derSequence($modDer.$expDer);

        // Wrap in SubjectPublicKeyInfo: SEQUENCE { AlgorithmIdentifier, BIT STRING }
        // AlgorithmIdentifier for rsaEncryption (1.2.840.113549.1.1.1)
        $rsaOid = "\x30\x0d\x06\x09\x2a\x86\x48\x86\xf7\x0d\x01\x01\x01\x05\x00";
        $bitString = $this->derBitString($rsaDer);
        $spki = $this->derSequence($rsaOid.$bitString);

        $pem = "-----BEGIN PUBLIC KEY-----\n"
            .chunk_split(base64_encode($spki), 64, "\n")
            ."-----END PUBLIC KEY-----\n";

        return $pem;
    }

    protected function base64UrlDecode(string $value): string
    {
        $pad = strlen($value) % 4;
        if ($pad) {
            $value .= str_repeat('=', 4 - $pad);
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);
        if ($decoded === false) {
            throw new InvalidArgumentException('Invalid base64url value in JWK');
        }

        return $decoded;
    }

    protected function derInteger(string $bytes): string
    {
        // Strip leading zero bytes but keep one if the high bit is set (to keep the number positive).
        while (strlen($bytes) > 1 && $bytes[0] === "\x00" && (ord($bytes[1]) & 0x80) === 0) {
            $bytes = substr($bytes, 1);
        }
        if ((ord($bytes[0]) & 0x80) !== 0) {
            $bytes = "\x00".$bytes;
        }

        return "\x02".$this->derLength(strlen($bytes)).$bytes;
    }

    protected function derSequence(string $contents): string
    {
        return "\x30".$this->derLength(strlen($contents)).$contents;
    }

    protected function derBitString(string $contents): string
    {
        // BIT STRING: one leading byte indicating unused bits (always 0 for us).
        return "\x03".$this->derLength(strlen($contents) + 1)."\x00".$contents;
    }

    protected function derLength(int $length): string
    {
        if ($length < 128) {
            return chr($length);
        }

        $bytes = '';
        while ($length > 0) {
            $bytes = chr($length & 0xff).$bytes;
            $length >>= 8;
        }

        return chr(0x80 | strlen($bytes)).$bytes;
    }

    protected function cacheKey(string $jwksUrl): string
    {
        return 'fof-oauth.jwks.'.hash('sha256', $jwksUrl);
    }
}
