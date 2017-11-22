<?php

namespace Sainsburys\Guzzle\Oauth2\Tests\GrantType;

use Sainsburys\Guzzle\Oauth2\GrantType\JwtBearer;
use Sainsburys\Guzzle\Oauth2\Tests\TestBase;
use SplFileObject;

class JwtBearerTest extends TestBase
{
    public function testMissingParentConfigException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The config is missing the following key: "client_id"');

        new JwtBearer($this->createClient());
    }

    public function testMissingConfigException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The config is missing the following key: "private_key"');

        new JwtBearer($this->createClient(), [
            'client_id' => 'testClient',
        ]);
    }

    public function testPrivateKeyNotSplFileObject()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('private_key needs to be instance of SplFileObject');

        new JwtBearer($this->createClient(), [
            'client_id' => 'testClient',
            'private_key' => 'INVALID'
        ]);
    }

    public function testValidRequestGetsToken()
    {
        $grantType = new JwtBearer($this->createClient(), [
            'client_id' => 'testClient',
            'private_key' => new SplFileObject(__DIR__ . '/../private.key')
        ]);
        $token = $grantType->getToken();
        $this->assertNotEmpty($token->getToken());
        $this->assertTrue($token->getExpires()->getTimestamp() > time());
    }
}
