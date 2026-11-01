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

use Flarum\Forum\Auth\Registration;
use FoF\OAuth\Errors\AuthenticationException;
use FoF\OAuth\Provider;
use FoF\OAuth\Providers\Discord;
use FoF\OAuth\Providers\GitLab;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;
use Omines\OAuth2\Client\Provider\GitlabResourceOwner;
use PHPUnit\Framework\Attributes\Test;
use Wohali\OAuth2\Client\Provider\DiscordResourceOwner;

class AvatarUrlTest extends ProviderTestCase
{
    #[Test]
    public function bundled_provider_gives_its_avatar_without_needing_an_email(): void
    {
        $user = new GitlabResourceOwner(['id' => 1, 'avatar_url' => 'https://gitlab.example/a.png'], new AccessToken(['access_token' => 't']));

        $this->assertSame('https://gitlab.example/a.png', (new GitLab($this->settings()))->avatarUrl($user, 'token'));
    }

    #[Test]
    public function discord_default_avatar_is_only_for_sign_ups(): void
    {
        $discord = new Discord($this->settings());
        $withoutAvatar = new DiscordResourceOwner(['id' => '1', 'username' => 'user', 'email' => 'user@example.com', 'verified' => true]);
        $withAvatar = new DiscordResourceOwner(['id' => '1', 'username' => 'user', 'email' => 'user@example.com', 'verified' => true, 'avatar' => 'abc']);

        $this->assertNull($discord->avatarUrl($withoutAvatar, 'token'));
        $this->assertSame('https://cdn.discordapp.com/avatars/1/abc.png', $discord->avatarUrl($withAvatar, 'token'));

        $registration = new Registration();
        $discord->suggestions($registration, $withoutAvatar, 'token');
        $this->assertSame('https://cdn.discordapp.com/embed/avatars/0.png', $registration->getProvided()['avatar_url'] ?? null);
    }

    private function thirdParty(callable $suggestions): Provider
    {
        return new class($this->settings(), $suggestions) extends Provider {
            public function __construct($settings, private $callback)
            {
                parent::__construct($settings);
            }

            public function name(): string
            {
                return 'third';
            }

            public function link(): string
            {
                return '';
            }

            public function fields(): array
            {
                return [];
            }

            public function pkceEnabled(): bool
            {
                return false;
            }

            public function provider(string $redirectUri): ?AbstractProvider
            {
                return null;
            }

            public function suggestions(Registration $registration, mixed $user, string $token): void
            {
                ($this->callback)($registration);
            }
        };
    }

    #[Test]
    public function other_providers_give_the_avatar_from_their_suggestions(): void
    {
        $this->assertSame('https://third.example/a@3x.png', $this->thirdParty(function (Registration $registration) {
            $registration->provideAvatar('https://third.example/a.png')->provideAvatar3x('https://third.example/a@3x.png');
        })->avatarUrl(null, 'token'));

        $this->assertNull($this->thirdParty(function () {
            throw new AuthenticationException('invalid_email');
        })->avatarUrl(null, 'token'), 'A provider that needs an email it lacks gives no avatar');
    }
}
