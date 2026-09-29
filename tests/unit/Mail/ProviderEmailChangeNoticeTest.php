<?php

/*
 * This file is part of fof/oauth.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\OAuth\Tests\unit\Mail;

use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\DisplayName\UsernameDriver;
use Flarum\User\User;
use FoF\OAuth\Mail\ProviderEmailChangeNotice;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProviderEmailChangeNoticeTest extends TestCase
{
    private const SUBJECT = 'fof-oauth.email.provider_email_change_notice.subject';
    private const BODY = 'fof-oauth.email.provider_email_change_notice.body';

    protected function setUp(): void
    {
        parent::setUp();

        User::setDisplayNameDriver(new UsernameDriver());
        // Registered by core's UserServiceProvider; unregistered preferences are discarded.
        User::registerPreference('locale');
    }

    /**
     * Behaves like Symfony's translator: looks the key up in the requested locale's catalogue, returns the key
     * unchanged when missing, and substitutes parameters.
     *
     * @param array<string, array<string, string>> $catalogues
     */
    private function translator(array $catalogues): TranslatorInterface
    {
        return new class($catalogues) implements TranslatorInterface {
            private $catalogues;

            public function __construct(array $catalogues)
            {
                $this->catalogues = $catalogues;
            }

            public function trans($id, array $parameters = [], $domain = null, $locale = null): string
            {
                return strtr($this->catalogues[$locale][$id] ?? $id, $parameters);
            }

            public function getLocale(): string
            {
                return 'en';
            }
        };
    }

    private function notice(array $catalogues): ProviderEmailChangeNotice
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('get')->willReturnMap([
            ['forum_title', null, 'My Forum'],
            ['default_locale', null, 'fr'],
        ]);

        return new ProviderEmailChangeNotice($this->translator($catalogues), $settings);
    }

    private function user(?string $locale): User
    {
        $user = new User();
        $user->username = 'Alice';
        $user->email = 'alice@example.com';
        $user->preferences = $locale === null ? [] : ['locale' => $locale];

        return $user;
    }

    public function test_notice_is_translated_in_the_users_preferred_locale(): void
    {
        $message = $this->notice([
            'de' => [self::SUBJECT => 'Betreff', self::BODY => 'Hallo {username}'],
            'fr' => [self::SUBJECT => 'Objet', self::BODY => 'Bonjour {username}'],
        ])->compose($this->user('de'), 'gitlab', 'new@example.com');

        $this->assertSame('Betreff', $message['subject']);
        $this->assertSame('Hallo Alice', $message['body']);
    }

    public function test_notice_falls_back_to_the_forum_default_locale(): void
    {
        $message = $this->notice([
            'de' => [self::SUBJECT => 'Betreff', self::BODY => 'Hallo {username}'],
            'fr' => [self::SUBJECT => 'Objet', self::BODY => 'Bonjour {username}'],
        ])->compose($this->user(null), 'gitlab', 'new@example.com');

        $this->assertSame('Objet', $message['subject']);
        $this->assertSame('Bonjour Alice', $message['body']);
    }

    public function test_body_includes_user_forum_provider_and_new_address(): void
    {
        $message = $this->notice([
            'de' => [
                self::BODY                         => '{username}|{forum}|{provider}|{new_email}',
                'fof-oauth.forum.providers.gitlab' => 'GitLab (de)',
            ],
        ])->compose($this->user('de'), 'gitlab', 'new@example.com');

        $this->assertSame('Alice|My Forum|GitLab (de)|new@example.com', $message['body']);
    }

    public function test_provider_name_falls_back_to_its_key_without_translation(): void
    {
        $message = $this->notice([
            'de' => [self::BODY => '{provider}'],
        ])->compose($this->user('de'), 'thirdparty', 'new@example.com');

        $this->assertSame('thirdparty', $message['body']);
    }

    public function test_notice_is_addressed_to_the_current_email(): void
    {
        $message = $this->notice([])->compose($this->user('de'), 'gitlab', 'new@example.com');

        $this->assertSame('alice@example.com', $message['to']);
    }
}
