<?php

namespace Sainsburys\Guzzle\Oauth2\Tests\GrantType;

use Sainsburys\Guzzle\Oauth2\GrantType\RefreshToken;
use Sainsburys\Guzzle\Oauth2\Tests\TestBase;

class RefreshTokenTest extends TestBase
{
    public function testGetTokenChecksForRefreshToken()
    {
        $this->expectException(\RuntimeException::class);

        $grant = new RefreshToken($this->createClient(), [
            'client_id' => 'test',
        ]);
        $grant->getToken();
    }

    public function testMissingParentConfigException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The config is missing the following key: "client_id"');

        new RefreshToken($this->createClient());
    }
}
