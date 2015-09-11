<?php

namespace CommerceGuys\Guzzle\Oauth2\GrantType;

use CommerceGuys\Guzzle\Oauth2\AccessToken;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Collection;


abstract class GrantTypeBase implements GrantTypeInterface
{
    /** @var ClientInterface The token endpoint client */
    protected $client;

    /** @var Collection Configuration settings */
    protected $config;

    /** @var string */
    protected $grantType = '';

    /** @var  \Doctrine\Common\Cache\Cache */
    protected $cache;

    /**
     * @param ClientInterface $client
     * @param array           $config
     */
    public function __construct(ClientInterface $client, array $config = [])
    {
        $this->client = $client;
        $this->config = Collection::fromConfig($config, $this->getDefaults(), $this->getRequired());
    }

    /**
     * @param \Doctrine\Common\Cache\Cache $cache
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    public function setCache($cache)
    {
        if (!$cache instanceof \Doctrine\Common\Cache\Cache) {
            throw new \InvalidArgumentException('Provided cache must implement Doctrine Cache interface');
        }

        $this->cache = $cache;
    }

    /**
     * @return mixed \Doctrine\Common\Cache\Cache|null
     */
    public function getCache()
    {
        return $this->cache;
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
            'auth_location' => 'headers',
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
     * Get additional options, if any.
     *
     * @return array|null
     */
    protected function getAdditionalOptions()
    {
        return null;
    }

    /**
     * @return AccessToken
     */
    public function getToken()
    {
        $config = $this->config->toArray();

        if ($this->cache) {
            $key = $this->getCacheKey($config);

            if (!$data = $this->cache->fetch($key)) { //cache miss

                $lifetime = 0;
                $data     = $this->getTokenDatas($config);
                if (isset($data['expires'])) {
                    $lifetime = (int) $data['expires'] - time();
                    unset($data['expires']);
                } elseif (isset($data['expires_in'])) {
                    $lifetime = (int) $data['expires_in'];
                    unset($data['expires_in']);
                }

                $this->cache->save($key, serialize($data), $lifetime);
            } else {
                $data = unserialize($data);
            }

        } else {
            $data = $this->getTokenDatas($config);
        }

        return new AccessToken($data['access_token'], $data['token_type'], $data);
    }

    /**
     * @param $config
     *
     * @return mixed
     */
    protected function getTokenDatas($config)
    {
        $body = $config;
        $body['grant_type'] = $this->grantType;
        unset($body['token_url'], $body['auth_location']);

        $requestOptions = [];

        if ($config['auth_location'] !== 'body') {
            $requestOptions['auth'] = [$config['client_id'], $config['client_secret']];
            unset($body['client_id'], $body['client_secret']);
        }

        $requestOptions['body'] = $body;

        if ($additionalOptions = $this->getAdditionalOptions()) {
            $requestOptions = array_merge_recursive($requestOptions, $additionalOptions);
        }

        $response = $this->client->post($config['token_url'], $requestOptions);

        return $response->json();
    }

    /**
     * compute the current token cache key
     *
     * @param $config
     *
     * @return string
     */
    protected function getCacheKey($config)
    {

        $tokenIdent = sha1($this->client->getBaseUrl() . '_' . $config['client_id']);

        $key = sprintf(
            'cg_acesstoken_%s_%s',
            $this->grantType,
            $tokenIdent
        );

        return $key;
    }
}
