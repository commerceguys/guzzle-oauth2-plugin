<?php

namespace CommerceGuys\Guzzle\Oauth2\GrantType;

/**
 * Resource owner password credentials grant type.
 *
 * @link http://tools.ietf.org/html/rfc6749#section-4.3
 */
class PasswordCredentials extends GrantTypeBase
{
    protected $grantType = 'password';
    protected $required = ['client_id', 'username', 'password'];

    /**
     * @inheritdoc
     */
    protected function getRequired()
    {
        return array_merge(parent::getRequired(), ['username', 'password']);
    }
}
