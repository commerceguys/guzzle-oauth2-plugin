![logo](http://www.sainsburys.co.uk/homepage/images/sainsburys.png)

Sainsbury's guzzle-oauth2-plugin
================================

Provides an OAuth2 plugin (middleware) for [Guzzle](http://guzzlephp.org/).

[![Build Status](https://travis-ci.org/Sainsburys/guzzle-oauth2-plugin.svg?branch=master)](https://travis-ci.org/Sainsburys/guzzle-oauth2-plugin)
[![Code Coverage](https://scrutinizer-ci.com/g/Sainsburys/guzzle-oauth2-plugin/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/Sainsburys/guzzle-oauth2-plugin/?branch=master)

Version 3.x (on the `master` branch) is intended for Guzzle 6:
```json
        "sainsburys/guzzle-oauth2-plugin": "^3.0"
```

Version 2.x (on the `release/2.0` branch) is intended for Guzzle 5:
```json
        "sainsburys/guzzle-oauth2-plugin": "^2.0"
```

Version 1.x (on the `release/1.0` branch) is intended for Guzzle 3 [Unmaintained]:
```json
        "sainsburys/guzzle-oauth2-plugin": "^1.0"
```

## Features

- Acquires access tokens via one of the supported grant types (code, client credentials,
  user credentials, refresh token). Or you can set an access token yourself.
- Supports refresh tokens (stores them and uses them to get new access tokens).
- Handles token expiration (acquires new tokens and retries failed requests).

## Running the tests

First make sure you have all the dependencies in place by running `composer install --prefer-dist`, then simply run `./bin/phpunit`.

## Example
```php
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Sainsburys\Guzzle\Oauth2\GrantType\RefreshToken;
use Sainsburys\Guzzle\Oauth2\GrantType\PasswordCredentials;
use Sainsburys\Guzzle\Oauth2\Middleware\OAuthMiddleware;

$baseUri = 'https://example.com';
$config = [
    PasswordCredentials::CONFIG_USERNAME => 'test@example.com',
    PasswordCredentials::CONFIG_PASSWORD => 'test password',
    PasswordCredentials::CONFIG_CLIENT_ID => 'test-client',
    PasswordCredentials::CONFIG_TOKEN_URL => '/oauth/token',
    'scope' => 'administration',
];

$oauthClient = new Client(['base_uri' => $baseUri]);
$grantType = new PasswordCredentials($oauthClient, $config);
$refreshToken = new RefreshToken($oauthClient, $config);
$middleware = new OAuthMiddleware($oauthClient, $grantType, $refreshToken);

$handlerStack = HandlerStack::create();
$handlerStack->push($middleware->onBefore());
$handlerStack->push($middleware->onFailure(5));

$client = new Client(['handler'=> $handlerStack, 'base_uri' => $baseUri, 'auth' => 'oauth2']);

$response = $client->request('GET', '/api/user/me');

// Use $middleware->getAccessToken(); and $middleware->getRefreshToken() to get tokens
// that can be persisted for subsequent requests.
```

## Example using Symfony DI config

```xml
<container xmlns="http://symfony.com/schema/dic/services"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="http://symfony.com/schema/dic/services http://symfony.com/schema/dic/services/services-1.0.xsd">

    <parameters>
        <parameter key="acme.base_uri">https://example.com</parameter>
        <parameter key="acme.oauth.config" type="collection">
            <parameter key="username">test@example.com</parameter>
            <parameter key="password">test password</parameter>
            <parameter key="client_id">test-client</parameter>
            <parameter key="token_url">/oauth/token</parameter>
        </parameter>
    </parameters>

    <services>
        <service id="acme.oauth.guzzle.client" class="GuzzleHttp\Client" public="false">
            <argument type="collection">
                <argument key="base_uri">%acme.base_uri%</argument>
            </argument>
        </service>

        <service id="acme.oauth.grant_type" class="Sainsburys\Guzzle\Oauth2\GrantType\PasswordCredentials" public="false">
            <argument type="service" id="acme.oauth.guzzle.client"/>
            <argument>%acme.oauth.config%</argument>
        </service>

        <service id="acme.oauth.refresh_token" class="Sainsburys\Guzzle\Oauth2\GrantType\RefreshToken" public="false">
            <argument type="service" id="acme.oauth.guzzle.client"/>
            <argument>%acme.oauth.config%</argument>
        </service>

        <service id="acme.oauth.middleware" class="Sainsburys\Guzzle\Oauth2\Middleware\OAuthMiddleware" public="false">
            <argument type="service" id="acme.oauth.guzzle.client"/>
            <argument type="service" id="acme.oauth.grant_type"/>
            <argument type="service" id="acme.oauth.refresh_token"/>
        </service>

        <service id="acme.guzzle.handler" class="GuzzleHttp\HandlerStack" public="false">
            <factory class="GuzzleHttp\HandlerStack" method="create"/>
            <call method="push">
                <argument type="service">
                    <service class="Closure">
                        <factory service="acme.oauth.middleware" method="onBefore"/>
                    </service>
                </argument>
            </call>
            <call method="push">
                <argument type="service">
                    <service class="Closure">
                        <factory service="acme.oauth.middleware" method="onFailure"/>
                        <argument>5</argument>
                    </service>
                </argument>
            </call>
        </service>

        <service id="acme.guzzle.client" class="GuzzleHttp\Client">
            <argument type="collection">
                <argument key="handler" type="service" id="acme.guzzle.handler" />
                <argument key="base_uri">%acme.base_uri%</argument>
                <argument key="auth">oauth2</argument>
            </argument>
        </service>
    </services>
</container>
```