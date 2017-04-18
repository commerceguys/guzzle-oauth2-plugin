<?php

namespace Sainsburys\Guzzle\Oauth2\Tests\GrantType;

use Sainsburys\Guzzle\Oauth2\GrantType\JwtBearer;
use Sainsburys\Guzzle\Oauth2\Tests\TestBase;
use SplFileObject;

class JwtBearerTest extends TestBase
{
    public function testMissingParentConfigException()
    {
        $this->setExpectedException('\\InvalidArgumentException', 'The config is missing the following key: "client_id"');
        new JwtBearer($this->createClient());
    }

    public function testMissingConfigException()
    {
        $this->setExpectedException('\\InvalidArgumentException', 'The config is missing the following key: "private_key"');
        new JwtBearer($this->createClient(), [
            'client_id' => 'testClient',
            'client_secret' => 'clientSecret'
        ]);
    }

    public function testPrivateKeyNotSplFileObject()
    {
        $this->setExpectedException('\\InvalidArgumentException', 'private_key needs to be instance of SplFileObject');
        new JwtBearer($this->createClient(), [
            'client_id' => 'testClient',
            'client_secret' => 'clientSecret',
            'private_key' => 'INVALID'
        ]);
    }

    public function testValidRequestGetsToken()
    {
        $grantType = new JwtBearer($this->createClient(), [
            'client_id' => 'testClient',
            'client_secret' => 'clientSecret',
            'private_key' => new SplFileObject(__DIR__ . '/../private.key')
        ]);
        $token = $grantType->getToken();
        $this->assertNotEmpty($token->getToken());
        $this->assertTrue($token->getExpires()->getTimestamp() > time());
    }
}
