<?php

/*
 * This file is part of fof/oauth.
 *
 * Copyright (c) 2020 FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace FoF\OAuth\Providers;

use Flarum\Forum\Auth\Registration;
use FoF\OAuth\Provider;
use League\OAuth2\Client\Provider\AbstractProvider;
use NomisCZ\OAuth2\Client\Provider\WeChat as WeChatProvider;

class WeChat extends Provider
{
    public function name(): string
    {
        return 'wechat';
    }

    public function link(): string
    {
        return 'https://open.weixin.qq.com/';
    }

    public function icon(): string
    {
        return "fab fa-weixin";
    }

    public function fields(): array
    {
        return [
            'app_id'     => 'required',
            'app_secret' => 'required',
        ];
    }

    public function provider(string $redirectUri): AbstractProvider
    {
        return new WeChatProvider([
            'appid'         => $this->getSetting('app_id'),
            'secret'        => $this->getSetting('app_secret'),
            'redirectUri'   => $redirectUri
        ]);
    }

    public function suggestions(Registration $registration, $user, string $token)
    {
        $registration
            ->suggestUsername($user->getNickname())
            ->setPayload($user->toArray());

        if ($user->getHeadImgUrl()) {
            $registration->provideAvatar($user->getHeadImgUrl());
        }
    }
}
