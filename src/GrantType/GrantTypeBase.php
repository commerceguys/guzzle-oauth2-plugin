<?php

namespace CommerceGuys\Guzzle\Oauth2\GrantType;

use CommerceGuys\Guzzle\Oauth2\AccessToken;
use CommerceGuys\Guzzle\Oauth2\AccessTokenRepository;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Collection;

abstract class GrantTypeBase implements GrantTypeInterface
{
    const AUTH_LOCATION_URL = 'url';
    const AUTH_LOCATION_BODY = 'body';
    const AUTH_LOCATION_HEADER = 'headers';

    /** @var ClientInterface The token endpoint client */
    protected $client;

    /** @var Collection Configuration settings */
    protected $config;

    /** @var string */
    protected $grantType = '';

    /** @var \CommerceGuys\Guzzle\Oauth2\AccessTokenRepositoryInterface */
    protected $accessTokenRepository;

    /**
     * @param ClientInterface $client
     * @param array           $config
     */
    public function __construct(ClientInterface $client, array $config = [])
    {
        $this->client = $client;

        $this->config = Collection::fromConfig($config, $this->getDefaults(), $this->getRequired());

        // Should be injected, but this way I avoid breaking the concrete GrantTypes for now.
        $this->accessTokenRepository = new AccessTokenRepository($this->client, $this->config->toArray());
    }

    /**
     * Get default configuration items.
     *
     * @return array
     */
    protected function getDefaults()
    {
        return [
            'client_secret' => '',
            'scope' => '',
            'token_url' => 'oauth2/token',
            'auth_location' => self::AUTH_LOCATION_BODY,
            'token_method' => 'POST',
        ];
    }

    /**
     * Get required configuration items.
     *
     * @return string[]
     */
    protected function getRequired()
    {
        return ['client_id'];
    }

    /**
     * @inheritdoc
     */
    public function getToken()
    {
        return $this->accessTokenRepository->findToken($this->grantType);
    }
}
