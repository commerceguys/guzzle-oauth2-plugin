<?php

namespace CommerceGuys\Guzzle\Oauth2\GrantType;

/**
 * Refresh token grant type.
 * @link http://tools.ietf.org/html/rfc6749#section-6
 */
class RefreshToken extends GrantTypeBase implements RefreshTokenGrantTypeInterface
{
    protected $grantType = 'refresh_token';
    protected $required = ['client_id', 'refresh_token'];

    /**
     * @param string $refreshToken
     */
    public function setRefreshToken($refreshToken)
    {
        $this->config['refresh_token'] = $refreshToken;
    }
}
