<?php

namespace CommerceGuys\Guzzle\Oauth2\GrantType;

interface RefreshTokenGrantTypeInterface extends GrantTypeInterface
{

    /**
     * @param string $refreshToken
     */
    public function setRefreshToken($refreshToken);
}
