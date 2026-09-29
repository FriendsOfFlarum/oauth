<?php

/*
 * This file is part of fof/oauth.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\OAuth\Mail;

use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The notice sent to a user's current email address when their OAuth provider's email triggers an email change.
 */
class ProviderEmailChangeNotice
{
    public function __construct(
        protected TranslatorInterface $translator,
        protected SettingsRepositoryInterface $settings
    ) {
    }

    /**
     * @return array{to: string, displayName: string, forumTitle: string, subject: string, body: string}
     */
    public function compose(User $user, string $providerName, string $newEmail): array
    {
        // Same rule as core's NotificationMailer, but passed per call rather than changing the shared translator's
        // locale.
        $locale = $user->getPreference('locale') ?? $this->settings->get('default_locale');
        $forumTitle = (string) $this->settings->get('forum_title');

        return [
            'to'          => $user->email,
            'displayName' => $user->display_name,
            'forumTitle'  => $forumTitle,
            'subject'     => $this->translator->trans('fof-oauth.email.provider_email_change_notice.subject', [], null, $locale),
            'body'        => $this->translator->trans('fof-oauth.email.provider_email_change_notice.body', [
                'username'  => $user->display_name,
                'forum'     => $forumTitle,
                'provider'  => $this->providerDisplayName($providerName, $locale),
                'new_email' => $newEmail,
            ], null, $locale),
        ];
    }

    protected function providerDisplayName(string $providerName, ?string $locale): string
    {
        $key = "fof-oauth.forum.providers.$providerName";
        $name = $this->translator->trans($key, [], null, $locale);

        return $name === $key ? $providerName : $name;
    }
}
