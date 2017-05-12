<?php

namespace Sainsburys\Guzzle\Oauth2\Tests\GrantType;

use Sainsburys\Guzzle\Oauth2\GrantType\RefreshToken;
use Sainsburys\Guzzle\Oauth2\Tests\TestBase;

class RefreshTokenTest extends TestBase
{
    public function testGetTokenChecksForRefreshToken()
    {
        $grant = new RefreshToken($this->createClient(), [
            'client_id' => 'test',
        ]);
        $this->setExpectedException(\RuntimeException::class);
        $grant->getToken();
    }
}
