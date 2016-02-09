<?php

namespace CommerceGuys\Guzzle\Oauth2\Tests\GrantType;

use CommerceGuys\Guzzle\Oauth2\GrantType\AuthorizationCode;
use CommerceGuys\Guzzle\Oauth2\Tests\TestBase;

class AuthorizationCodeTest extends TestBase
{
    public function testMissingParentConfigException()
    {
        $this->setExpectedException('\\InvalidArgumentException', 'The config is missing the following key: "client_id"');
        new AuthorizationCode($this->getClient());
    }

    public function testMissingConfigException()
    {
        $this->setExpectedException('\\InvalidArgumentException', 'The config is missing the following key: "code"');
        new AuthorizationCode($this->getClient(), ['client_id' => 'testClient']);
    }
}
