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
use FoF\OAuth\Events\LinkingToProvider;
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
     * A member signs up with a provider: they join its group once their account is saved.
     */
    public function handle(RegisteringFromProvider $event): void
    {
        $groupId = $this->groupId($event->provider);

        if ($groupId !== null) {
            $event->user->afterSave(function (User $user) use ($groupId) {
                // Another extension may add the same group during registration, e.g. fof/doorman's doorkey group.
                $user->groups()->syncWithoutDetaching([$groupId]);
            });
        }
    }

    /**
     * An existing member links a provider: they join its group, as they would have by signing up with it.
     * Unlinking leaves the group alone, because an admin may have given it by hand.
     */
    public function handleLinking(LinkingToProvider $event): void
    {
        $groupId = $this->groupId($event->providerName);

        if ($groupId !== null) {
            $event->actor->groups()->syncWithoutDetaching([$groupId]);
        }
    }

    protected function groupId(string $provider): ?int
    {
        $groupId = $this->settings->get("fof-oauth.{$provider}.group");

        return $groupId && is_numeric($groupId) ? (int) $groupId : null;
    }
}
