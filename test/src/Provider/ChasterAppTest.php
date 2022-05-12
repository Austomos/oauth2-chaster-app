<?php

/** @noinspection PhpIllegalPsrClassPathInspection */

namespace Austomos\OAuth2\Client\Test\Provider;

use Austomos\OAuth2\Client\Provider\ChasterApp;
use League\OAuth2\Client\Tool\QueryBuilderTrait;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class ChasterAppTest extends TestCase
{
    use QueryBuilderTrait;

    protected ChasterApp $provider;

    public function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }

    public function testAuthorizationUrl(): void
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);
        parse_str($uri['query'], $query);

        $this->assertArrayHasKey('client_id', $query);
        $this->assertArrayHasKey('redirect_uri', $query);
        $this->assertArrayHasKey('state', $query);
        $this->assertArrayHasKey('scope', $query);
        $this->assertArrayHasKey('response_type', $query);
        $this->assertArrayHasKey('approval_prompt', $query);
        $this->assertNotNull($this->provider->getState());
    }

    /**
     * @throws \League\OAuth2\Client\Provider\Exception\IdentityProviderException
     */
    public function testGetAccessToken(): void
    {
        $response = m::mock('Psr\Http\Message\ResponseInterface');
        $response->allows('getBody')
            ->andReturns('{"access_token":"mock_access_token", "scope":"repo,gist", "token_type":"bearer"}');
        $response->allows('getHeader')
            ->andReturns(['content-type' => 'json']);
        $response->allows('getStatusCode')
            ->andReturns(200);

        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->expects('send')->times(1)->andReturns($response);
        /** @noinspection PhpParamsInspection */
        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);

        $this->assertEquals('mock_access_token', $token->getToken());
        $this->assertNull($token->getExpires());
        $this->assertNull($token->getRefreshToken());
        $this->assertNull($token->getResourceOwnerId());
    }

    public function testGetAuthorizationUrl(): void
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);

        $this->assertEquals('/auth/realms/app/protocol/openid-connect/auth', $uri['path']);
    }

    public function testGetBaseAccessTokenUrl(): void
    {
        $params = [];

        $url = $this->provider->getBaseAccessTokenUrl($params);
        $uri = parse_url($url);

        $this->assertEquals('/auth/realms/app/protocol/openid-connect/token', $uri['path']);
    }

    /**
     * @throws \League\OAuth2\Client\Provider\Exception\IdentityProviderException
     */
    public function testGithubEnterpriseDomainUrls(): void
    {
        /** @noinspection PhpUndefinedFieldInspection */
        $this->provider->oAuthBaseUrl = 'https://sso.chaster.app/auth/realms/app/protocol/openid-connect';

        $response = m::mock('Psr\Http\Message\ResponseInterface');
        $response->expects('getBody')
            ->times(1)
            ->andReturns(
                http_build_query([
                    'access_token' => 'mock_access_token',
                    'expires' => 3600,
                    'refresh_token' => 'mock_refresh_token',
                ])
            );
        $response->allows('getHeader')
            ->andReturns(['content-type' => 'application/x-www-form-urlencoded']);
        $response->allows('getStatusCode')
            ->andReturns(200);

        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->expects('send')->times(1)->andReturns($response);
        /** @noinspection PhpParamsInspection */
        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);

        $providerAuthorizationUrl = $this->provider->oAuthBaseUrl . '/auth';
        $providerAccessTokenUrl = $this->provider->oAuthBaseUrl . '/token';
        $providerResourceOwnerUrl = 'https://api.chaster.app/auth/profile';

        $this->assertEquals($providerAuthorizationUrl, $this->provider->getBaseAuthorizationUrl());
        $this->assertEquals($providerAccessTokenUrl, $this->provider->getBaseAccessTokenUrl([]));
        $this->assertEquals($providerResourceOwnerUrl, $this->provider->getResourceOwnerDetailsUrl($token));
    }

    public function testScopes(): void
    {
        $scopeSeparator = ' ';
        $options = ['scope' => [uniqid('', true), uniqid('', true)]];
        $query = ['scope' => implode($scopeSeparator, $options['scope'])];
        $url = $this->provider->getAuthorizationUrl($options);
        $encodedScope = $this->buildQueryString($query);

        $this->assertStringContainsString($encodedScope, $url);
    }

    protected function setUp(): void
    {
        $this->provider = new ChasterApp(
            [
                'clientId' => 'mock_client_id',
                'clientSecret' => 'mock_secret',
                'redirectUri' => 'none',
            ]
        );
    }
}
