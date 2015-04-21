<?php

namespace CommerceGuys\Guzzle\Oauth2\GrantType;

/**
 * Refresh token grant type.
 *
 * @link http://tools.ietf.org/html/rfc6749#section-6
 */
class RefreshToken extends GrantTypeBase implements RefreshTokenGrantTypeInterface
{
    /**
     * @inheritdoc
     */
    protected $grantType = 'refresh_token';

    /**
     * @var string
     */
    protected $refreshToken;

    /**
     * @inheritdoc
     */
    protected function getDefaults()
    {
        return parent::getDefaults() + ['refresh_token' => ''];
    }

    /**
     * @inheritdoc
     */
    public function setRefreshToken($refreshToken)
    {
        $this->refreshToken = $refreshToken;
    }

    /**
     * @inheritdoc
     */
    public function hasRefreshToken()
    {
        return !empty($this->refreshToken);
    }

    /**
     * @inheritdoc
     */
    public function getToken()
    {
        if (!$this->hasRefreshToken()) {
            throw new \RuntimeException("Refresh token not available");
        }

        return $this->accessTokenRepository->findToken($this->grantType, [
            'refresh_token' => $this->refreshToken,
        ]);
    }
}
