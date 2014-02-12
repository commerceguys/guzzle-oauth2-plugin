<?php

namespace CommerceGuys\Guzzle\Plugin\Oauth2\GrantType;

use Symfony\Component\Security\Core\SecurityContextInterface;
use HWI\Bundle\OAuthBundle\Security\Http\ResourceOwnerMap;

/**
 * HWIOAuthBundle Aware Refresh token grant type.
 * @link http://tools.ietf.org/html/rfc6749#section-6
 */
class HWIOAuthBundleRefreshToken implements GrantTypeInterface
{
    /** @var SecurityContextInterface Symfony2 security component */
    protected $securityContext;

    /** @var ResourceOwnerMap         HWIOAuthBundle OAuth2 ResourceOwnerMap */
    protected $resourceOwnerMap;

    public function __construct(ResourceOwnerMap $resourceOwnerMap, SecurityContextInterface $securityContext)
    {
        $this->securityContext = $securityContext;
        $this->resourceOwnerMap = $resourceOwnerMap;
    }

    /**
     * {@inheritdoc}
     */
    public function getTokenData($refreshToken = null)
    {
        $token = $this->securityContext->getToken();
        $resourceName = $token->getResourceOwnerName();
        $resourceOwner = $this->resourceOwnerMap->getResourceOwnerByName($resourceName);

        $data = $resourceOwner->refreshAccessToken($refreshToken);
        $token->setRawToken($data);
        $requiredData = array_flip(array('access_token', 'expires_in', 'refresh_token'));

        return array_intersect_key($data, $requiredData);
    }
}
