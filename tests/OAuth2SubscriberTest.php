<?php

namespace CommerceGuys\Guzzle\Oauth2\Tests\GrantType;

use CommerceGuys\Guzzle\Oauth2\GrantType\ClientCredentials;
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
        $response = $client->get('api/collection', ['exceptions' => false]);
        $this->assertEquals(200, $response->getStatusCode());
    }
}
