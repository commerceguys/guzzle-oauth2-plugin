<?php

namespace CommerceGuys\Guzzle\Oauth2\GrantType;

use CommerceGuys\Guzzle\Oauth2\AccessToken;
use GuzzleHttp\Collection;
use GuzzleHttp\ClientInterface;

abstract class GrantTypeBase implements GrantTypeInterface
{
    /** @var ClientInterface The token endpoint client */
    protected $client;

    /** @var Collection Configuration settings */
    protected $config;

    /** @var string */
    protected $grantType = '';

    /** @var array */
    protected $defaults = ['client_secret' => '', 'scope' => '', 'token_url' => 'oauth2/token'];

    /** @var array */
    protected $required = ['client_id'];

    /**
     * @param ClientInterface $client
     * @param array           $config
     */
    public function __construct(ClientInterface $client, array $config = [])
    {
        $this->client = $client;
        $this->config = Collection::fromConfig($config, $this->defaults, $this->required);
    }

    /**
     * @inheritdoc
     */
    public function getToken()
    {
        $body = $this->config->toArray();
        $body['grant_type'] = $this->grantType;

        $access_token_url = $body['token_url'];
        unset($body['token_url']);

        $response = $this->client->post($access_token_url, ['body' => $body]);
        $data = $response->json();

        return new AccessToken($data['access_token'], $data['token_type'], $data);
    }
}
