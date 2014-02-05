<?php

namespace CommerceGuys\Guzzle\Plugin\Oauth2\GrantType;

interface GrantTypeInterface
{
    /**
     * Get the token data returned by the OAuth2 server.
     *
     * @return array An array with the following keys:
     *               - access_token: The access token.
     *               - expires_in: The access token lifetime, in seconds.
     *               - refresh_token: The refresh token, if present.
     */
    public function getTokenData();
}
