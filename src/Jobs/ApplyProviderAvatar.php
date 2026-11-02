<?php

/*
 * This file is part of fof/oauth.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\OAuth\Jobs;

use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\AvatarUploader;
use Flarum\User\User;
use FoF\OAuth\Avatar\AvatarDownloader;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Gives an account that was just linked to a provider the provider's avatar, if the account has none.
 */
class ApplyProviderAvatar implements ShouldQueue
{
    use Queueable;

    /**
     * The avatar is optional, so a failed attempt is not retried.
     */
    public int $tries = 1;

    public function __construct(
        public int $userId,
        public string $url,
    ) {
    }

    public function handle(AvatarDownloader $downloader, AvatarUploader $uploader, SettingsRepositoryInterface $settings): void
    {
        // Checked again here: things may have changed since the job was queued.
        if ((bool) $settings->get('fof-oauth.disable_avatars') || !$this->hasNoAvatar(User::find($this->userId))) {
            return;
        }

        $image = $downloader->download($this->url);

        if ($image === null) {
            return;
        }

        // The download can take seconds: lock the row and check once more, so an avatar the member uploaded
        // meanwhile, or one another job applied, is not overwritten.
        User::query()->getConnection()->transaction(function () use ($uploader, $image) {
            $user = User::query()->lockForUpdate()->find($this->userId);

            if (!$this->hasNoAvatar($user)) {
                return;
            }

            $uploader->upload($user, $image);
            $user->save();
        });
    }

    protected function hasNoAvatar(?User $user): bool
    {
        return $user !== null && $user->getRawOriginal('avatar_url') === null;
    }
}
