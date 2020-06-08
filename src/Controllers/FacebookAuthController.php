<?php


namespace FoF\OAuth\Controllers;


use Flarum\Forum\Auth\Registration;
use FoF\OAuth\Controller;
use FoF\OAuth\Errors\AuthenticationException;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Facebook;
use League\OAuth2\Client\Provider\FacebookUser;

class FacebookAuthController extends Controller
{

    protected function getProvider(string $redirectUri): AbstractProvider
    {
        return new Facebook([
            'clientId' => $this->getSetting('app_id'),
            'clientSecret' => $this->getSetting('app_secret'),
            'redirectUri' => $redirectUri,
            'graphApiVersion' => 'v3.0',
        ]);
    }

    protected function getProviderName(): string
    {
        return 'facebook';
    }

    protected function getAuthorizationUrlOptions(): array
    {
        return [];
    }

    /**
     * @param Registration $registration
     * @param FacebookUser $user
     * @param string $token
     */
    protected function setSuggestions(Registration $registration, $user, string $token)
    {
        $this->verifyEmail($email = $user->getEmail());

        $registration
            ->provideTrustedEmail($email)
            ->provideAvatar($user->getPictureUrl() ?: '')
            ->suggestUsername($user->getName() ?: '')
            ->setPayload($user->toArray());
    }
}
