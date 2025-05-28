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

use Flarum\Api\Context;
use Flarum\Api\Schema;

class AddForumAttributes
{
    public function __invoke(): array
    {
        return [
            // This attribute is used to display the OAuth providers on the login page
            Schema\Str::make('fof-oauth')
                ->visible(fn ($model, Context $context) => $context->getActor()->isGuest())
                ->get(fn () => resolve('fof-oauth.providers.forum')),

            // This attribute is used to check if the user can moderate OAuth providers
            Schema\Boolean::make('fofOauthModerate')
                ->hidden(fn ($model, Context $context) => $context->getActor()->isGuest())
                ->get(fn ($model, Context $context) => $context->getActor()->can('moderateUserProviders')),
        ];
    }
}
