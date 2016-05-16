<?php

namespace Sainsburys\Guzzle\Oauth2\GrantType;

use Firebase\JWT\JWT;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;
use InvalidArgumentException;
use Psr\Http\Message\UriInterface;
use SplFileObject;

/**
 * JSON Web Token (JWT) Bearer Token Profiles for OAuth 2.0.
 *
 * @link http://tools.ietf.org/html/draft-jones-oauth-jwt-bearer-04
 */
class JwtBearer extends GrantTypeBase
{
    const CONFIG_PRIVATE_KEY = 'private_key';

    protected $grantType = 'urn:ietf:params:oauth:grant-type:jwt-bearer';

    /**
     * @param ClientInterface $client
     * @param array           $config
     */
    public function __construct(ClientInterface $client, array $config = [])
    {
        parent::__construct($client, $config);

        if (!($this->config[self::CONFIG_PRIVATE_KEY] instanceof SplFileObject)) {
            throw new InvalidArgumentException('private_key needs to be instance of SplFileObject');
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getRequired()
    {
        return array_merge(parent::getRequired(), [self::CONFIG_PRIVATE_KEY => null]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getAdditionalOptions()
    {
        return [
            RequestOptions::FORM_PARAMS => [
                'assertion' => $this->computeJwt(),
            ],
        ];
    }

    /**
     * Compute JWT, signing with provided private key.
     *
     * @return string
     */
    protected function computeJwt()
    {
        $baseUri = $this->client->getConfig('base_uri');

        $payload = [
            'iss' => $this->config[self::CONFIG_CLIENT_ID],
            'aud' => sprintf('%s/%s', rtrim(($baseUri instanceof UriInterface ? $baseUri->__toString() : ''), '/'), ltrim($this->config[self::CONFIG_TOKEN_URL], '/')),
            'exp' => time() + 60 * 60,
            'iat' => time(),
        ];

        return JWT::encode($payload, $this->readPrivateKey($this->config[self::CONFIG_PRIVATE_KEY]), 'RS256');
    }

    /**
     * Read private key.
     *
     * @param SplFileObject $privateKey
     *
     * @return string
     */
    protected function readPrivateKey(SplFileObject $privateKey)
    {
        $key = '';
        while (!$privateKey->eof()) {
            $key .= $privateKey->fgets();
        }

        return $key;
    }
}
