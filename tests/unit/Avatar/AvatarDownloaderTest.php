<?php

/*
 * This file is part of fof/oauth.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\OAuth\Tests\unit\Avatar;

use Flarum\Testing\unit\TestCase;
use Flarum\User\AvatarValidator;
use FoF\OAuth\Avatar\AvatarDownloader;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Intervention\Image\ImageManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class AvatarDownloaderTest extends TestCase
{
    /** @var array<int, array{request: \Psr\Http\Message\RequestInterface, options: array}> */
    private array $history = [];

    /**
     * @param array<string, string[]> $dns
     */
    private function downloader(array $responses = [], array $dns = [], int $maxKb = 2048, int $maxResolution = 4096 * 4096): AvatarDownloader
    {
        $validator = $this->createStub(AvatarValidator::class);
        $validator->method('getMaxSize')->willReturn($maxKb);
        $validator->method('getMaxResolution')->willReturn($maxResolution);

        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($this->history));
        $client = new Client(['handler' => $stack]);

        return new class(ImageManager::gd(), $validator, $client, $dns) extends AvatarDownloader {
            public function __construct(ImageManager $images, AvatarValidator $validator, private ClientInterface $mock, private array $dns)
            {
                parent::__construct($images, $validator);
            }

            protected function resolve(string $host): array
            {
                return $this->dns[$host] ?? [];
            }

            protected function client(): ClientInterface
            {
                return $this->mock;
            }
        };
    }

    private function png(int $width, int $height): string
    {
        $image = imagecreatetruecolor($width, $height);
        ob_start();
        imagepng($image);

        return (string) ob_get_clean();
    }

    #[Test]
    #[DataProvider('blockedHosts')]
    public function blocked_addresses_are_refused(string $host, array $dns): void
    {
        $this->assertNull($this->downloader(dns: $dns)->allowedAddress($host));
    }

    public static function blockedHosts(): array
    {
        return [
            'loopback'                   => ['127.0.0.1', []],
            'IPv6 loopback'              => ['[::1]', []],
            'link-local metadata'        => ['169.254.169.254', []],
            'unspecified'                => ['0.0.0.0', []],
            'name resolving to loopback' => ['forum.test', ['forum.test' => ['127.0.0.1']]],
            'unresolvable name'          => ['nowhere.invalid', []],
            'NAT64 to loopback'          => ['[64:ff9b::7f00:1]', []],
            'NAT64, expanded spelling'   => ['[64:ff9b:0:0:0:0:7f00:1]', []],
            'NAT64, uppercase'           => ['[64:FF9B::127.0.0.1]', []],
            'local-use NAT64'            => ['[64:ff9b:1::7f00:1]', []],
            '6to4'                       => ['[2002:7f00:1::1]', []],
            'Teredo'                     => ['[2001:0:4136:e378:8000:63bf:80ff:fffe]', []],
            'name resolving to NAT64'    => ['forum.example', ['forum.example' => ['64:ff9b::7f00:1']]],
        ];
    }

    #[Test]
    #[DataProvider('allowedHosts')]
    public function allowed_addresses_are_accepted(string $host, array $dns, string $expected): void
    {
        $this->assertSame($expected, $this->downloader(dns: $dns)->allowedAddress($host));
    }

    public static function allowedHosts(): array
    {
        return [
            'public IP'                          => ['93.184.216.34', [], '93.184.216.34'],
            'private LAN IP, allowed as in core' => ['192.168.1.10', [], '192.168.1.10'],
            'public IPv6'                        => ['[2606:4700::1111]', [], '2606:4700::1111'],
            'public IPv6 under 2001::/16'        => ['[2001:4860:4860::8888]', [], '2001:4860:4860::8888'],
            'name'                               => ['forum.example', ['forum.example' => ['93.184.216.34']], '93.184.216.34'],
            'first allowed of several'           => ['forum.example', ['forum.example' => ['127.0.0.1', '10.0.0.5']], '10.0.0.5'],
        ];
    }

    #[Test]
    public function avatar_is_downloaded_from_the_checked_address(): void
    {
        $image = $this->downloader([new Response(200, ['Content-Type' => 'image/png'], $this->png(300, 300))])
            ->download('https://93.184.216.34/avatar@3x.png');

        $this->assertNotNull($image);
        $this->assertSame(300, $image->width());
        $this->assertSame(['93.184.216.34:443:93.184.216.34'], $this->history[0]['options']['curl'][CURLOPT_RESOLVE]);
        $this->assertFalse($this->history[0]['options']['allow_redirects']);
    }

    #[Test]
    public function ipv6_hosts_and_ports_are_pinned(): void
    {
        $this->downloader([new Response(200, [], $this->png(1, 1))])->download('http://[2606:4700::1111]:8080/a.png');
        $this->downloader([new Response(200, [], $this->png(1, 1))], dns: ['forum.example' => ['2606:4700::1111']])->download('https://forum.example/a.png');

        $this->assertSame(['[2606:4700::1111]:8080:[2606:4700::1111]'], $this->history[0]['options']['curl'][CURLOPT_RESOLVE]);
        $this->assertSame(['forum.example:443:[2606:4700::1111]'], $this->history[1]['options']['curl'][CURLOPT_RESOLVE]);
        $this->assertSame('', $this->history[1]['options']['proxy']);
    }

    #[Test]
    public function blocked_or_unsupported_urls_are_not_requested(): void
    {
        $downloader = $this->downloader(dns: ['forum.test' => ['127.0.0.1']]);

        $this->assertNull($downloader->download('http://forum.test/avatar.png'));
        $this->assertNull($downloader->download('http://127.0.0.1/avatar.png'));
        $this->assertNull($downloader->download('ftp://93.184.216.34/avatar.png'));
        $this->assertNull($downloader->download('/assets/avatars/a.png'));
        $this->assertSame([], $this->history);
    }

    #[Test]
    public function failed_responses_give_no_avatar(): void
    {
        $this->assertNull($this->downloader([new Response(404)])->download('https://93.184.216.34/a.png'));
        $this->assertNull($this->downloader([new Response(302, ['Location' => 'http://127.0.0.1/a.png'])])->download('https://93.184.216.34/a.png'));
        $this->assertNull($this->downloader([new Response(200, [], 'not an image')])->download('https://93.184.216.34/a.png'));
    }

    #[Test]
    public function oversized_avatars_are_refused(): void
    {
        $png = $this->png(300, 300);

        $tiny = $this->png(1, 1);
        $this->assertNotNull($this->downloader([new Response(200, [], $tiny)], maxKb: 1)->download('https://93.184.216.34/a.png'), 'Fits the cap');
        $this->assertNull($this->downloader([new Response(200, ['Content-Length' => '5000'], $tiny)], maxKb: 1)->download('https://93.184.216.34/a.png'), 'Declared too large');
        $noise = imagecreatetruecolor(300, 300);
        for ($i = 0; $i < 20000; $i++) {
            imagesetpixel($noise, random_int(0, 299), random_int(0, 299), random_int(0, 0xFFFFFF));
        }
        ob_start();
        imagepng($noise);
        $large = (string) ob_get_clean();
        $this->assertGreaterThan(50 * 1024, strlen($large));
        $this->assertNotNull($this->downloader([new Response(200, [], $large)], maxKb: 1024)->download('https://93.184.216.34/a.png'), 'Fits a larger cap');
        $this->assertNull($this->downloader([new Response(200, [], $large)], maxKb: 50)->download('https://93.184.216.34/a.png'), 'Body too large, no Content-Length');
        $this->assertNull($this->downloader([new Response(200, [], $png)], maxResolution: 200 * 200)->download('https://93.184.216.34/a.png'), 'Resolution too large');
    }
}
