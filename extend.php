<?php

/*
 * This file is part of fof/oauth.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\OAuth;

use Flarum\Api\Serializer\ForumSerializer;
use Flarum\Extend;
use Flarum\Frontend\Document;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/resources/less/forum.less'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js')
        ->css(__DIR__.'/resources/less/admin.less')
        ->content(function (Document $document) {
            $document->payload['fof-oauth'] = resolve('fof-oauth.providers.admin');
        }),

    new Extend\Locales(__DIR__.'/resources/locale'),

    (new Extend\Middleware('forum'))
        ->add(Middleware\ErrorHandler::class),

    (new Extend\Routes('forum'))
        ->get('/auth/twitter', 'auth.twitter', Controllers\TwitterAuthController::class),

    (new Extend\Routes('api'))
        ->get('/linked-accounts', 'users.provider.list', Api\Controllers\ListProvidersController::class)
        ->delete('/linked-accounts/{id}', 'users.provider.delete', Api\Controllers\DeleteProviderLinkController::class),

    (new Extend\ServiceProvider())
        ->register(OAuthServiceProvider::class),

    (new Extend\ApiSerializer(ForumSerializer::class))
        ->attributes(function (ForumSerializer $serializer) {
            $attributes = [];
            if ($serializer->getActor()->isGuest()) {
                $attributes['fof-oauth'] = resolve('fof-oauth.providers.forum');
            }

            return $attributes;
        }),

    (new Extend\Settings())
        ->default('fof-oauth.only_icons', false)
        ->serializeToForum('fof-oauth.only_icons', 'fof-oauth.only_icons', 'boolVal'),
];
