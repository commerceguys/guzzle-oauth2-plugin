<?php

namespace CommerceGuys\Guzzle\Oauth2;

use GuzzleHttp\Collection;
use GuzzleHttp\ClientInterface;
use CommerceGuys\Guzzle\Oauth2\AccessToken;
use CommerceGuys\Guzzle\Oauth2\AccessTokenService;
use CommerceGuys\Guzzle\Oauth2\GrantType\GrantTypeBase;
use InvalidArgumentException;
use LogicException;

final class AccessTokenRepository implements AccessTokenRepositoryInterface
{
    /**
     * @param stdClass
     */
    private $accessTokenService;

    /**
     * @param array
     */
    private $config;

    /**
     * @param ClientInterface $client
     * @param array           $config
     */
    public function __construct(ClientInterface $client, array $config = [])
    {
        $this->client = $client;

        if (empty($config['token_method'])) {
            throw new InvalidArgumentException('You must define a "token_method" in your configuration.');
        }

        if (empty($config['token_url'])) {
            throw new InvalidArgumentException('You must define a "token_url" in your configuration.');
        }

        $this->accessTokenService = new AccessTokenService(
            $config['token_method'],
            $config['token_url']
        );

        unset($config['token_method']);
        unset($config['token_url']);

        $this->config = $config;
    }

    /**
     * @inheritdoc
     */
    public function findToken($grantType, array $grantTypeOptions = [])
    {
        if (!is_string($grantType)) {
            throw new InvalidArgumentException('Argument $grantType must be a string.');
        }

        $parameters = array_merge($this->config, $grantTypeOptions, ['grant_type' => $grantType]);
        $guzzleOptions = [];

        switch ($this->config['auth_location']) {
            case GrantTypeBase::AUTH_LOCATION_HEADER:
                $guzzleOptions['auth'] = [$this->config['client_id'], $this->config['client_secret']];
                break;
            default:
        }

        switch ($this->accessTokenService->getMethod()) {
            case 'POST':
                $response = $this->client->post(
                    $this->accessTokenService->getUrl(),
                    array_merge($guzzleOptions, ['body' => $parameters])
                );
                break;
            case 'GET':
                $response = $this->client->get(
                    $this->accessTokenService->getUrl($parameters),
                    array_merge($guzzleOptions, ['headers' => ['Accept' => 'application/json']])
                );
                break;
            default:
                throw new LogicException('The configured "token_method" is not valid.');
        }

        $data = $response->json();

        return new AccessToken($data['access_token'], $data['token_type'], $data);
    }
}
