<?php

namespace CommerceGuys\Guzzle\Oauth2;

use CommerceGuys\Guzzle\Oauth2\GrantType\GrantTypeInterface;
use CommerceGuys\Guzzle\Oauth2\GrantType\RefreshTokenGrantTypeInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class OauthRetrier
{
    /**
     * @var AccessToken|null
     */
    protected $accessToken;

    /**
     * @var AccessToken|null
     */
    protected $refreshToken;

    /**
     * @var GrantTypeInterface
     */
    protected $grantType;

    /**
     * @var RefreshTokenGrantTypeInterface
     */
    protected $refreshTokenGrantType;

    /**
     * Create a new Oauth2 subscriber.
     *
     * @param GrantTypeInterface             $grantType
     * @param RefreshTokenGrantTypeInterface $refreshTokenGrantType
     */
    public function __construct(/*GrantTypeInterface $grantType = null, RefreshTokenGrantTypeInterface $refreshTokenGrantType = null*/)
    {
//        $this->grantType = $grantType;
//        $this->refreshTokenGrantType = $refreshTokenGrantType;
    }

    /**
     * @param int               $retries
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param mixed        $data
     *
     * @return callable
     */
    public function decider()
    {
        return function ($retries, RequestInterface $request, ResponseInterface $response = null, $data) {
            if ($retries > 5 || ($response && $response->getStatusCode() != 401)) {
                return false;
            }

            var_dump($data);
            return false;
        };
    }
}
