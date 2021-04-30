<?php

/*
 * This file is part of fof/oauth.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        /**
         * @var $settings SettingsRepositoryInterface
         */
        $settings = resolve(SettingsRepositoryInterface::class);
        $connection = $schema->getConnection();
        $rows = $connection->table('settings')
            ->where('key', 'LIKE', 'flarum-auth-%')
            ->orWhere('key', 'LIKE', 'fof-oauth-%')
            ->get();

        foreach ($rows as $item) {
            $key = preg_replace('/(?:flarum|fof)-auth-(\w+?)\.(\w+)/', 'fof-oauth.$1.$2', $item->key);

            $settings->set($key, $item->value);
            $settings->delete($item->key);
        }
    },
    'down' => function (Builder $schema) {
        $schema->getConnection()->table('settings')
            ->where('key', 'LIKE', 'fof-oauth.%')
            ->delete();
    },
];
