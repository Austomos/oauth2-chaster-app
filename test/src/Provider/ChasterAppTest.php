<?php

/** @noinspection PhpIllegalPsrClassPathInspection */

namespace Austomos\OAuth2\Client\Test\Provider;

use Austomos\OAuth2\Client\Provider\ChasterApp;
use Austomos\OAuth2\Client\Provider\ChasterAppResourceOwner;
use Exception;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
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
    public function testChasterAppUrls(): void
    {
        /** @noinspection PhpUndefinedFieldInspection */
        $this->provider->oAuthBaseUrl = 'https://sso.chaster.app/auth/realms/app/protocol/openid-connect';

        $response = m::mock('Psr\Http\Message\ResponseInterface');
        $response->expects('getBody')
            ->times(1)
            ->andReturns(
                http_build_query([
                    'access_token' => 'mock_access_token',
                    'expires_in' => 300,
                    'refresh_expires_in' => 1800,
                    'refresh_token' => 'mock_refresh_token',
                    'token_type' => 'bearer',
                    'not-before-policy' => 0,
                    'scope' => 'profile'
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

    /**
     * @throws \League\OAuth2\Client\Provider\Exception\IdentityProviderException
     * @throws \JsonException
     */
    public function testCheckResponseThrowIdentityProviderException(): void
    {
        try {
            $status = random_int(400, 600);
        } catch (Exception $e) {
            $status = 500;
        }
        $postResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $postResponse->allows('getBody')
            ->andReturns(
                json_encode([
                    'message' => 'Validation Failed',
                    'errors' => [
                        ['resource' => 'Issue', 'field' => 'title', 'code' => 'missing_field'],
                    ],
                ], JSON_THROW_ON_ERROR)
            );
        $postResponse->allows('getHeader')
            ->andReturns(['content-type' => 'json']);
        $postResponse->allows('getStatusCode')
            ->andReturns($status);

        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->expects('send')
            ->times(1)
            ->andReturns($postResponse);
        /** @noinspection PhpParamsInspection */
        $this->provider->setHttpClient($client);

        $this->expectException(IdentityProviderException::class);

        $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
    }

    /**
     * @throws \League\OAuth2\Client\Provider\Exception\IdentityProviderException
     */
    public function testGetAccessToken(): void
    {
        $response = m::mock('Psr\Http\Message\ResponseInterface');
        $response->allows('getBody')
            ->andReturns('{"access_token":"mock_access_token", "scope":"profile", "token_type":"bearer"}');
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
     * @throws \JsonException
     * @throws \League\OAuth2\Client\Provider\Exception\IdentityProviderException
     */
    public function testProfileData(): void
    {
        try {
            $userId = random_int(1000, 9999);
        } catch (Exception $e) {
            $userId = 999;
        }
        $username = uniqid('', true);
        $email = uniqid('', true);
        $mockString = 'mock_string_value';
        $mockInt = 123;
        $mockArray = ['a' => 'A', 'b' => 'B', 'c' => 'C'];

        $postResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $postResponse->allows('getBody')
            ->andReturns(
                http_build_query([
                    'access_token' => 'mock_access_token',
                    'expires_in' => 300,
                    'refresh_expires_in' => 1800,
                    'refresh_token' => 'mock_refresh_token',
                    'token_type' => 'bearer',
                    'not-before-policy' => 0,
                    'scope' => 'profile'
                ])
            );
        $postResponse->allows('getHeader')
            ->andReturns(['content-type' => 'application/x-www-form-urlencoded']);
        $postResponse->allows('getStatusCode')
            ->andReturns(200);

        $userResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $userResponse->allows('getBody')
            ->andReturns(
                json_encode([
                    '_id' => $userId,
                    'username' => $username,
                    'email' => $email,
                    'mock_string' => $mockString,
                    'mock_int' => $mockInt,
                    'mock_array' => $mockArray,
                ], JSON_THROW_ON_ERROR)
            );
        $userResponse->allows('getHeader')
            ->andReturns(['content-type' => 'json']);
        $userResponse->allows('getStatusCode')
            ->andReturns(200);

        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->expects('send')
            ->times(2)
            ->andReturns($postResponse, $userResponse);
        /** @noinspection PhpParamsInspection */
        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
        $profile = $this->provider->getResourceOwner($token);

        $this->assertInstanceOf(ChasterAppResourceOwner::class, $profile);
        $this->assertEquals($userId, $profile->getId());
        $this->assertEquals($userId, $profile->toArray()['_id']);
        $this->assertEquals($userId, $profile->_id);

        $this->assertEquals($username, $profile->getUsername());
        $this->assertEquals($username, $profile->toArray()['username']);
        $this->assertEquals($username, $profile->username);

        $this->assertEquals($email, $profile->getEmail());
        $this->assertEquals($email, $profile->toArray()['email']);
        $this->assertEquals($email, $profile->email);

        $this->assertEquals($mockString, $profile->toArray()['mock_string']);
        $this->assertEquals($mockString, $profile->mock_string);
        $this->assertEquals($mockInt, $profile->toArray()['mock_int']);
        $this->assertEquals($mockInt, $profile->mock_int);
        $this->assertEquals($mockArray, $profile->toArray()['mock_array']);
        $this->assertEquals($mockArray, $profile->mock_array);
        $this->assertNull($profile->mock_null);
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
