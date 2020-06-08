<?php


namespace FoF\OAuth\Providers;


use FoF\OAuth\Controllers;
use FoF\OAuth\Provider;

class Facebook extends Provider
{
    protected function name(): string
    {
        return 'facebook';
    }

    protected function link(): string
    {
        return 'https://developers.facebook.com/apps/';
    }

    protected function package(): string
    {
        return 'league/oauth2-facebook';
    }

    protected function controller(): string
    {
        return Controllers\FacebookAuthController::class;
    }

    protected function fields(): array
    {
        return [
            'app_id' => 'required',
            'app_secret' => 'required',
        ];
    }

    protected function available(): bool
    {
        return class_exists(\League\OAuth2\Client\Provider\Facebook::class);
    }
}
