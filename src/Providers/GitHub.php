<?php


namespace FoF\OAuth\Providers;


use Flarum\Forum\Auth\Registration;
use FoF\OAuth\Controllers;
use FoF\OAuth\Provider;
use Illuminate\Support\Arr;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Github as GitHubProvider;

class GitHub extends Provider
{
    /**
     * @var GitHubProvider
     */
    protected $provider;

    public function name(): string
    {
        return 'github';
    }

    public function link(): string
    {
        return 'https://github.com/settings/developers';
    }

    public function package(): string
    {
        return 'league/oauth2-github';
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
        return class_exists(GitHubProvider::class);
    }



    public function provider(string $redirectUri): AbstractProvider
    {
        return $this->provider = new GitHubProvider([
            'clientId'     => $this->getSetting('client_id'),
            'clientSecret' => $this->getSetting('client_secret'),
            'redirectUri'  => $redirectUri,
        ]);
    }

    public function options(): array
    {
        return ['scope' => ['user:email']];
    }

    public function suggestions(Registration $registration, $user, string $token)
    {
        $this->verifyEmail($email = $user->getEmail() ?: $this->getEmailFromApi($token));

        $registration
            ->provideTrustedEmail($email)
            ->suggestUsername($user->getNickname() ?: '')
            ->provideAvatar(Arr::get($user->toArray(), 'avatar_url', ''))
            ->setPayload($user->toArray());
    }

    private function getEmailFromApi(string $token)
    {
        $url = $this->provider->apiDomain.'/user/emails';

        $response = $this->provider->getResponse(
            $this->provider->getAuthenticatedRequest('GET', $url, $token)
        );

        $emails = json_decode($response->getBody(), true);

        foreach ($emails as $email) {
            if ($email['primary'] && $email['verified']) {
                return $email['email'];
            }
        }
    }
}
