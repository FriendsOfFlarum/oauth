<?php

/*
 * This file is part of fof/oauth.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\OAuth\Providers\Custom\LinkedIn\Provider;

use FoF\OAuth\Providers\Custom\LinkedIn\Provider\Exception\LinkedInAccessDeniedException;
use FoF\OAuth\Providers\Custom\LinkedIn\Token\LinkedInAccessToken;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use League\OAuth2\Client\Grant\AbstractGrant;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;

class LinkedIn extends \League\OAuth2\Client\Provider\AbstractProvider
{
    use BearerAuthorizationTrait;

    /**
     * Default scopes.
     *
     * @var array
     */
    public $defaultScopes = ['openid', 'profile', 'email'];

    /**
     * Requested fields in scope, seeded with default values.
     *
     * @var array
     *
     * @see https://developer.linkedin.com/docs/fields/basic-profile
     */
    protected $fields = [
        'sub', 'name', 'given_name', 'family_name', 'picture', 'locale', 'email', 'email_verified',
    ];

    /**
     * Constructs an OAuth 2.0 service provider.
     *
     * @param array $options       An array of options to set on this provider.
     *                             Options include `clientId`, `clientSecret`, `redirectUri`, and `state`.
     *                             Individual providers may introduce more options, as needed.
     * @param array $collaborators An array of collaborators that may be used to
     *                             override this provider's default behavior. Collaborators include
     *                             `grantFactory`, `requestFactory`, and `httpClient`.
     *                             Individual providers may introduce more collaborators, as needed.
     */
    public function __construct(array $options = [], array $collaborators = [])
    {
        if (isset($options['fields']) && !is_array($options['fields'])) {
            throw new InvalidArgumentException('The fields option must be an array');
        }

        parent::__construct($options, $collaborators);
    }

    /**
     * Creates an access token from a response.
     *
     * The grant that was used to fetch the response can be used to provide
     * additional context.
     *
     * @param array         $response
     * @param AbstractGrant $grant
     *
     * @return LinkedInAccessToken
     */
    protected function createAccessToken(array $response, AbstractGrant $grant)
    {
        return new LinkedInAccessToken($response);
    }

    protected function getAuthorizationHeaders($token = null)
    {
        return [
            'Authorization' => "Bearer {$token}",
        ];
    }

    /**
     * Get the string used to separate scopes.
     *
     * @return string
     */
    protected function getScopeSeparator()
    {
        return ' ';
    }

    /**
     * Get authorization url to begin OAuth flow.
     *
     * @return string
     */
    public function getBaseAuthorizationUrl()
    {
        return 'https://www.linkedin.com/oauth/v2/authorization';
    }

    /**
     * Get access token url to retrieve token.
     *
     * @return string
     */
    public function getBaseAccessTokenUrl(array $params)
    {
        return 'https://www.linkedin.com/oauth/v2/accessToken';
    }

    /**
     * Get provider url to fetch user details.
     *
     * @param AccessToken $token
     *
     * @return string
     */
    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        return 'https://api.linkedin.com/v2/userinfo';
    }

    /**
     * Get the default scopes used by this provider.
     *
     * This should not be a complete list of all scopes, but the minimum
     * required for the provider user interface!
     *
     * @return array
     */
    protected function getDefaultScopes()
    {
        return $this->defaultScopes;
    }

    /**
     * Check a provider response for errors.
     *
     * @param ResponseInterface $response
     * @param array             $data     Parsed response data
     *
     * @throws IdentityProviderException
     *
     * @return void
     *
     * @see https://developer.linkedin.com/docs/guide/v2/error-handling
     */
    protected function checkResponse(ResponseInterface $response, $data)
    {
        $this->checkResponseUnauthorized($response, $data);

        if ($response->getStatusCode() >= 400) {
            throw new IdentityProviderException(
                isset($data['message']) ? $data['message'] : $response->getReasonPhrase(),
                isset($data['status']) ? $data['status'] : $response->getStatusCode(),
                $response
            );
        }
    }

    /**
     * Check a provider response for unauthorized errors.
     *
     * @param ResponseInterface $response
     * @param array             $data     Parsed response data
     *
     * @throws LinkedInAccessDeniedException
     *
     * @return void
     *
     * @see https://developer.linkedin.com/docs/guide/v2/error-handling
     */
    protected function checkResponseUnauthorized(ResponseInterface $response, $data)
    {
        if (isset($data['status']) && $data['status'] === 403) {
            throw new LinkedInAccessDeniedException(
                isset($data['message']) ? $data['message'] : $response->getReasonPhrase(),
                Arr::get($data, 'status', $response->getStatusCode()),
                $response
            );
        }
    }

    /**
     * Generate a user object from a successful user details request.
     *
     * @param array       $response
     * @param AccessToken $token
     *
     * @return LinkedInResourceOwner
     */
    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new LinkedInResourceOwner($response);
    }

    /**
     * Returns the requested fields in scope.
     *
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Updates the requested fields in scope.
     *
     * @param array $fields
     *
     * @return LinkedIn
     */
    public function withFields(array $fields)
    {
        $this->fields = $fields;

        return $this;
    }
}
