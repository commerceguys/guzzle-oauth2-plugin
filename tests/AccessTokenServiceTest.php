<?php

namespace CommerceGuys\Guzzle\Oauth2\Tests;

use CommerceGuys\Guzzle\Oauth2\AccessTokenService;

class AccessTokenServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider servicesProvider
     */
    public function testFormatUrl($expectedUrl, $method, $url, $parameters)
    {
        $token = new AccessTokenService($method, $url);

        $this->assertSame($expectedUrl, $token->getUrl($parameters));
    }

    public function servicesProvider()
    {
        return [
            ['http://foo.bar', 'GET', 'http://foo.bar', []],
            ['http://foo.bar?token=123', 'GET', 'http://foo.bar', ['token' => '123']],
            ['http://foo.bar?baz=quux&token=123', 'GET', 'http://foo.bar?baz=quux', ['token' => '123']],
        ];
    }
}
