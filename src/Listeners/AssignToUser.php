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
use GuzzleHttp\Client;
use Illuminate\Support\Arr;
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

        // ===== Set Avatar =====
        $avatarUrl = Arr::get($event->payload, 'avatarUrl');

        // Use our own custom fetching method instead of Flarum v1's (relies on allow_url_fopen being enabled).
        // TODO: Remove this for Flarum v2, which uses below Guzzle-based method and upgrades to Intervention Image v3.
        if ($avatarUrl) {
            $urlContents = $this->retrieveAvatarFromUrl($avatarUrl);

            if ($urlContents !== null) {
                $image = $this->imageManager->make($urlContents);

                $this->avatarUploader->upload($user, $image);
            }
        }
    }

    /**
     * Copied from Flarum 2.x, MIT licensed.
     * https://github.com/flarum/framework/blob/a46ce07255219093fb6f77e16ea7c7108a5f61aa/framework/core/src/Api/Resource/UserResource.php#L432-L447.
     */
    private function retrieveAvatarFromUrl(string $url): ?string
    {
        $client = new Client();

        try {
            $response = $client->get($url);
        } catch (\Exception $ignored) {
            return null;
        }

        if ($response->getStatusCode() !== 200) {
            return null;
        }

        return $response->getBody()->getContents();
    }
}
