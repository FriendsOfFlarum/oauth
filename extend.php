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

use Flarum\Api\Serializer\CurrentUserSerializer;
use Flarum\Api\Serializer\ForumSerializer;
use Flarum\Extend;
use Flarum\Frontend\Document;
use Flarum\User\Event\LoggedOut;
use Flarum\User\Event\RegisteringFromProvider;
use Flarum\User\Filter\UserFilterer;
use Flarum\User\Search\UserSearcher;
use FoF\Extend\Events\OAuthLoginSuccessful;

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
        ->add(Middleware\ErrorHandler::class)
        ->add(Middleware\BindRequest::class),

    (new Extend\Middleware('api'))
        ->add(Middleware\BindRequest::class),

    (new Extend\Routes('forum'))
        ->get('/auth/twitter', 'auth.twitter', Controllers\TwitterAuthController::class),

    (new Extend\Routes('api'))
        ->get('/users/{id}/linked-accounts', 'users.provider.list', Api\Controllers\ListProvidersController::class)
        ->get('/linked-accounts', 'user.provider.list', Api\Controllers\ListProvidersController::class)
        ->delete('/linked-accounts/{id}', 'users.provider.delete', Api\Controllers\DeleteProviderLinkController::class),

    (new Extend\ServiceProvider())
        ->register(OAuthServiceProvider::class),

    (new Extend\ApiSerializer(ForumSerializer::class))
        ->attributes(Api\AddForumAttributes::class),

    (new Extend\Settings())
        ->default('fof-oauth.only_icons', false)
        ->default('fof-oauth.update_email_from_provider', true)
        ->serializeToForum('fof-oauth.only_icons', 'fof-oauth.only_icons', 'boolVal')
        ->default('fof-oauth.popupWidth', 580)
        ->default('fof-oauth.popupHeight', 400)
        ->default('fof-oauth.fullscreenPopup', true)
        ->serializeToForum('fof-oauth.popupWidth', 'fof-oauth.popupWidth', 'intval')
        ->serializeToForum('fof-oauth.popupHeight', 'fof-oauth.popupHeight', 'intval')
        ->serializeToForum('fof-oauth.fullscreenPopup', 'fof-oauth.fullscreenPopup', 'boolVal')
        ->default('fof-oauth.log-oauth-errors', false),

    (new Extend\Event())
        ->listen(RegisteringFromProvider::class, Listeners\AssignGroupToUser::class)
        ->listen(OAuthLoginSuccessful::class, Listeners\UpdateEmailFromProvider::class)
        ->listen(LoggedOut::class, Listeners\HandleLogout::class)
        ->subscribe(Listeners\ClearOAuthCache::class),

    (new Extend\ApiSerializer(CurrentUserSerializer::class))
        ->attributes(Api\CurrentUserAttributes::class),

    (new Extend\Filter(UserFilterer::class))
        ->addFilter(Query\SsoIdFilterGambit::class),

    (new Extend\SimpleFlarumSearch(UserSearcher::class))
        ->addGambit(Query\SsoIdFilterGambit::class),

    (new Extend\Conditional())
        ->whenExtensionEnabled('flarum-gdpr', fn () => [
            (new Extend\ApiSerializer(ForumSerializer::class))
                ->attribute('passwordlessSignUp', function (ForumSerializer $serializer) {
                    return !$serializer->getActor()->isGuest() && $serializer->getActor()->loginProviders()->count() > 0;
                }),
        ]),
];
