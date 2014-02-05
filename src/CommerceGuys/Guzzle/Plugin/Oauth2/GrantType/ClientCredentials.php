<?php

namespace CommerceGuys\Guzzle\Plugin\Oauth2\GrantType;

use Guzzle\Common\Collection;
use Guzzle\Http\ClientInterface;
use Guzzle\Http\Exception\RequestException;

/**
 * Client credentials grant type.
 * @link http://tools.ietf.org/html/rfc6749#section-4.4
 */
class ClientCredentials implements GrantTypeInterface
{
    /** @var ClientInterface The token endpoint client */
    protected $client;

    /** @var Collection Configuration settings */
    protected $config;

    public function __construct(ClientInterface $client, $config)
    {
        $this->client = $client;
        $this->config = Collection::fromConfig($config, array(
            'client_secret' => '',
            'scope' => '',
        ), array(
            'client_id',
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getTokenData()
    {
        $postBody = array(
            'grant_type' => 'client_credentials',
        );
        if ($this->config['scope']) {
            $postBody['scope'] = $this->config['scope'];
        }
        $request = $this->client->post(null, array(), $postBody);
        $request->setAuth($this->config['client_id'], $this->config['client_secret']);
        $response = $request->send();
        $data = $response->json();

        $requiredData = array_flip(array('access_token', 'expires_in', 'refresh_token'));
        return array_intersect_key($data, $requiredData);
    }
}
