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

use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\LoginProvider;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;
use FoF\OAuth\Avatar\AvatarDownloader;
use FoF\OAuth\Providers\Discord;
use League\OAuth2\Client\Token\AccessToken;
use Wohali\OAuth2\Client\Provider\Discord as DiscordClient;
use Wohali\OAuth2\Client\Provider\DiscordResourceOwner;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;
use PHPUnit\Framework\Attributes\Test;

/**
 * An account that is linked to a provider, rather than signed up through it, gets the provider's avatar if it has none.
 */
class AvatarOnLinkTest extends TestCase
{
    use RetrievesAuthorizedUsers;
    use PerformsOAuthLogin;

    private const AVATAR = 'https://gitlab.example/avatar.png';

    /** @var string[] URLs the downloader was asked for */
    private array $downloads = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('fof-oauth');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 4, 'username' => 'plain', 'is_email_confirmed' => 1, 'email' => 'plain@machine.local', 'avatar_url' => null, 'joined_at' => '2021-01-01 00:00:00'],
                ['id' => 5, 'username' => 'pictured', 'is_email_confirmed' => 1, 'email' => 'pictured@machine.local', 'avatar_url' => 'mine.webp', 'joined_at' => '2021-01-01 00:00:00'],
            ],
        ]);

        $this->setting('fof-oauth.gitlab', 1);
        $this->setting('fof-oauth.gitlab.client_id', 'test');
        $this->setting('fof-oauth.gitlab.client_secret', 'test');
        $this->setting('fof-oauth.discord', 1);
        $this->setting('fof-oauth.discord.client_id', 'test');
        $this->setting('fof-oauth.discord.client_secret', 'test');
        $this->setting('fof-oauth.update_email_from_provider', 0);
    }

    private function mockDiscord(array $resourceOwner): void
    {
        $container = $this->app()->getContainer();

        $client = $this->getMockBuilder(DiscordClient::class)
            ->setConstructorArgs([['clientId' => 'test', 'clientSecret' => 'test', 'redirectUri' => 'http://localhost/auth/discord']])
            ->onlyMethods(['getAccessToken', 'getResourceOwner'])
            ->getMock();
        $client->method('getAccessToken')->willReturn(new AccessToken(['access_token' => 'token', 'expires' => time() + 3600]));
        $client->method('getResourceOwner')->willReturn(new DiscordResourceOwner($resourceOwner));

        $provider = $this->getMockBuilder(Discord::class)
            ->setConstructorArgs([$container->make(SettingsRepositoryInterface::class)])
            ->onlyMethods(['provider'])
            ->getMock();
        $provider->method('provider')->willReturn($client);

        $container->instance(Discord::class, $provider);
    }

    /**
     * Replace the downloader: it returns a 300px image, or nothing when $fails.
     */
    private function fakeDownloads(bool $fails = false, ?callable $during = null): void
    {
        $container = $this->app()->getContainer();
        $downloads = &$this->downloads;

        $container->instance(AvatarDownloader::class, new class($container->make(ImageManager::class), $fails, $downloads, $during) extends AvatarDownloader {
            public function __construct(private ImageManager $manager, private bool $fails, private array &$downloads, private $during)
            {
            }

            public function download(string $url): ?ImageInterface
            {
                $this->downloads[] = $url;

                if ($this->during) {
                    ($this->during)();
                }

                return $this->fails ? null : $this->manager->create(300, 300);
            }
        });
    }

    private function user(int $id): User
    {
        return User::query()->findOrFail($id);
    }

    #[Test]
    public function account_linked_by_its_confirmed_email_gets_the_avatar(): void
    {
        $this->mockGitlab(['id' => 999, 'username' => 'plain', 'email' => 'plain@machine.local', 'confirmed_at' => '2021-01-01T00:00:00Z']);
        $this->fakeDownloads();

        $this->assertStringContainsString('_flarum_linked=gitlab', $this->oauthLogin('gitlab'));

        $user = $this->user(4);
        $this->assertSame([self::AVATAR], $this->downloads);
        $this->assertNotNull($user->getRawOriginal('avatar_url'));
        $this->assertTrue((bool) $user->has_avatar_2x);
        $this->assertTrue((bool) $user->has_avatar_3x);
    }

    #[Test]
    public function account_linked_from_its_settings_gets_the_avatar(): void
    {
        $this->mockGitlab(['id' => 999, 'username' => 'someone', 'email' => 'someone@machine.local', 'confirmed_at' => null]);
        $this->fakeDownloads();

        $response = $this->oauthLinkCallback('gitlab', 4, 4);

        $this->assertEquals(302, $response->getStatusCode(), (string) $response->getBody());
        $this->assertSame(4, LoginProvider::where('provider', 'gitlab')->where('identifier', '999')->value('user_id'));
        $this->assertSame([self::AVATAR], $this->downloads);
        $this->assertNotNull($this->user(4)->getRawOriginal('avatar_url'));
    }

    #[Test]
    public function provider_without_an_email_still_gives_the_avatar_when_linked_from_settings(): void
    {
        $this->mockGitlab(['id' => 999, 'username' => 'someone', 'email' => null, 'confirmed_at' => null]);
        $this->fakeDownloads();

        $this->assertEquals(302, $this->oauthLinkCallback('gitlab', 4, 4)->getStatusCode());

        $this->assertSame([self::AVATAR], $this->downloads);
        $this->assertNotNull($this->user(4)->getRawOriginal('avatar_url'));
    }

    #[Test]
    public function avatar_uploaded_during_the_download_is_kept(): void
    {
        $this->mockGitlab(['id' => 999, 'username' => 'plain', 'email' => 'plain@machine.local', 'confirmed_at' => '2021-01-01T00:00:00Z']);
        $this->fakeDownloads(during: fn () => User::query()->whereKey(4)->update(['avatar_url' => 'uploaded.webp']));

        $this->oauthLogin('gitlab');

        $this->assertSame([self::AVATAR], $this->downloads);
        $this->assertSame('uploaded.webp', $this->user(4)->getRawOriginal('avatar_url'));
    }

    #[Test]
    public function discord_placeholder_is_not_given_to_an_account_linked_by_email(): void
    {
        $this->mockDiscord(['id' => '999', 'username' => 'plain', 'email' => 'plain@machine.local', 'verified' => true]);
        $this->fakeDownloads();

        $this->assertStringContainsString('_flarum_linked=discord', $this->oauthLogin('discord'));

        $this->assertSame([], $this->downloads, 'Discord has no avatar for this user, only its placeholder');
        $this->assertNull($this->user(4)->getRawOriginal('avatar_url'));
    }

    #[Test]
    public function discord_avatar_is_given_to_an_account_linked_by_email(): void
    {
        $this->mockDiscord(['id' => '999', 'username' => 'plain', 'email' => 'plain@machine.local', 'verified' => true, 'avatar' => 'abc']);
        $this->fakeDownloads();

        $this->oauthLogin('discord');

        $this->assertSame(['https://cdn.discordapp.com/avatars/999/abc.png'], $this->downloads);
    }

    #[Test]
    public function account_with_an_avatar_keeps_it(): void
    {
        $this->mockGitlab(['id' => 999, 'username' => 'pictured', 'email' => 'pictured@machine.local', 'confirmed_at' => '2021-01-01T00:00:00Z']);
        $this->fakeDownloads();

        $this->assertStringContainsString('_flarum_linked=gitlab', $this->oauthLogin('gitlab'));

        $this->assertSame([], $this->downloads);
        $this->assertSame('mine.webp', $this->user(5)->getRawOriginal('avatar_url'));
    }

    #[Test]
    public function logging_in_with_an_existing_link_does_not_fetch_the_avatar(): void
    {
        $this->prepareDatabase([LoginProvider::class => [['id' => 1, 'user_id' => 4, 'provider' => 'gitlab', 'identifier' => '999', 'created_at' => '2021-01-01 00:00:00']]]);
        $this->mockGitlab(['id' => 999, 'username' => 'plain', 'email' => 'plain@machine.local', 'confirmed_at' => '2021-01-01T00:00:00Z']);
        $this->fakeDownloads();

        $this->oauthLogin('gitlab');

        $this->assertSame([], $this->downloads, 'Only a new link applies it, so a removed avatar is not brought back');
        $this->assertNull($this->user(4)->getRawOriginal('avatar_url'));
    }

    #[Test]
    public function disabled_provider_avatars_are_not_applied(): void
    {
        $this->setting('fof-oauth.disable_avatars', 1);
        $this->mockGitlab(['id' => 999, 'username' => 'plain', 'email' => 'plain@machine.local', 'confirmed_at' => '2021-01-01T00:00:00Z']);
        $this->fakeDownloads();

        $this->oauthLogin('gitlab');

        $this->assertSame([], $this->downloads);
        $this->assertNull($this->user(4)->getRawOriginal('avatar_url'));
    }

    #[Test]
    public function failed_download_still_links_and_logs_in(): void
    {
        $this->mockGitlab(['id' => 999, 'username' => 'plain', 'email' => 'plain@machine.local', 'confirmed_at' => '2021-01-01T00:00:00Z']);
        $this->fakeDownloads(fails: true);

        $this->assertStringContainsString('_flarum_linked=gitlab', $this->oauthLogin('gitlab'));

        $this->assertSame([self::AVATAR], $this->downloads);
        $this->assertNull($this->user(4)->getRawOriginal('avatar_url'));
        $this->assertSame(4, LoginProvider::where('provider', 'gitlab')->where('identifier', '999')->value('user_id'));
    }

    #[Test]
    public function sign_up_is_left_to_core(): void
    {
        $this->mockGitlab(['id' => 999, 'username' => 'newcomer', 'email' => 'newcomer@machine.local', 'confirmed_at' => '2021-01-01T00:00:00Z']);
        $this->fakeDownloads();

        $this->assertStringContainsString('_flarum_auth', $this->oauthLogin('gitlab'));

        $this->assertSame([], $this->downloads);
    }
}
