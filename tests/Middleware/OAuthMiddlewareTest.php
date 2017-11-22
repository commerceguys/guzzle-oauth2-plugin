<?php

namespace Sainsburys\Guzzle\Oauth2\Tests\Middleware;

use Sainsburys\Guzzle\Oauth2\AccessToken;
use Sainsburys\Guzzle\Oauth2\GrantType\ClientCredentials;
use Sainsburys\Guzzle\Oauth2\GrantType\RefreshToken;
use Sainsburys\Guzzle\Oauth2\Middleware\OAuthMiddleware;
use Sainsburys\Guzzle\Oauth2\Tests\MockOAuth2Server;
use Sainsburys\Guzzle\Oauth2\Tests\MockOAuthMiddleware;
use Sainsburys\Guzzle\Oauth2\Tests\TestBase;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;

class OAuthMiddlewareTest extends TestBase
{
    public function testMiddlewareOnBeforeWhenAccessTokenNotSetYet()
    {
        $client = $this->createClient([
            RequestOptions::AUTH => 'oauth2',
        ],
        [
            MockOAuth2Server::KEY_EXPECTED_QUERY_COUNT => 2,
        ]);

        $middleware = new OAuthMiddleware($client, new ClientCredentials($client, [
            'client_id' => 'test',
            'client_secret' => 'testSecret',
        ]));

        $handlerStack = $this->getHandlerStack();
        $handlerStack->push($middleware->onBefore());
        $handlerStack->push($middleware->onFailure(5));


        /** @var ResponseInterface */
        $response = $client->get('/api/collection');
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testMiddlewareOnBeforeUsesRefreshToken()
    {
        $credentials = [
            'client_id' => 'test',
            'client_secret' => 'testSecret',
        ];

        $client = $this->createClient(
            [
                RequestOptions::AUTH => 'oauth2',
            ],
            [
                MockOAuth2Server::KEY_EXPECTED_QUERY_COUNT => 3,
            ]
        );

        $accessTokenGrantType = new ClientCredentials(
            $client,
            $credentials
        );
        $refreshToken = new RefreshToken($client, $credentials);

        $middleware = new OAuthMiddleware(
            $client,
            $accessTokenGrantType,
            $refreshToken
        );
        $handlerStack = $this->getHandlerStack();
        $handlerStack->push($middleware->onBefore());
        $handlerStack->push($middleware->onFailure(5));

        // Initially, the access token should be expired. Before the first API
        // call, the middleware will use the refresh token to get a new access
        // token.
        $accessToken = new AccessToken('tokenOld', 'client_credentials', [
            'refresh_token' => 'refreshTokenOld',
            'expires' => 0
        ]);
        $middleware->setAccessToken($accessToken);

        $response = $client->get('/api/collection');

        // Now, the access token should be valid.
        $this->assertEquals('token', $middleware->getAccessToken()->getToken());
        $this->assertFalse($middleware->getAccessToken()->isExpired());
        $this->assertEquals(200, $response->getStatusCode());

        // Also, the refresh token should have changed.
        $newRefreshToken = $middleware->getRefreshToken();
        $this->assertEquals('refreshToken', $newRefreshToken->getToken());
    }

    public function testMiddlewareOnFailureUsesRefreshToken()
    {
        $credentials = [
            'client_id' => 'test',
            'client_secret' => 'testSecret',
        ];
        $client = $this->createClient(
            [
                RequestOptions::AUTH => 'oauth2',
            ],
            [
                MockOAuth2Server::KEY_TOKEN_INVALID_COUNT => 1,
                MockOAuth2Server::KEY_EXPECTED_QUERY_COUNT => 3,
            ]
        );

        $accessTokenGrantType = new ClientCredentials($client, $credentials);

        $middleware = new MockOAuthMiddleware(
            $client,
            $accessTokenGrantType,
            new RefreshToken($client, $credentials),
            [
                MockOAuthMiddleware::KEY_TOKEN_EXPIRED_ON_FAILURE_COUNT => 1,
            ]
        );
        $handlerStack = $this->getHandlerStack();
        $handlerStack->push($middleware->onBefore());
        $handlerStack->push($middleware->modifyBeforeOnFailure());
        $handlerStack->push($middleware->onFailure(5));

        // Use a access token that isn't expired on the client side, but
        // the server thinks is expired. This should trigger the onFailure event
        // in the middleware, forcing it to try the refresh token grant type.
        $accessToken = new AccessToken('tokenInvalid', 'client_credentials', [
            'refresh_token' => 'refreshTokenOld',
            'expires' => time() + 500,
        ]);
        $middleware->setAccessToken($accessToken);
        $this->assertFalse($middleware->getAccessToken()->isExpired());

        //Will invoke once the onFailure
        $response = $client->get('/api/collection');

        // Now, the access token should be valid.
        $this->assertFalse($middleware->getAccessToken()->isExpired());
        $this->assertEquals(200, $response->getStatusCode());

        // Also, the refresh token should have changed.
        $newRefreshToken = $middleware->getRefreshToken();
        $this->assertEquals('refreshToken', $newRefreshToken->getToken());
    }

    public function testMiddlewareWithValidNotExpiredToken()
    {
        $client = $this->createClient([
            RequestOptions::AUTH => 'oauth2',
            RequestOptions::HTTP_ERRORS => false,
        ], [
            MockOAuth2Server::KEY_EXPECTED_QUERY_COUNT => 1,
        ]);
        $credentials = [
            'client_id' => 'test',
            'client_secret' => 'testSecret',
        ];

        $accessTokenGrantType = new ClientCredentials($client, $credentials);

        $middleware = new OAuthMiddleware($client, $accessTokenGrantType);
        $handlerStack = $this->getHandlerStack();
        $handlerStack->push($middleware->onBefore());
        $handlerStack->push($middleware->onFailure(5));

        // Set a valid token.
        $middleware->setAccessToken('token');
        $this->assertEquals($middleware->getAccessToken()->getToken(), 'token');
        $this->assertFalse($middleware->getAccessToken()->isExpired());
        $response = $client->get('/api/collection');
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testOnFailureWhichReachesLimit()
    {
        $client = $this->createClient([
            RequestOptions::AUTH => 'oauth2',
            RequestOptions::HTTP_ERRORS => false,
        ], [
            MockOAuth2Server::KEY_TOKEN_INVALID_COUNT => 6,
            MockOAuth2Server::KEY_EXPECTED_QUERY_COUNT => 6,
        ]);
        $credentials = [
            'client_id' => 'test',
            'client_secret' => 'testSecret',
        ];

        $accessTokenGrantType = new ClientCredentials($client, $credentials);

        $middleware = new OAuthMiddleware($client, $accessTokenGrantType, new RefreshToken($client, $credentials));
        $handlerStack = $this->getHandlerStack();
        $handlerStack->push($middleware->onBefore());
        $handlerStack->push($middleware->onFailure(5));

        //Will invoke 5 times onFailure
        $middleware->setAccessToken('tokenInvalid');
        $response = $client->get('/api/collection');
        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testOnFailureWhichSuccessOnThirdTime()
    {
        $client = $this->createClient([
            RequestOptions::AUTH => 'oauth2',
            RequestOptions::HTTP_ERRORS => false,
        ], [
            MockOAuth2Server::KEY_TOKEN_INVALID_COUNT => 2,
            MockOAuth2Server::KEY_EXPECTED_QUERY_COUNT => 4,
        ]);
        $credentials = [
            'client_id' => 'test',
            'client_secret' => 'testSecret',
        ];

        $accessTokenGrantType = new ClientCredentials($client, $credentials);

        $middleware = new OAuthMiddleware($client, $accessTokenGrantType);
        $handlerStack = $this->getHandlerStack();
        $handlerStack->push($middleware->onBefore());
        $handlerStack->push($middleware->onFailure(5));

        // Will invoke 2 times onFailure
        $response = $client->get('/api/collection');
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testSettingManualAccessTokenWithInvalidValue()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid access token');

        $client = $this->createClient([], []);
        $middleware = new OAuthMiddleware($client);
        $middleware->setAccessToken([]);
    }

    public function testSettingManualRefreshTokenWhenNoAccessToken()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to update the refresh token. You have never set first the access token.');

        $client = $this->createClient([], []);
        $middleware = new OAuthMiddleware($client);
        $middleware->setRefreshToken('refreshToken');
    }

    public function testSettingManualRefreshTokenWithRefreshTokenGrantType()
    {
        $credentials = [
            'client_id' => 'test',
            'client_secret' => 'testSecret',
        ];
        $client = $this->createClient([], []);
        $accessTokenGrantType = new ClientCredentials($client, $credentials);
        $refreshTokenGrantType = new RefreshToken($client, $credentials);

        $middleware = new OAuthMiddleware($client, $accessTokenGrantType, $refreshTokenGrantType);
        $token = new AccessToken('token', 'client_credentials', ['refresh_token' => 'refreshTokenOld']);
        $middleware->setAccessToken($token);

        $this->assertEquals('refreshTokenOld', $middleware->getRefreshToken()->getToken());
        $this->assertEquals('refreshTokenOld', $refreshTokenGrantType->getConfigByName(RefreshToken::CONFIG_REFRESH_TOKEN));

        $middleware->setRefreshToken('refreshToken');
        $this->assertEquals('refresh_token', $middleware->getRefreshToken()->getType());
        $this->assertEquals('refreshToken', $middleware->getRefreshToken()->getToken());
        $this->assertEquals('refreshToken', $refreshTokenGrantType->getConfigByName(RefreshToken::CONFIG_REFRESH_TOKEN));
    }

    public function testSettingManualRefreshToken()
    {
        $client = $this->createClient([], []);

        $middleware = new OAuthMiddleware($client);
        $token = new AccessToken('token', 'client_credentials', ['refresh_token' => 'refreshTokenOld']);
        $middleware->setAccessToken($token);

        $this->assertEquals('refreshTokenOld', $middleware->getRefreshToken()->getToken());

        $middleware->setRefreshToken('refreshToken');
        $this->assertEquals('refresh_token', $middleware->getRefreshToken()->getType());
        $this->assertEquals('refreshToken', $middleware->getRefreshToken()->getToken());
    }
}
