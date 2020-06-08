<?php


namespace FoF\OAuth;


use Exception;
use Flarum\Http\Exception\RouteNotFoundException;
use FoF\OAuth\Errors\AuthenticationException;
use FoF\Extend\Controllers\AbstractOAuthController;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

abstract class Controller extends AbstractOAuthController
{
    protected function getRouteName(): string
    {
        return 'auth.' . $this->getProviderName();
    }

    protected function getIdentifier($user): string
    {
        return $user->getId();
    }

    protected function getSetting($key): string
    {
        return $this->settings->get("fof-oauth.{$this->getProviderName()}.{$key}");
    }

    protected function verifyEmail($email)
    {
        if (!$email || empty($email)) {
            throw new AuthenticationException('invalid_email');
        }
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     * @throws Exception
     *
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (!(bool) (int) $this->settings->get('fof-oauth.'.$this->getProviderName())) {
            throw new RouteNotFoundException();
        }

        try {
            return parent::handle($request);
        } catch (Exception $e) {
            if ($e->getMessage() === 'Invalid state' || $e instanceof IdentityProviderException) {
                throw new AuthenticationException($e->getMessage());
            }

            throw $e;
        }
    }
}
