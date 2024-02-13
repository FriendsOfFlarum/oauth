<?php

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
