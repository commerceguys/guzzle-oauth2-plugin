<?php

namespace Sainsburys\Guzzle\Oauth2\Tests\GrantType;

use Sainsburys\Guzzle\Oauth2\GrantType\AuthorizationCode;
use Sainsburys\Guzzle\Oauth2\Tests\TestBase;

class AuthorizationCodeTest extends TestBase
{
    public function testMissingParentConfigException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The config is missing the following key: "client_id"');

        new AuthorizationCode($this->createClient());
    }

    public function testMissingConfigException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The config is missing the following key: "code"');

        new AuthorizationCode($this->createClient(), [
            'client_id' => 'testClient',
        ]);
    }
}
