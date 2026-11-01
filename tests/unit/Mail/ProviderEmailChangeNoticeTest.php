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
use Flarum\Testing\unit\TestCase;
use Flarum\User\DisplayName\UsernameDriver;
use Flarum\User\User;
use FoF\OAuth\Mail\ProviderEmailChangeNotice;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProviderEmailChangeNoticeTest extends TestCase
{
    private const string SUBJECT = 'fof-oauth.email.provider_email_change_notice.subject';
    private const string BODY = 'fof-oauth.email.provider_email_change_notice.body';

    protected function setUp(): void
    {
        parent::setUp();

        User::setDisplayNameDriver(new UsernameDriver());
        // Registered by core's UserServiceProvider; unregistered preferences are discarded.
        User::registerPreference('locale');
    }

    /**
     * Behaves like Flarum's translator: looks the key up in the requested locale's catalogue, returns the key
     * unchanged when missing, and substitutes `{name}` placeholders.
     *
     * @param array<string, array<string, string>> $catalogues
     */
    private function translator(array $catalogues): TranslatorInterface
    {
        return new class($catalogues) implements TranslatorInterface {
            public function __construct(private array $catalogues)
            {
            }

            public function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
            {
                $replace = [];
                foreach ($parameters as $key => $value) {
                    $replace['{'.trim($key, '{}').'}'] = $value;
                }

                return strtr($this->catalogues[$locale][$id] ?? $id, $replace);
            }

            public function getLocale(): string
            {
                return 'en';
            }
        };
    }

    private function notice(array $catalogues): ProviderEmailChangeNotice
    {
        $settings = $this->createStub(SettingsRepositoryInterface::class);
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

    #[Test]
    public function notice_is_translated_in_the_users_preferred_locale(): void
    {
        $message = $this->notice([
            'de' => [self::SUBJECT => 'Betreff', self::BODY => 'Hallo {username}'],
            'fr' => [self::SUBJECT => 'Objet', self::BODY => 'Bonjour {username}'],
        ])->compose($this->user('de'), 'gitlab', 'new@example.com');

        $this->assertSame('Betreff', $message['subject']);
        $this->assertSame('Hallo Alice', $message['body']);
    }

    #[Test]
    public function notice_falls_back_to_the_forum_default_locale(): void
    {
        $message = $this->notice([
            'de' => [self::SUBJECT => 'Betreff', self::BODY => 'Hallo {username}'],
            'fr' => [self::SUBJECT => 'Objet', self::BODY => 'Bonjour {username}'],
        ])->compose($this->user(null), 'gitlab', 'new@example.com');

        $this->assertSame('Objet', $message['subject']);
        $this->assertSame('Bonjour Alice', $message['body']);
    }

    #[Test]
    public function body_includes_user_forum_provider_and_new_address(): void
    {
        $message = $this->notice([
            'de' => [
                self::BODY                         => '{username}|{forum}|{provider}|{new_email}',
                'fof-oauth.forum.providers.gitlab' => 'GitLab (de)',
            ],
        ])->compose($this->user('de'), 'gitlab', 'new@example.com');

        $this->assertSame('Alice|My Forum|GitLab (de)|new@example.com', $message['body']);
    }

    #[Test]
    public function provider_name_falls_back_to_its_key_without_translation(): void
    {
        $message = $this->notice(['de' => [self::BODY => '{provider}']])->compose($this->user('de'), 'thirdparty', 'new@example.com');

        $this->assertSame('thirdparty', $message['body']);
    }

    #[Test]
    public function notice_is_addressed_to_the_current_email_with_display_name_and_forum_title(): void
    {
        $message = $this->notice([])->compose($this->user('de'), 'gitlab', 'new@example.com');

        $this->assertSame('alice@example.com', $message['to']);
        $this->assertSame('Alice', $message['displayName']);
        $this->assertSame('My Forum', $message['forumTitle']);
    }
}
