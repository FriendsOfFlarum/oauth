<?php

/*
 * This file is part of fof/oauth.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\OAuth\Listeners;

use Flarum\Group\Group;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\AvatarUploader;
use Flarum\User\Event\RegisteringFromProvider;
use Flarum\User\User;
use Intervention\Image\ImageManager;

class AssignToUser
{
    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    protected ImageManager $imageManager;
    protected AvatarUploader $avatarUploader;

    /**
     * @param SettingsRepositoryInterface $settings
     */
    public function __construct(SettingsRepositoryInterface $settings, ImageManager $imageManager, AvatarUploader $avatarUploader)
    {
        $this->settings = $settings;
        $this->imageManager = $imageManager;
        $this->avatarUploader = $avatarUploader;
    }

    /**
     * @param RegisteringFromProvider $event
     */
    public function handle(RegisteringFromProvider $event)
    {
        $provider = $event->provider;
        $user = $event->user;

        // ===== Assign Group =====
        // Get the group ID for this provider
        $groupId = $this->settings->get("fof-oauth.{$provider}.group");

        // If a group is specified, assign it to the user
        if ($groupId && is_numeric($groupId)) {
            $user->afterSave(function (User $user) use ($groupId) {
                // Attach the group to the user
                $user->groups()->attach($groupId);
            });
        }
    }
}
