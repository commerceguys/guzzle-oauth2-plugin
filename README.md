guzzle-oauth2-plugin
====================

Provides an OAuth2 plugin (middleware) for [Guzzle](http://guzzlephp.org/).

[![Build Status](https://travis-ci.org/commerceguys/guzzle-oauth2-plugin.svg)](https://travis-ci.org/commerceguys/guzzle-oauth2-plugin)
[![Code Coverage](https://scrutinizer-ci.com/g/commerceguys/guzzle-oauth2-plugin/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/commerceguys/guzzle-oauth2-plugin/?branch=master)

This version is intended for Guzzle 6:

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
use CommerceGuys\Guzzle\Oauth2\GrantType\RefreshToken;
use CommerceGuys\Guzzle\Oauth2\GrantType\PasswordCredentials;
use CommerceGuys\Guzzle\Oauth2\OAuthMiddleware;

$base_uri = 'https://example.com';

$handlerStack = HandlerStack::create();
$client = new Client(['handler'=> $handlerStack, 'base_uri' => $base_uri, 'auth' => 'oauth2']);

$config = [
    PasswordCredentials::USERNAME => 'test@example.com',
    PasswordCredentials::PASSWORD => 'test password',
    PasswordCredentials::CLIENT_ID => 'test-client',
    PasswordCredentials::CONFIG_TOKEN_URL => '/oauth/token',
    'scope' => 'administration',
];

$token = new PasswordCredentials($client, $config);
$refreshToken = new RefreshToken($client, $config);
$middleware = new OAuthMiddleware($client, $token, $refreshToken);

$handlerStack->push($middleware->onBefore());
$handlerStack->push($middleware->onFailure(5));

$response = $client->get('/api/user/me');

print_r($response->json());

// Use $middleware->getAccessToken(); and $middleware->getRefreshToken() to get tokens
// that can be persisted for subsequent requests.

```
