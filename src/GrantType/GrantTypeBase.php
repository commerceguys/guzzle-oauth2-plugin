<?php

namespace CommerceGuys\Guzzle\Oauth2\GrantType;

use CommerceGuys\Guzzle\Oauth2\AccessToken;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\RequestOptions;

abstract class GrantTypeBase implements GrantTypeInterface
{
    const CONFIG_TOKEN_URL = 'token_url';
    const CONFIG_CLIENT_ID = 'client_id';
    const CONFIG_CLIENT_SECRET = 'client_secret';
    const CONFIG_AUTH_LOCATION = 'auth_location';

    const GRANT_TYPE = 'grant_type';

    /**
     * @var ClientInterface The token endpoint client
     */
    protected $client;

    /**
     * @var array Configuration settings
     */
    protected $config;

    /**
     * @var string
     */
    protected $grantType = '';

    /**
     * @param ClientInterface $client
     * @param array           $config
     */
    public function __construct(ClientInterface $client, array $config = [])
    {
        $this->client = $client;
        $this->config = array_merge($config, $this->getDefaults(), $this->getRequired());
    }

    /**
     * Get default configuration items.
     *
     * @return array
     */
    protected function getDefaults()
    {
        return [
            self::CONFIG_CLIENT_SECRET => '',
            'scope' => '',
            self::CONFIG_TOKEN_URL => 'oauth2/token',
            self::CONFIG_AUTH_LOCATION => 'headers',
        ];
    }

    /**
     * Get required configuration items.
     *
     * @return string[]
     */
    protected function getRequired()
    {
        return [self::CONFIG_CLIENT_ID];
    }

    /**
     * Get additional options, if any.
     *
     * @return array|null
     */
    protected function getAdditionalOptions()
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getToken()
    {
        $body = $this->config;
        $body[self::GRANT_TYPE] = $this->grantType;
        unset($body[self::CONFIG_TOKEN_URL], $body[self::CONFIG_AUTH_LOCATION]);

        $requestOptions = [];

        if ($this->config[self::CONFIG_AUTH_LOCATION] !== RequestOptions::BODY) {
            $requestOptions[RequestOptions::AUTH] = [$this->config[self::CONFIG_CLIENT_ID], $this->config[self::CONFIG_CLIENT_SECRET]];
            unset($body[self::CONFIG_CLIENT_ID], $body[self::CONFIG_CLIENT_SECRET]);
        }

        $requestOptions[RequestOptions::FORM_PARAMS] = $body;

        if ($additionalOptions  = $this->getAdditionalOptions()) {
            $requestOptions = array_merge_recursive($requestOptions, $additionalOptions);
        }

        $request = new Request('POST', $this->config[self::CONFIG_TOKEN_URL], $requestOptions, $body);
        $response = $this->client->send($request);
        $data = json_decode($response->getBody()->__toString(), true);

        return new AccessToken($data['access_token'], $data['token_type'], $data);
    }
}
