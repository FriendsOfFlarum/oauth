<?php

namespace FoF\OAuth\Query;

use Flarum\Filter\FilterInterface;
use Flarum\Filter\FilterState;
use Flarum\Filter\ValidateFilterTrait;
use Flarum\Search\AbstractRegexGambit;
use Flarum\Search\SearchState;
use Illuminate\Database\Query\Builder;

class SsoIdFilterGambit extends AbstractRegexGambit implements FilterInterface
{
    use ValidateFilterTrait;

    public function apply(SearchState $search, $bit)
    {
        if (! $search->getActor()->hasPermission('fof-oauth.admin.permissions.moderate_user_providers')) {
            return false;
        }

        return parent::apply($search, $bit);
    }

    public function getGambitPattern()
    {
        return 'sso:(.+)';
    }

    protected function conditions(SearchState $search, array $matches, $negate)
    {
        $this->constrain($search->getQuery(), $matches[1], $negate);
    }

    public function getFilterKey(): string
    {
        return 'sso';
    }

    public function filter(FilterState $filterState, $filterValue, bool $negate)
    {
        if (! $filterState->getActor()->hasPermission('fof-oauth.admin.permissions.moderate_user_providers')) {
            return;
        }

        $this->constrain($filterState->getQuery(), $filterValue, $negate);
    }

    protected function constrain(Builder $query, $rawSso, bool $negate)
    {
        $sso = $this->asString($rawSso);

        $query->whereIn('id', function ($query) use ($sso) {
            $query->select('user_id')
                ->from('login_providers')
                ->where('identifier', $sso);
        });
    }
}
