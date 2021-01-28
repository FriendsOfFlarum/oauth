<?php

/*
 * This file is part of fof/oauth.
 *
 * Copyright (c) 2020 FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace FoF\OAuth;

use Flarum\Api\Serializer\ForumSerializer;
use Flarum\Extend;
use Flarum\Foundation\Application;
use Flarum\Frontend\Document;
use FoF\Components\Extend\AddFofComponents;
use FoF\Extend\Extend\ExtensionSettings;

return [
    new AddFofComponents(),

    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/resources/less/forum.less'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js')
        ->css(__DIR__.'/resources/less/admin.less')
        ->content(function (Document $document) {
            $document->payload['fof-oauth'] = app('fof-oauth.providers.admin');
        }),

    new Extend\Locales(__DIR__.'/resources/locale'),

    (new Extend\Middleware('forum'))
        ->add(Middleware\ErrorHandler::class),

    (new ExtensionSettings())
        ->addKey('fof-oauth.only_icons', false),

    (new Extend\Routes('forum'))
        ->get('/auth/twitter', 'auth.twitter', Controllers\TwitterAuthController::class)
        ->get(new OAuth2RoutePattern(), 'fof-oauth', Controllers\AuthController::class),

    function (Application $app) {
        $app->register(OAuthServiceProvider::class);
    },

    (new Extend\ApiSerializer(ForumSerializer::class))
        ->mutate(function (ForumSerializer $serializer) {
            $attributes = [];
            if ($serializer->getActor()->isGuest()) {
                $attributes['fof-oauth'] = app()->make('fof-oauth.providers.forum');
            }

            return $attributes;
        }),
];
