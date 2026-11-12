<?php

/*
 * This file is part of fof/oauth.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\OAuth\Tests\fixtures\ThirdParty;

use Flarum\Http\UrlGenerator;
use Flarum\Settings\SettingsRepositoryInterface;

class UrlDependentProvider extends ThirdPartyProvider
{
    public function __construct(SettingsRepositoryInterface $settings, UrlGenerator $url)
    {
        parent::__construct($settings);
    }
}
