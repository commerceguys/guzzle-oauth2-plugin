<?php

namespace CommerceGuys\Guzzle\Oauth2\Tests;

use GuzzleHttp\Ring\Client\MockHandler;

class MockOAuth2Server
{
    public $tokenPath = '/oauth2/token';

    /**
     * @return MockHandler
     */
    public function getHandler()
    {
        return new MockHandler(function (array $request) {
            return $this->getResult($request);
        });
    }

    /**
     * @param array $request
     *
     * @return array
     */
    protected function getResult(array $request)
    {
        if ($request['uri'] === $this->tokenPath) {
            $response = $this->oauth2Token($request);
        }
        elseif (strpos($request['uri'], 'api/') !== false) {
            $response = $this->mockApiCall($request);
        }
        if (!isset($response)) {
            throw new \RuntimeException("Mock server cannot handle given request URI");
        }

        return $response;
    }

    /**
     * @param array $request
     *
     * @return array
     */
    protected function oauth2Token(array $request)
    {
        /** @var \GuzzleHttp\Post\PostBody $body */
        $body = $request['body'];
        $requestBody = $body->getFields();
        $grantType = $requestBody['grant_type'];
        switch ($grantType) {
            case 'password':
                return $this->grantTypePassword($requestBody);

            case 'client_credentials':
                return $this->grantTypeClientCredentials($requestBody);

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
            'token_type' => 'bearer',
            'expires_in' => 3600,
        ];

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
     * @param array $requestBody
     *
     * @return array
     *   The response as expected by the MockHandler.
     */
    protected function grantTypeClientCredentials(array $requestBody)
    {
        if ($requestBody['client_secret'] != 'testSecret') {
            // @todo correct response headers
            return ['status' => 401];
        }

        return $this->validTokenResponse();
    }

    /**
     * @param array $request
     *
     * @return array
     */
    protected function mockApiCall(array $request)
    {
        if (!isset($request['headers']['Authorization']) || $request['headers']['Authorization'][0] != 'Bearer testToken') {
            return ['status' => 401];
        }

        return ['status' => 200, 'body' => json_encode('Hello World!')];
    }
}