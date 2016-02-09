<?php

namespace CommerceGuys\Guzzle\Oauth2\Tests;


use GuzzleHttp\Handler\MockHandler;
use Psr\Http\Message\RequestInterface;

class MockOAuth2Server
{
    /** @var array */
    protected $options;

    /**
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $defaults = [
            'tokenExpiresIn' => 3600,
            'tokenPath' => '/oauth2/token',
        ];
        $this->options = $options + $defaults;
    }

    /**
     * @return MockHandler
     */
    public function getHandler()
    {
        return new MockHandler([function (RequestInterface $request, array $options) {
            return $this->getResult($request, $options);
        }]);
    }

    /**
     * @param RequestInterface $request
     * @param array            $options
     *
     * @return array
     */
    protected function getResult(RequestInterface $request, array $options)
    {
        if ($request->getUri()->getPath() === $this->options['tokenPath']) {
            $response = $this->oauth2Token($request);
        } elseif (strpos($request->getUri()->getPath(), 'api/') !== false) {
            $response = $this->mockApiCall($request);
        }
        if (!isset($response)) {
            throw new \RuntimeException("Mock server cannot handle given request URI");
        }

        return $response;
    }

    /**
     * @param RequestInterface $request
     *
     * @return array
     */
    protected function oauth2Token(RequestInterface $request)
    {
        $body = $request->getBody()->__toString();
        $requestBody = json_decode($body, true);
        $grantType = $requestBody['grant_type'];
        switch ($grantType) {
            case 'password':
                return $this->grantTypePassword($requestBody);

            case 'client_credentials':
                return $this->grantTypeClientCredentials($request);

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
            'access_token' => 'testToken',
            'refresh_token' => 'testRefreshTokenFromServer',
            'token_type' => 'bearer',
        ];

        if (isset($this->options['tokenExpires'])) {
            $token['expires'] = $this->options['tokenExpires'];
        } elseif (isset($this->options['tokenExpiresIn'])) {
            $token['expires_in'] = $this->options['tokenExpiresIn'];
        }

        return [
            'status' => 200,
            'body' => json_encode($token),
        ];
    }

    /**
     * @param array $requestBody
     *
     * @return array
     *   The response as expected by the MockHandler.
     */
    protected function grantTypePassword(array $requestBody)
    {
        if ($requestBody['username'] != 'validUsername' || $requestBody['password'] != 'validPassword') {
            // @todo correct response headers
            return ['status' => 401];
        }

        return $this->validTokenResponse();
    }

    /**
     * @param RequestInterface $request
     *
     * @return array
     *   The response as expected by the MockHandler.
     */
    protected function grantTypeClientCredentials(RequestInterface $request)
    {
        if ($request->getHeader('auth')[1] != 'testSecret') {
            // @todo correct response headers
            return ['status' => 401];
        }

        return $this->validTokenResponse();
    }

    /**
     * @param array $requestBody
     *
     * @return array
     */
    protected function grantTypeRefreshToken(array $requestBody)
    {
        if ($requestBody['refresh_token'] != 'testRefreshToken') {
            return ['status' => 401];
        }

        return $this->validTokenResponse();
    }

    /**
     * @param array $requestBody
     *
     * @return array
     */
    protected function grantTypeJwtBearer(array $requestBody)
    {
        if (!array_key_exists('assertion', $requestBody)) {
            return ['status' => 401];
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
        if (!empty($request->getHeader('Authorization')) || $request->getHeader('Authorization')[0] != 'Bearer testToken') {
            return ['status' => 401];
        }

        return ['status' => 200, 'body' => json_encode('Hello World!')];
    }
}
