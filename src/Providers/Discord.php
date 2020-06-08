<?php


namespace FoF\OAuth\Providers;


use Flarum\Forum\Auth\Registration;
use FoF\OAuth\Controllers;
use FoF\OAuth\Provider;
use League\OAuth2\Client\Provider\AbstractProvider;
use Wohali\OAuth2\Client\Provider\Discord as DiscordProvider;

class Discord extends Provider
{
    public function name(): string
    {
        return 'discord';
    }

    public function link(): string
    {
        return 'https://discordapp.com/developers/applications';
    }

    public function package(): string
    {
        return 'wohali/oauth2-discord-new';
    }

    public function fields(): array
    {
        return [
            'client_id' => 'required',
            'client_secret' => 'required',
        ];
    }

    public function available(): bool
    {
        return class_exists(DiscordProvider::class);
    }



    public function provider(string $redirectUri): AbstractProvider
    {
        return new DiscordProvider([
            'clientId' => $this->getSetting('client_id'),
            'clientSecret' => $this->getSetting('client_secret'),
            'redirectUri' => $redirectUri,
        ]);
    }

    public function options(): array
    {
        return ['scope' => ['identify', 'email']];
    }

    public function suggestions(Registration $registration, $user, string $token)
    {
        $this->verifyEmail($email = $user->getEmail());

        $hash = $user->getAvatarHash();
        $file = $hash ? "{$user->getId()}/{$user->getAvatarHash()}.png" : '0.png';

        $registration
            ->provideTrustedEmail($email)
            ->provideAvatar("https://cdn.discordapp.com/avatars/$file")
            ->suggestUsername($user->getUsername() ?: '')
            ->setPayload($user->toArray());
    }
}
