<?php

namespace Sainsburys\Guzzle\Oauth2\Tests;


use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;

class MockOAuth2Server
{
    const KEY_TOKEN_EXPIRES_IN = 'tokenExpiresIn';
    const KEY_TOKEN_PATH = 'tokenPath';
    const KEY_TOKEN_INVALID_COUNT = 'tokenInvalidCount';
    const KEY_EXPECTED_QUERY_COUNT = 'expectedQueryCount';

    /** @var array */
    protected $options;

    /**
     * @var HandlerStack
     */
    private $handlerStack;

    /**
     * @var int
     */
    private $tokenInvalidCount = 0;

    /**
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $defaults = [
            self::KEY_TOKEN_EXPIRES_IN => 3600,
            self::KEY_TOKEN_PATH => '/oauth2/token',
            self::KEY_EXPECTED_QUERY_COUNT => 1
        ];

        $this->options = $options + $defaults;

        $handler = new MockHandler(
            $this->options[self::KEY_EXPECTED_QUERY_COUNT] > 0 ?
                array_fill(
                    0,
                    $this->options[self::KEY_EXPECTED_QUERY_COUNT],
                    function (RequestInterface $request, array $options) {
                        return $this->getResult($request, $options);
                    }
                )
                : []
        );

        $this->handlerStack = HandlerStack::create($handler);
    }

    /**
     * @return HandlerStack
     */
    public function getHandlerStack()
    {
        return $this->handlerStack;
    }

    /**
     * @param RequestInterface $request
     * @param array            $options
     *
     * @return array
     */
    protected function getResult(RequestInterface $request, array $options)
    {
        if ($request->getUri()->getPath() === $this->options[self::KEY_TOKEN_PATH]) {
            $response = $this->oauth2Token($request, $options);
        } elseif (strpos($request->getUri()->getPath(), '/api/') !== false) {
            $response = $this->mockApiCall($request);
        }
        if (!isset($response)) {
            throw new \RuntimeException("Mock server cannot handle given request URI");
        }

        return $response;
    }

    /**
     * @param RequestInterface $request
     * @param array            $options
     *
     * @return array
     */
    protected function oauth2Token(RequestInterface $request, $options)
    {
        $body = $request->getBody()->__toString();
        $requestBody = [];
        parse_str($body, $requestBody);
        $grantType = $requestBody['grant_type'];
        switch ($grantType) {
            case 'password':
                return $this->grantTypePassword($requestBody);

            case 'client_credentials':
                return $this->grantTypeClientCredentials($options);

            case 'refresh_token':
                return $this->grantTypeRefreshToken($requestBody);

            case 'urn:ietf:params:oauth:grant-type:jwt-bearer':
                return $this->grantTypeJwtBearer($requestBody);
        }
        throw new \RuntimeException("Test grant type not implemented: $grantType");
    }

    /**
     * @return array
     */
    protected function validTokenResponse()
    {
        $token = [
            'access_token' => 'token',
            'refresh_token' => 'refreshToken',
            'token_type' => 'bearer',
        ];

        if (isset($this->options[self::KEY_TOKEN_INVALID_COUNT])) {
            $token['access_token'] = 'tokenInvalid';
        } elseif (isset($this->options[self::KEY_TOKEN_EXPIRES_IN])) {
            $token['expires_in'] = $this->options[self::KEY_TOKEN_EXPIRES_IN];
        }

        return new Response(200, [], json_encode($token));
    }

    /**
     * @param array  $requestBody
     *
     * @return array
     *   The response as expected by the MockHandler.
     */
    protected function grantTypePassword(array $requestBody)
    {
        if ($requestBody['username'] != 'validUsername' || $requestBody['password'] != 'validPassword') {
            // @todo correct response headers
            return new Response(401);
        }

        return $this->validTokenResponse();
    }

    /**
     * @param array  $options
     *
     * @return array
     *   The response as expected by the MockHandler.
     */
    protected function grantTypeClientCredentials(array $options)
    {
        if (!isset($options['auth']) || !isset($options['auth'][1]) || $options['auth'][1] != 'testSecret') {
            // @todo correct response headers
            return new Response(401);
        }

        return $this->validTokenResponse();
    }

    /**
     * @param array  $requestBody
     *
     * @return array
     */
    protected function grantTypeRefreshToken(array $requestBody)
    {
        if ($requestBody['refresh_token'] == 'refreshTokenInvalid') {
            return new Response(401);
        }

        return $this->validTokenResponse();
    }

    /**
     * @param array  $requestBody
     *
     * @return array
     */
    protected function grantTypeJwtBearer(array $requestBody)
    {
        if (!array_key_exists('assertion', $requestBody)) {
            return new Response(401);
        }

        return $this->validTokenResponse();
    }

    /**
     * @param RequestInterface $request
     *
     * @return array
     */
    protected function mockApiCall(RequestInterface $request)
    {
        if (
            empty($request->getHeader('Authorization')) ||
            (
                $request->getHeader('Authorization')[0] == 'Bearer tokenInvalid' &&
                isset($this->options[self::KEY_TOKEN_INVALID_COUNT]) &&
                $this->tokenInvalidCount < $this->options[self::KEY_TOKEN_INVALID_COUNT]
            )
        ) {
            if ($request->getHeader('Authorization')[0] == 'Bearer tokenInvalid') {
                ++$this->tokenInvalidCount;
            }

            return new Response(401);
        }

        return new Response(200, [], json_encode('Hello World!'));
    }
}
