<?php

namespace Sainsburys\Guzzle\Oauth2\Middleware;

use Sainsburys\Guzzle\Oauth2\AccessToken;
use Sainsburys\Guzzle\Oauth2\GrantType\GrantTypeBase;
use Sainsburys\Guzzle\Oauth2\GrantType\GrantTypeInterface;
use Sainsburys\Guzzle\Oauth2\GrantType\RefreshTokenGrantTypeInterface;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class OAuthMiddleware
{
    /**
     * @var AccessToken|null
     */
    protected $accessToken;

    /**
     * @var GrantTypeInterface
     */
    protected $grantType;

    /**
     * @var RefreshTokenGrantTypeInterface
     */
    protected $refreshTokenGrantType;

    /**
     * @var ClientInterface
     */
    protected $client;

    /**
     * Create a new Oauth2 subscriber.
     *
     * @param ClientInterface                $client
     * @param GrantTypeInterface             $grantType
     * @param RefreshTokenGrantTypeInterface $refreshTokenGrantType
     */
    public function __construct(
        ClientInterface $client,
        GrantTypeInterface $grantType = null,
        RefreshTokenGrantTypeInterface $refreshTokenGrantType = null
    ) {
        $this->client = $client;
        $this->grantType = $grantType;
        $this->refreshTokenGrantType = $refreshTokenGrantType;
    }

    /**
     * {@inheritdoc}
     */
    public function onBefore()
    {
        return function (callable $handler) {
            return function (RequestInterface $request, array $options) use ($handler) {
                if (
                    isset($options['auth']) &&
                    'oauth2' == $options['auth'] &&
                    $this->grantType instanceof GrantTypeInterface &&
                    $this->grantType->getConfigByName(GrantTypeBase::CONFIG_TOKEN_URL) != $request->getUri()->getPath()
                ) {
                    $token = $this->getAccessToken();
                    if ($token !== null) {
                        return $handler($request->withAddedHeader('Authorization', 'Bearer '.$token->getToken()), $options);
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
                /* @var PromiseInterface */
                $promise = $handler($request, $options);

                return $promise->then(
                    function (ResponseInterface $response) use ($request, $options, &$calls, $limit) {
                        if (
                            $response->getStatusCode() == 401 &&
                            isset($options['auth']) &&
                            'oauth2' == $options['auth'] &&
                            $this->grantType instanceof GrantTypeInterface &&
                            $this->grantType->getConfigByName(GrantTypeBase::CONFIG_TOKEN_URL) != $request->getUri()->getPath()
                        ) {
                            ++$calls;
                            if ($calls > $limit) {
                                return $response;
                            }

                            if ($token = $this->getAccessToken()) {
                                $response = $this->client->send($request->withHeader('Authorization', 'Bearer '.$token->getToken()), $options);
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
        if ($this->refreshTokenGrantType) {
            // Get an access token using the stored refresh token.
            if ($this->accessToken instanceof AccessToken && $this->accessToken->getRefreshToken() instanceof AccessToken && $this->accessToken->isExpired()) {
                $this->refreshTokenGrantType->setRefreshToken($this->accessToken->getRefreshToken()->getToken());
                $this->accessToken = $this->refreshTokenGrantType->getToken();
            }
        }

        if ((!$this->accessToken || $this->accessToken->isExpired()) && $this->grantType) {
            // Get a new access token.
            $this->accessToken = $this->grantType->getToken();
        }

        return $this->accessToken;
    }

    /**
     * Get the access token.
     *
     * @return AccessToken|null Oauth2 access token
     */
    public function getAccessToken()
    {
        if (!($this->accessToken instanceof AccessToken) || $this->accessToken->isExpired()) {
            $this->acquireAccessToken();
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
        if ($this->accessToken instanceof AccessToken) {
            return $this->accessToken->getRefreshToken();
        }

        return null;
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
            $this->accessToken = new AccessToken($accessToken, $type, ['expires' => $expires]);
        } elseif ($accessToken instanceof AccessToken) {
            $this->accessToken = $accessToken;
        } else {
            throw new \InvalidArgumentException('Invalid access token');
        }

        if (
            $this->accessToken->getRefreshToken() instanceof AccessToken &&
            $this->refreshTokenGrantType instanceof RefreshTokenGrantTypeInterface
        ) {
            $this->refreshTokenGrantType->setRefreshToken($this->accessToken->getRefreshToken()->getToken());
        }

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
        if (!($this->accessToken instanceof AccessToken)) {
            throw new \InvalidArgumentException('Unable to update the refresh token. You have never set first the access token.');
        }

        if (is_string($refreshToken)) {
            $refreshToken = new AccessToken($refreshToken, 'refresh_token');
        } elseif (!$refreshToken instanceof AccessToken) {
            throw new \InvalidArgumentException('Invalid refresh token');
        }

        $this->accessToken->setRefreshToken($refreshToken);

        if ($this->refreshTokenGrantType instanceof RefreshTokenGrantTypeInterface) {
            $this->refreshTokenGrantType->setRefreshToken($refreshToken->getToken());
        }

        return $this;
    }
}
