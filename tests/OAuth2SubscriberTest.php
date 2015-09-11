<?php

namespace CommerceGuys\Guzzle\Oauth2\Tests\GrantType;

use CommerceGuys\Guzzle\Oauth2\GrantType\ClientCredentials;
use CommerceGuys\Guzzle\Oauth2\GrantType\RefreshToken;
use CommerceGuys\Guzzle\Oauth2\Oauth2Subscriber;
use CommerceGuys\Guzzle\Oauth2\Tests\TestBase;
use Doctrine\Common\Cache\ArrayCache;

class OAuth2SubscriberTest extends TestBase
{
    public function testSubscriberRetriesRequestOn401()
    {
        $subscriber = new Oauth2Subscriber(new ClientCredentials($this->getClient(), [
            'client_id' => 'test',
            'client_secret' => 'testSecret',
        ]));
        $client = $this->getClient([
            'defaults' => [
                'subscribers' => [$subscriber],
                'auth' => 'oauth2',
            ],
        ]);
        $response = $client->get('api/collection');
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testSubscriberUsesRefreshToken()
    {
        $credentials = [
            'client_id' => 'test',
            'client_secret' => 'testSecret',
        ];

        $accessTokenGrantType = new ClientCredentials(
            $this->getClient([], ['tokenExpires' => 0]),
            $credentials
        );

        $subscriber = new Oauth2Subscriber(
            $accessTokenGrantType,
            new RefreshToken($this->getClient(), $credentials)
        );
        $subscriber->setRefreshToken('testRefreshToken');
        $client = $this->getClient([
            'defaults' => [
                'subscribers' => [$subscriber],
                'auth' => 'oauth2',
            ],
        ]);

        // Initially, the access token should be expired. After the first API
        // call, the subscriber will use the refresh token to get a new access
        // token.
        $this->assertTrue($accessTokenGrantType->getToken()->isExpired());

        $response = $client->get('api/collection');

        // Now, the access token should be valid.
        $this->assertFalse($subscriber->getAccessToken()->isExpired());
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testSettingManualAccessToken()
    {
        $subscriber = new Oauth2Subscriber();
        $client = $this->getClient([
            'defaults' => [
                'subscribers' => [$subscriber],
                'auth' => 'oauth2',
                'exceptions' => false,
            ],
        ]);

        // Set a valid token.
        $subscriber->setAccessToken('testToken');
        $this->assertEquals($subscriber->getAccessToken()->getToken(), 'testToken');
        $this->assertFalse($subscriber->getAccessToken()->isExpired());
        $response = $client->get('api/collection');
        $this->assertEquals(200, $response->getStatusCode());

        // Set an invalid token.
        $subscriber->setAccessToken('testInvalidToken');
        $response = $client->get('api/collection');
        $this->assertEquals(401, $response->getStatusCode());

        // Set an expired token.
        $subscriber->setAccessToken('testToken', 'bearer', 500);
        $this->assertNull($subscriber->getAccessToken());
    }

    public function testSettingManualRefreshToken()
    {
        $subscriber = new Oauth2Subscriber();
        $subscriber->setRefreshToken('testRefreshToken');
        $this->assertEquals('refresh_token', $subscriber->getRefreshToken()->getType());
        $this->assertEquals('testRefreshToken', $subscriber->getRefreshToken()->getToken());
    }

    public function testBadCacheInstanciation()
    {
        $this->setExpectedException('\\InvalidArgumentException', 'Provided cache must implement Doctrine Cache interface');

        $dummyCache = new \stdClass();
        $subscriber = new Oauth2Subscriber();
        $subscriber->setCache($dummyCache);
    }

    public function testSubscriberCacheInstanciation()
    {
        $credentials = [
            'client_id' => 'test',
            'client_secret' => 'testSecret',
        ];

        $accessTokenGrantType = new ClientCredentials(
            $this->getClient([], ['tokenExpires' => 0]),
            $credentials
        );

        $subscriber = new Oauth2Subscriber(
            $accessTokenGrantType,
            new RefreshToken($this->getClient(), $credentials)
        );

        $cache = new ArrayCache();
        $subscriber->setCache($cache);

        $this->assertSame($accessTokenGrantType->getCache(), $cache);
    }

    public function testCacheSuccessfullyPopulated()
    {
        $cache = new ArrayCache();
        $computedCacheKey = 'cg_acesstoken_client_credentials_0a8508877b473c528ee9e510db3e49aafac51145';

        $credentials = [
            'client_id' => 'test',
            'client_secret' => 'testSecret',
        ];

        $accessTokenGrantType = new ClientCredentials(
            $this->getClient([], ['tokenExpires' => 0]),
            $credentials
        );

        $subscriber = new Oauth2Subscriber(
            $accessTokenGrantType,
            new RefreshToken($this->getClient(), $credentials)
        );
        $subscriber->setCache($cache);

        $client = $this->getClient([
            'defaults' => [
                'subscribers' => [$subscriber],
                'auth' => 'oauth2',
                'exceptions' => false,
            ],
        ]);

        $this->assertFalse($cache->contains($computedCacheKey));

        $response = $client->get('api/collection');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($cache->contains($computedCacheKey));

        $serializedCacheContent = $cache->fetch($computedCacheKey);
        $tokenDatas = unserialize($serializedCacheContent);

        $this->assertInternalType('array', $tokenDatas);
        $this->assertArrayHasKey('access_token', $tokenDatas);
    }

    public function testCacheShouldBeUSedIfAlreadyPopulated()
    {
        $cache = new ArrayCache();
        $computedCacheKey = 'cg_acesstoken_client_credentials_0a8508877b473c528ee9e510db3e49aafac51145';

        $cache->save($computedCacheKey, serialize([
            'access_token' => 'cachedToken',
            'token_type' => 'bearer'
            ])
        );

        $credentials = [
            'client_id' => 'test',
            'client_secret' => 'testSecret',
        ];

        $accessTokenGrantType = new ClientCredentials(
            $this->getClient([], ['tokenExpires' => 0]),
            $credentials
        );

        $subscriber = new Oauth2Subscriber(
            $accessTokenGrantType,
            new RefreshToken($this->getClient(), $credentials)
        );
        $subscriber->setCache($cache);

        $client = $this->getClient([
            'defaults' => [
                'subscribers' => [$subscriber],
                'auth' => 'oauth2',
                'exceptions' => false,
            ],
        ]);


        $response = $client->get('api/collection');
        $token = $subscriber->getAccessToken();

        $this->assertEquals('cachedToken', $token->getToken());
        $this->assertEquals('bearer', $token->getType());
    }
}
