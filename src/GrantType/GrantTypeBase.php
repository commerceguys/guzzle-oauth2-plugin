<?php

namespace Sainsburys\Guzzle\Oauth2\GrantType;

use Sainsburys\Guzzle\Oauth2\AccessToken;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;
use InvalidArgumentException;

abstract class GrantTypeBase implements GrantTypeInterface
{
    const CONFIG_TOKEN_URL = 'token_url';
    const CONFIG_CLIENT_ID = 'client_id';
    const CONFIG_CLIENT_SECRET = 'client_secret';
    const CONFIG_AUTH_LOCATION = 'auth_location';
    const CONFIG_RESOURCE = 'resource';

    const GRANT_TYPE = 'grant_type';

    const MISSING_ARGUMENT = 'The config is missing the following key: "%s"';

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
        $this->config = array_merge($this->getDefaults(), $config);

        foreach ($this->getRequired() as $key => $requiredAttribute) {
            if (!isset($this->config[$key]) || empty($this->config[$key])) {
                throw new InvalidArgumentException(sprintf(self::MISSING_ARGUMENT, $key));
            }
        }
    }

    /**
     * Get default configuration items.
     *
     * @return array
     */
    protected function getDefaults()
    {
        return [
            'scope' => '',
            self::CONFIG_TOKEN_URL => '/oauth2/token',
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
        return [
            self::CONFIG_CLIENT_ID => '',
        ];
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
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param string $name
     *
     * @return mixed|null
     */
    public function getConfigByName($name)
    {
        if (array_key_exists($name, $this->config)) {
            return $this->config[$name];
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getToken()
    {
        $body = $this->config;
        $body[self::GRANT_TYPE] = $this->grantType;
        unset($body[self::CONFIG_TOKEN_URL], $body[self::CONFIG_AUTH_LOCATION]);

        $requestOptions = [];

        if ($this->config[self::CONFIG_AUTH_LOCATION] !== RequestOptions::BODY) {
            $requestOptions[RequestOptions::AUTH] = [
                $this->config[self::CONFIG_CLIENT_ID],
                isset($this->config[self::CONFIG_CLIENT_SECRET]) ? $this->config[self::CONFIG_CLIENT_SECRET] : '',
            ];
            unset($body[self::CONFIG_CLIENT_ID], $body[self::CONFIG_CLIENT_SECRET]);
        }

        $requestOptions[RequestOptions::FORM_PARAMS] = $body;

        if ($additionalOptions = $this->getAdditionalOptions()) {
            $requestOptions = array_merge_recursive($requestOptions, $additionalOptions);
        }

        $response = $this->client->post($this->config[self::CONFIG_TOKEN_URL], $requestOptions);
        $data = json_decode($response->getBody()->__toString(), true);

        return new AccessToken($data['access_token'], $data['token_type'], $data);
    }
}
