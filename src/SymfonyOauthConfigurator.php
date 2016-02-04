<?php

namespace CommerceGuys\Guzzle\Oauth2;

use CommerceGuys\Guzzle\Oauth2\OauthRetrier;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Symfony\Component\DependencyInjection\Container;

class SymfonyOauthConfigurator
{
    /**
     * @var Container
     */
    private $container;

    public function __construct(Container $container)
    {
    }

    public function configure(HandlerStack $handlerStack)
    {
        $handlerStack->push($this->oauthModifier->onBefore());
        $handlerStack->push(Middleware::retry($this->oauthRetrier->decider()));
    }
}
