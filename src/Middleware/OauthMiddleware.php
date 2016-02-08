<?php

namespace CommerceGuys\Guzzle\Oauth2\Middleware;

use CommerceGuys\Guzzle\Oauth2\AccessToken;
use CommerceGuys\Guzzle\Oauth2\GrantType\GrantTypeBase;
use CommerceGuys\Guzzle\Oauth2\GrantType\GrantTypeInterface;
use CommerceGuys\Guzzle\Oauth2\GrantType\RefreshTokenGrantTypeInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\DependencyInjection\Container;

class OauthMiddleware
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
     * @var Client
     */
    protected $client;

    /**
     * Create a new Oauth2 subscriber.
     *
     * @param Client                         $client
     * @param GrantTypeInterface             $grantType
     * @param RefreshTokenGrantTypeInterface $refreshTokenGrantType
     */
    public function __construct(
        Client $client,
        GrantTypeInterface $grantType = null,
        RefreshTokenGrantTypeInterface $refreshTokenGrantType = null
    ) {
        $this->client = $client;
        $this->grantType = $grantType;
        $this->refreshTokenGrantType = $refreshTokenGrantType;
    }

    /**
     * @inheritdoc
     */
    public function onBefore()
    {
        return function (callable $handler) {
            return function (RequestInterface $request, array $options) use ($handler) {
                if (
                    isset($options['auth']) &&
                    'oauth2' == $options['auth'] &&
                    $this->grantType->getConfigByName(GrantTypeBase::CONFIG_TOKEN_URL) != $request->getUri()->getPath()
                ) {
                    $token = $this->getAccessToken();
                    if ($token !== null) {
                        return $handler($request->withAddedHeader('Authorization', 'Bearer ' . $token->getToken()), $options);
                    }
                }

                return $handler($request, $options);
            };
        };
    }

    public function onFailure($limit)
    {
        $calls = 0;

        return function (callable $handler) use (&$calls, $limit) {
            return function (RequestInterface $request, array $options) use ($handler, &$calls, $limit) {
                /** @var PromiseInterface */
                $promise = $handler($request, $options);
                return $promise->then(
                    function (ResponseInterface $response) use ($request, $options, &$calls, $limit) {
                        ++$calls;
                        if (
                            $calls < $limit &&
                            $response->getStatusCode() == 401 &&
                            isset($options['auth']) &&
                            'oauth2' == $options['auth'] &&
                            $this->grantType->getConfigByName(GrantTypeBase::CONFIG_TOKEN_URL) != $request->getUri()->getPath()
                        ) {
                            if ($token = $this->acquireAccessToken()) {
                                $this->accessToken = $token;
                                $this->refreshToken = $token->getRefreshToken();
                                $response = $this->client->send($request->withHeader('Authorization', 'Bearer ' . $token->getToken()), $options);
                            }
                        }

                        return $response;
                    }
                );
            };
        };
    }

    /**
     * Get a new access token.
     *
     * @return AccessToken|null
     */
    protected function acquireAccessToken()
    {
        $accessToken = null;

        if ($this->refreshTokenGrantType) {
            // Get an access token using the stored refresh token.
            if ($this->refreshToken) {
                $this->refreshTokenGrantType->setRefreshToken($this->refreshToken->getToken());
            }
            if ($this->refreshTokenGrantType->hasRefreshToken()) {
                $accessToken = $this->refreshTokenGrantType->getToken();
            }
        }

        if (!$accessToken && $this->grantType) {
            // Get a new access token.
            $accessToken = $this->grantType->getToken();
        }

        return $accessToken ?: null;
    }

    /**
     * Get the access token.
     *
     * @return AccessToken|null Oauth2 access token
     */
    public function getAccessToken()
    {
        if ($this->accessToken && $this->accessToken->isExpired()) {
            // The access token has expired.
            $this->accessToken = null;
        }

        if (null === $this->accessToken) {
            // Try to acquire a new access token from the server.
            $this->accessToken = $this->acquireAccessToken();
            if ($this->accessToken) {
                $this->refreshToken = $this->accessToken->getRefreshToken();
            }
        }

        return $this->accessToken;
    }

    /**
     * Get the refresh token.
     *
     * @return AccessToken|null
     */
    public function getRefreshToken()
    {
        return $this->refreshToken;
    }

    /**
     * Set the access token.
     *
     * @param AccessToken|string $accessToken
     * @param string             $type
     * @param int                $expires
     *
     * @return self
     */
    public function setAccessToken($accessToken, $type = null, $expires = null)
    {
        if (is_string($accessToken)) {
            $accessToken = new AccessToken($accessToken, $type, ['expires' => $expires]);
        } elseif (!$accessToken instanceof AccessToken) {
            throw new \InvalidArgumentException('Invalid access token');
        }
        $this->accessToken = $accessToken;
        $this->refreshToken = $accessToken->getRefreshToken();

        return $this;
    }

    /**
     * Set the refresh token.
     *
     * @param AccessToken|string $refreshToken The refresh token
     *
     * @return self
     */
    public function setRefreshToken($refreshToken)
    {
        if (is_string($refreshToken)) {
            $refreshToken = new AccessToken($refreshToken, 'refresh_token');
        } elseif (!$refreshToken instanceof AccessToken) {
            throw new \InvalidArgumentException('Invalid refresh token');
        }

        $this->refreshToken = $refreshToken;

        return $this;
    }
}
