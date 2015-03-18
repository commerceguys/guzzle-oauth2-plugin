<?php

namespace CommerceGuys\Guzzle\Oauth2\Tests\GrantType;

use CommerceGuys\Guzzle\Oauth2\GrantType\ClientCredentials;
use CommerceGuys\Guzzle\Oauth2\GrantType\RefreshToken;
use CommerceGuys\Guzzle\Oauth2\Oauth2Subscriber;
use CommerceGuys\Guzzle\Oauth2\Tests\TestBase;

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
            new RefreshToken($this->getClient(), $credentials + ['refresh_token' => ''])
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
}
