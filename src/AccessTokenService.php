<?php

namespace CommerceGuys\Guzzle\Oauth2;

class AccessTokenService
{
    /**
     * @var string
     */
    private $method;

    /**
     * @var string
     */
    private $url;

    /**
     * @param string $method
     * @param string $url
     */
    public function __construct($method, $url)
    {
        if (!is_string($method)) {
            throw new InvalidArgumentException('Argument $method must be a string.');
        }

        if (!is_string($url)) {
            throw new InvalidArgumentException('Argument $url must be a string.');
        }

        $this->method = $method;
        $this->url = $url;
    }

    /**
     * @param array $parameters
     *
     * @return string
     */
    public function getUrl(array $parameters = [])
    {
        if (empty($parameters)) {
            return $this->url;
        }

        if (false === strstr($this->url, '?')) {
            return $this->url . '?' . http_build_query($parameters);
        }

        return $this->url . '&' . http_build_query($parameters);
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }
}
