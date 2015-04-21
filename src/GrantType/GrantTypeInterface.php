<?php

namespace CommerceGuys\Guzzle\Oauth2\GrantType;

use CommerceGuys\Guzzle\Oauth2\AccessToken;

interface GrantTypeInterface
{
    /**
     * Get the token data returned by the OAuth2 server.
     *
     * @throws LogicException
     *   When an unsopported http method is used for the access_token endpoint.
     *
     * @return AccessToken
     */
    public function getToken();
}
