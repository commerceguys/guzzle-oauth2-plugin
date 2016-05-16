<?php

namespace Sainsburys\Guzzle\Oauth2\GrantType;

/**
 * Refresh token grant type.
 *
 * @link http://tools.ietf.org/html/rfc6749#section-6
 */
class RefreshToken extends GrantTypeBase implements RefreshTokenGrantTypeInterface
{
    const CONFIG_REFRESH_TOKEN = 'refresh_token';

    /**
     * @var string
     */
    protected $grantType = 'refresh_token';

    /**
     * {@inheritdoc}
     */
    protected function getDefaults()
    {
        return parent::getDefaults() + [self::CONFIG_REFRESH_TOKEN => ''];
    }

    /**
     * {@inheritdoc}
     */
    public function setRefreshToken($refreshToken)
    {
        $this->config[self::CONFIG_REFRESH_TOKEN] = $refreshToken;
    }

    /**
     * {@inheritdoc}
     */
    public function hasRefreshToken()
    {
        return !empty($this->config[self::CONFIG_REFRESH_TOKEN]);
    }

    /**
     * {@inheritdoc}
     */
    public function getToken()
    {
        if (!$this->hasRefreshToken()) {
            throw new \RuntimeException('Refresh token not available');
        }

        return parent::getToken();
    }
}
