<?php

/*
 * This file is part of fof/oauth.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\OAuth\Avatar;

use Flarum\User\AvatarValidator;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Throwable;

/**
 * Downloads an avatar image from a URL a provider shared, for an account that is linked rather than signed up.
 *
 * Follows the rules core applies to avatars at sign-up: http(s) only, no redirects, and no loopback, link-local or
 * reserved addresses (private LAN addresses are allowed, as in core). The connection is pinned to the checked
 * address with the cURL handler, the body is capped at the avatar size limit, and the resolution is checked before
 * the image is decoded. Any failure gives null: the avatar is optional.
 */
class AvatarDownloader
{
    public const TIMEOUT = 5;

    /**
     * IPv6 ranges that embed an IPv4 address the reserved-range filter doesn't look into: NAT64 (well-known and
     * local-use), 6to4 and Teredo. Where a gateway exists they reach any IPv4 address, loopback included.
     */
    protected const EMBEDDED_IPV4_PREFIXES = [
        ['64:ff9b::', 96],
        ['64:ff9b:1::', 48],
        ['2002::', 16],
        ['2001::', 32],
    ];

    public function __construct(
        protected ImageManager $images,
        protected AvatarValidator $validator,
    ) {
    }

    public function download(string $url): ?ImageInterface
    {
        $parts = parse_url($url);
        $scheme = strtolower($parts['scheme'] ?? '');
        $host = $parts['host'] ?? '';

        if (!in_array($scheme, ['http', 'https'], true) || $host === '' || !defined('CURLOPT_RESOLVE')) {
            return null;
        }

        $ip = $this->allowedAddress($host);

        if ($ip === null) {
            return null;
        }

        $port = $parts['port'] ?? ($scheme === 'https' ? 443 : 80);
        $maxBytes = $this->validator->getMaxSize() * 1024;
        $body = new CappedBuffer($maxBytes);

        try {
            $response = $this->client()->request('GET', $url, [
                'allow_redirects' => false,
                'http_errors'     => false,
                'timeout'         => self::TIMEOUT,
                'connect_timeout' => self::TIMEOUT,
                // Talk to the checked address directly, never through a proxy.
                'proxy' => '',
                // Connect to the address that passed the check, so DNS can't be rebound in between.
                'curl'       => [CURLOPT_RESOLVE => ["$host:$port:".(str_contains($ip, ':') ? "[$ip]" : $ip)]],
                'sink'       => $body,
                'on_headers' => function (ResponseInterface $response) use ($maxBytes) {
                    $length = $response->getHeaderLine('Content-Length');

                    if ($length !== '' && (int) $length > $maxBytes) {
                        throw new RuntimeException('The avatar is too large.');
                    }
                },
            ]);
        } catch (Throwable) {
            return null;
        }

        if ($body->exceeded() || $response->getStatusCode() !== 200) {
            return null;
        }

        $contents = (string) $body;
        $dimensions = @getimagesizefromstring($contents);

        if ($dimensions === false || $dimensions[0] * $dimensions[1] > $this->validator->getMaxResolution()) {
            return null;
        }

        try {
            return $this->images->read($contents);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * The first address of the host that may be connected to, or null if it has none.
     */
    public function allowedAddress(string $host): ?string
    {
        $literal = trim($host, '[]');

        $addresses = filter_var($literal, FILTER_VALIDATE_IP) !== false ? [$literal] : $this->resolve($host);

        foreach ($addresses as $address) {
            if ($this->embedsIpv4($address)) {
                continue;
            }

            if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE) !== false) {
                return $address;
            }
        }

        return null;
    }

    /**
     * Compared as binary, so every spelling of an address is caught.
     */
    protected function embedsIpv4(string $address): bool
    {
        $packed = @inet_pton($address);

        if ($packed === false || strlen($packed) !== 16) {
            return false;
        }

        foreach (self::EMBEDDED_IPV4_PREFIXES as [$prefix, $bits]) {
            $prefixPacked = (string) inet_pton($prefix);
            $bytes = intdiv($bits, 8);

            if (substr($packed, 0, $bytes) === substr($prefixPacked, 0, $bytes)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return string[]
     */
    protected function resolve(string $host): array
    {
        return array_merge(
            gethostbynamel($host) ?: [],
            array_column(@dns_get_record($host, DNS_AAAA) ?: [], 'ipv6')
        );
    }

    protected function client(): ClientInterface
    {
        // The cURL handler: Guzzle's default stack may use the stream handler, which ignores CURLOPT_RESOLVE.
        return new Client(['handler' => HandlerStack::create(new CurlHandler())]);
    }
}
