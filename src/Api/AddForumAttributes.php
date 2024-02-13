<?php

/*
 * This file is part of fof/oauth.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\OAuth\Api;

use Flarum\Api\Serializer\ForumSerializer;

class AddForumAttributes
{
    public function __invoke(ForumSerializer $serializer, $model, array $attributes): array
    {
        if ($serializer->getActor()->isGuest()) {
            $attributes['fof-oauth'] = resolve('fof-oauth.providers.forum');
        } else {
            $attributes['fofOauthModerate'] = $serializer->getActor()->can('moderateUserProviders');
        }

        return $attributes;
    }
}
