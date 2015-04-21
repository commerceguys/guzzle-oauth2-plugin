<?php

namespace CommerceGuys\Guzzle\Oauth2\Tests;

use CommerceGuys\Guzzle\Oauth2\AccessTokenRepository;

class AccessTokenRepositoryTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->client = $this->prophesize('GuzzleHttp\ClientInterface');
    }

    public function testWrongInstantiation()
    {
        $this->setExpectedException(
          'InvalidArgumentException',
          'You must define a "token_method" in your configuration.'
        );

        new AccessTokenRepository($this->client->reveal(), []);


        $this->setExpectedException(
          'InvalidArgumentException',
          'You must define a "token_url" in your configuration.'
        );

        new AccessTokenRepository($this->client->reveal(), ['token_method' => 'POST']);
    }

    public function testDoesntFindTokenForWrongGrantType()
    {
        $repository = new AccessTokenRepository($this->client->reveal(), [
            'token_method' => 'POST',
            'token_url' => 'foo/bar',
        ]);

        $this->setExpectedException(
          'InvalidArgumentException',
          'Argument $grantType must be a string.'
        );

        $repository->findToken([]);
    }

    public function testDoesntFindTokenWhenUsingWrongHttpMethod()
    {
        $repository = new AccessTokenRepository($this->client->reveal(), [
            'token_method' => 'HEAD',
            'token_url' => 'foo/bar',
        ]);

        $this->setExpectedException(
          'LogicException',
          'The configured "token_method" is not valid.'
        );

        $repository->findToken('foo');
    }

    public function testFindsTokenWhenUsingPostHttpMethod()
    {
        $repository = new AccessTokenRepository($this->client->reveal(), [
            'token_method' => 'POST',
            'token_url' => 'foo/bar',
        ]);

        $response = $this->prophesize('GuzzleHttp\Message\ResponseInterface');

        $response->json()->willReturn([
            'access_token' => 'foo',
            'token_type' => 'bar',
        ]);

        $this->client->post('foo/bar', ['body' => ['grant_type' => 'foo']])
            ->willReturn($response->reveal())
            ->shouldBeCalledTimes(1)
        ;

        $this->assertInstanceOf('CommerceGuys\Guzzle\Oauth2\AccessToken', $repository->findToken('foo'));
    }

    public function testFindsTokenWhenUsingGetHttpMethod()
    {
        $repository = new AccessTokenRepository($this->client->reveal(), [
            'token_method' => 'GET',
            'token_url' => 'foo/bar',
        ]);

        $response = $this->prophesize('GuzzleHttp\Message\ResponseInterface');

        $response->json()->willReturn([
            'access_token' => 'foo',
            'token_type' => 'bar',
        ]);

        $this->client->get('foo/bar?grant_type=foo', ['headers' => ['Accept' => 'application/json']])
            ->willReturn($response->reveal())
            ->shouldBeCalledTimes(1)
        ;

        $this->assertInstanceOf('CommerceGuys\Guzzle\Oauth2\AccessToken', $repository->findToken('foo'));
    }
}
