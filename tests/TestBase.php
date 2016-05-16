<?php

namespace Sainsburys\Guzzle\Oauth2\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\HandlerStack;

abstract class TestBase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ClientInterface
     */
    protected $client;

    /**
     * @var MockOAuth2Server
     */
    protected $server;

    /**
     * @param array $options
     * @param array $serverOptions
     *
     * @return ClientInterface
     */
    protected function createClient(array $options = [], array $serverOptions = [])
    {
        $this->server = new MockOAuth2Server($serverOptions);
        $this->client = new Client([
                'handler' => $this->server->getHandlerStack()
            ] + $options);

        return $this->client;
    }

    /**
     * @return ClientInterface|null
     */
    protected function getClient()
    {
        return $this->client;
    }

    /**
     * @return HandlerStack|null
     */
    protected function getHandlerStack()
    {
        if ($this->server instanceof MockOAuth2Server) {
            return $this->server->getHandlerStack();
        }

        return null;
    }
}
