<?php

namespace CommerceGuys\Guzzle\Oauth2;

interface AccessTokenRepositoryInterface
{
    /**
     * Finds a token for the configured client given its grant type.
     *
     * @param string $grantType
     *   One of the supported OAuth2 grant types.
     *
     * @return AccessToken
     */
    public function findToken($grantType);
}
