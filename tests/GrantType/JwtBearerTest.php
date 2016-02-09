<?php

namespace CommerceGuys\Guzzle\Oauth2\Tests\GrantType;

use CommerceGuys\Guzzle\Oauth2\GrantType\JwtBearer;
use CommerceGuys\Guzzle\Oauth2\Tests\TestBase;
use SplFileObject;

class JwtBearerTest extends TestBase
{
    public function testMissingParentConfigException()
    {
        $this->setExpectedException('\\InvalidArgumentException', 'The config is missing the following key: "client_id"');
        new JwtBearer($this->getClient());
    }

    public function testMissingConfigException()
    {
        $this->setExpectedException('\\InvalidArgumentException', 'The config is missing the following key: "private_key"');
        new JwtBearer($this->getClient(), ['client_id' => 'testClient']);
    }

    public function testPrivateKeyNotSplFileObject()
    {
        $this->setExpectedException('\\InvalidArgumentException', 'private_key needs to be instance of SplFileObject');
        new JwtBearer($this->getClient(), [
            'client_id' => 'testClient',
            'private_key' => 'INVALID'
        ]);
    }

    public function testValidRequestGetsToken()
    {
        $grantType = new JwtBearer($this->getClient(), [
            'client_id' => 'testClient',
            'private_key' => new SplFileObject(__DIR__ . '/../private.key')
        ]);
        $token = $grantType->getToken();
        $this->assertNotEmpty($token->getToken());
        $this->assertTrue($token->getExpires()->getTimestamp() > time());
    }
}
