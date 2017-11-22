<?php

namespace Sainsburys\Guzzle\Oauth2\Tests\GrantType;

use Sainsburys\Guzzle\Oauth2\GrantType\PasswordCredentials;
use Sainsburys\Guzzle\Oauth2\Tests\TestBase;

class PasswordCredentialsTest extends TestBase
{
    public function testMissingParentConfigException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The config is missing the following key: "client_id"');

        new PasswordCredentials($this->createClient());
    }

    public function testMissingUsernameConfigException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The config is missing the following key: "username"');

        new PasswordCredentials($this->createClient(), [
            'client_id' => 'testClient',
        ]);
    }

    public function testMissingPasswordConfigException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The config is missing the following key: "password"');

        new PasswordCredentials($this->createClient(), [
            'client_id' => 'testClient',
            'username' => 'validUsername',
        ]);
    }

    public function testValidPasswordGetsToken()
    {
        $grantType = new PasswordCredentials($this->createClient(), [
            'client_id' => 'testClient',
            'username' => 'validUsername',
            'password' => 'validPassword',
        ]);

        $token = $grantType->getToken();
        $this->assertNotEmpty($token->getToken());
        $this->assertTrue($token->getExpires()->getTimestamp() > time());
    }
}
