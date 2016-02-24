<?php

namespace CommerceGuys\Guzzle\Oauth2\Tests\GrantType;

use CommerceGuys\Guzzle\Oauth2\GrantType\RefreshToken;
use CommerceGuys\Guzzle\Oauth2\Tests\TestBase;

class RefreshTokenTest extends TestBase
{
    public function testGetTokenChecksForRefreshToken()
    {
        $grant = new RefreshToken($this->createClient(), [
            'client_id' => 'test',
            'client_secret' => 'clientSecret',
        ]);
        $this->setExpectedException('\\RuntimeException');
        $grant->getToken();
    }
}
