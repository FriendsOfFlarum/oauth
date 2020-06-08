<?php


namespace FoF\OAuth\Controllers;


use Flarum\Forum\Auth\Registration;
use FoF\OAuth\Controller;
use League\OAuth2\Client\Provider\AbstractProvider;
use Wohali\OAuth2\Client\Provider\Discord;
use Wohali\OAuth2\Client\Provider\DiscordResourceOwner;

class DiscordAuthController extends Controller
{

    protected function getProvider(string $redirectUri): AbstractProvider
    {
        return new Discord([
            'clientId' => $this->getSetting('client_id'),
            'clientSecret' => $this->getSetting('client_secret'),
            'redirectUri' => $redirectUri,
        ]);
    }

    protected function getProviderName(): string
    {
        return 'discord';
    }

    protected function getAuthorizationUrlOptions(): array
    {
        return ['scope' => ['identify', 'email']];
    }

    /**
     * @param Registration $registration
     * @param DiscordResourceOwner $user
     * @param string $token
     */
    protected function setSuggestions(Registration $registration, $user, string $token)
    {
        $this->verifyEmail($email = $user->getEmail());

        $registration
            ->provideTrustedEmail($email)
            ->provideAvatar($this->getAvatar($user))
            ->suggestUsername($user->getUsername() ?: '')
            ->setPayload($user->toArray());
    }

    private function getAvatar(DiscordResourceOwner $user)
    {
        $hash = $user->getAvatarHash();

        return isset($hash) ? "https://cdn.discordapp.com/avatars/{$user->getId()}/{$user->getAvatarHash()}.png" : 'https://cdn.discordapp.com/embed/avatars/0.png';
    }
}
