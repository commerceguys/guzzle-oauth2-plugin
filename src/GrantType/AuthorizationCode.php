<?php

namespace CommerceGuys\Guzzle\Oauth2\GrantType;

/**
 * Authorization code grant type.
 * @link http://tools.ietf.org/html/rfc6749#section-4.1
 */
class AuthorizationCode extends GrantTypeBase
{
    protected $grantType = 'authorization_code';
    protected $defaults = ['client_secret' => '', 'scope' => '', 'redirect_uri' => ''];
    protected $required = ['client_id', 'code'];
}
