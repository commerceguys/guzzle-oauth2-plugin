<?php

namespace Sainsburys\Guzzle\Oauth2\Tests;

use Sainsburys\Guzzle\Oauth2\AccessToken;
use Sainsburys\Guzzle\Oauth2\GrantType\GrantTypeBase;
use Sainsburys\Guzzle\Oauth2\GrantType\GrantTypeInterface;
use Sainsburys\Guzzle\Oauth2\GrantType\RefreshTokenGrantTypeInterface;
use Sainsburys\Guzzle\Oauth2\Middleware\OAuthMiddleware;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class MockOAuthMiddleware extends OAuthMiddleware
{
    const KEY_TOKEN_EXPIRED_ON_FAILURE_COUNT = 'tokenExpiredOnFailureCount';

    /**
     * @var array
     */
    protected $options;

    /**
     * @var int
     */
    private $tokenExpiredOnFailureCount;

    /**
     * Create a new Oauth2 subscriber.
     *
     * @param ClientInterface                     $client
     * @param GrantTypeInterface|null             $grantType
     * @param RefreshTokenGrantTypeInterface|null $refreshTokenGrantType
     * @param array                               $options
     */
    public function __construct(
        ClientInterface $client,
        GrantTypeInterface $grantType = null,
        RefreshTokenGrantTypeInterface $refreshTokenGrantType = null,
        array $options = []
    ) {
        parent::__construct($client, $grantType, $refreshTokenGrantType);

        $this->options = $options;
        $this->tokenExpiredOnFailureCount = 0;
    }

    /**
     * @return \Closure
     */
    public function modifyBeforeOnFailure()
    {
        $calls = 0;

        return function (callable $handler) use (&$calls) {
            return function (RequestInterface $request, array $options) use ($handler, &$calls) {
                /** @var PromiseInterface */
                $promise = $handler($request, $options);
                return $promise->then(
                    function (ResponseInterface $response) use ($request, $options, &$calls) {
                        if (
                            isset($this->options[self::KEY_TOKEN_EXPIRED_ON_FAILURE_COUNT]) &&
                            isset($options['auth']) &&
                            'oauth2' == $options['auth'] &&
                            $this->grantType instanceof GrantTypeInterface &&
                            $this->grantType->getConfigByName(GrantTypeBase::CONFIG_TOKEN_URL) != $request->getUri()->getPath()
                        ) {
                            ++$this->tokenExpiredOnFailureCount;
                            if ($this->tokenExpiredOnFailureCount <= $this->options[self::KEY_TOKEN_EXPIRED_ON_FAILURE_COUNT]) {
                                $token = new AccessToken('tokenExpires', 'client_credentials', ['expires' => 0, 'refresh_token' => 'refreshTokenOld']);
                                $this->setAccessToken($token);
                            }
                        }

                        return $response;
                    }
                );
            };
        };
    }
}
