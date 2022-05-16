<?php

/** @noinspection PhpIllegalPsrClassPathInspection */

namespace Austomos\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\ArrayAccessorTrait;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;

/**
 *
 */
class ChasterApp extends AbstractProvider
{
    use ArrayAccessorTrait;
    use BearerAuthorizationTrait;

    /**
     * OAuth 2 Base URL used by Chaster API
     * @var string
     */
    protected string $baseAuthUrl = 'https://sso.chaster.app/auth/realms/app/protocol/openid-connect';

    /**
     * API Domain used for endpoints
     * @link https://api.chaster.app/api
     * @var string
     */
    protected string $apiDomain = 'https://api.chaster.app';

    /**
     * Returns the base URL for requesting an access token.
     *
     * @param array $params
     *
     * @return string
     */
    public function getBaseAccessTokenUrl(array $params): string
    {
        return $this->baseAuthUrl . '/token';
    }

    /**
     * Returns the base URL for authorizing a client.
     *
     * @return string
     */
    public function getBaseAuthorizationUrl(): string
    {
        return $this->baseAuthUrl . '/auth';
    }

    /**
     * Returns the URL for requesting the resource owner's details.
     *
     * By default, the owner's details is an auth request to /auth/profile endpoint
     *
     * @param AccessToken $token
     *
     * @return string
     */
    public function getResourceOwnerDetailsUrl(AccessToken $token): string
    {
        return $this->apiDomain . '/auth/profile';
    }

    /**
     * Check a provider response for errors.
     *
     * @param ResponseInterface $response
     * @param array $data Parsed response data
     *
     * @return void
     *
     * @throws IdentityProviderException
     */
    protected function checkResponse(ResponseInterface $response, $data): void
    {
        if ($response->getStatusCode() >= 400) {
            throw new IdentityProviderException(
                $data['message'] ?? $response->getReasonPhrase(),
                $response->getStatusCode(),
                $data
            );
        }
    }

    /**
     * Generate a user object from a successful user details request.
     *
     * @param array $response
     * @param AccessToken $token
     *
     * @return ChasterAppResourceOwner
     */
    protected function createResourceOwner(array $response, AccessToken $token): ChasterAppResourceOwner
    {
        return new ChasterAppResourceOwner($response);
    }

    /**
     * Get the default scopes used by API.
     *
     * This should only be the scopes that are required to request the details
     * of the resource owner, rather than all the available scopes.
     *
     * The default scope required for owner's details is only 'profile'
     *
     * @link https://docs.chaster.app/api-scopes
     *
     * @return array
     */
    protected function getDefaultScopes(): array
    {
        return ['profile'];
    }

    /**
     * Returns the string that should be used to separate scopes when building
     * the URL for requesting an access token.
     *
     * @return string Scope separator, defaults to ' '
     */
    protected function getScopeSeparator(): string
    {
        return ' ';
    }
}
