<?php

namespace CommerceGuys\Guzzle\Oauth2\GrantType;

use CommerceGuys\Guzzle\Oauth2\AccessToken;


interface GrantTypeInterface
{
    /**
     * Get the token data returned by the OAuth2 server.
     * if cache is activated, we might need to force cache invalidation (ie 401)
     *
     * @param bool $forceCache
     *
     * @return AccessToken
     */
    public function getToken($forceCache = false);

    /**
     * @param $cache
     *
     * @return mixed
     */
    public function setCache($cache);
}
