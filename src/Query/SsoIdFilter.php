<?php

/*
 * This file is part of fof/oauth.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\OAuth\Query;

use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\Filter\FilterInterface;
use Flarum\Search\ValidateFilterTrait;

class SsoIdFilter implements FilterInterface
{
    use ValidateFilterTrait;

    public function getFilterKey(): string
    {
        return 'sso';
    }

    public function filter(mixed $state, mixed $value, bool $negate): void
    {
        /** @var DatabaseSearchState $state */
        if (!$state->getActor()->can('moderateUserProviders')) {
            return;
        }

        $sso = $this->asStringArray($value);

        $state->getQuery()
            ->whereHas('loginProviders', function ($query) use ($sso) {
                $query->whereIn('provider', $sso);
            }, $negate ? '=' : '>', 0);
    }
}
