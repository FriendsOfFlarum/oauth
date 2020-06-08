<?php


namespace FoF\OAuth\Providers;


use FoF\OAuth\Controllers;
use FoF\OAuth\Provider;

class Discord extends Provider
{
    protected function name(): string
    {
        return 'discord';
    }

    protected function link(): string
    {
        return 'https://discordapp.com/developers/applications';
    }

    protected function package(): string
    {
        return 'wohali/oauth2-discord-new';
    }

    protected function controller(): string
    {
        return Controllers\DiscordAuthController::class;
    }

    protected function fields(): array
    {
        return [
            'client_id' => 'required',
            'client_secret' => 'required',
        ];
    }

    protected function available(): bool
    {
        return class_exists(\Wohali\OAuth2\Client\Provider\Discord::class);
    }
}
