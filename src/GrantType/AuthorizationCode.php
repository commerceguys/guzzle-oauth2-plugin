<?php

namespace Sainsburys\Guzzle\Oauth2\GrantType;

/**
 * Authorization code grant type.
 *
 * @link http://tools.ietf.org/html/rfc6749#section-4.1
 */
class AuthorizationCode extends GrantTypeBase
{
    protected $grantType = 'authorization_code';

    const CONFIG_REDIRECT_URI = 'redirect_uri';
    const CONFIG_CODE = 'code';

    /**
     * {@inheritdoc}
     */
    protected function getDefaults()
    {
        return array_merge(parent::getDefaults(), [self::CONFIG_REDIRECT_URI => '']);
    }

    /**
     * {@inheritdoc}
     */
    protected function getRequired()
    {
        return array_merge(parent::getRequired(), [self::CONFIG_CODE => '']);
    }
}
