<?php

namespace FoF\OAuth\Query;

use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\Filter\FilterInterface;
use Illuminate\Support\Arr;

class SsoIdFilter implements FilterInterface
{
    public function getFilterKey(): string
    {
        return 'sso';
    }

    public function filter($state, $value, bool $negate): void
    {
        /** @var DatabaseSearchState $state */

        if (!$state->getActor()->hasPermission('fof-oauth.admin.permissions.moderate_user_providers')) {
            return;
        }

        $sso = explode(',', trim($value, '"'));

        $state->getQuery()
            ->whereHas('loginProviders', function ($query) use ($sso) {
                $query->whereIn('provider', $sso);
            }, $negate ? '=' : '>', 0);
    }
}
