guzzle-oauth2-plugin
====================

Provides an OAuth2 plugin for [Guzzle](http://guzzlephp.org/).

The `master` branch is intended for Guzzle 5:
```json
        "commerceguys/guzzle-oauth2-plugin": "dev-master"
```

Guzzle 3 compatibility continues in the [`1.0`](https://github.com/commerceguys/guzzle-oauth2-plugin/tree/1.0) branch:
```json
        "commerceguys/guzzle-oauth2-plugin": "~1.0"
```

## Features

- Acquires access tokens via one of the supported grant types (code, client credentials,
  user credentials, refresh token). Or you can set an access token yourself.
- Supports refresh tokens (stores them and uses them to get new access tokens).
- Handles token expiration (acquires new tokens and retries failed requests).

## Example
```php
use GuzzleHttp\Client;
use CommerceGuys\Guzzle\Oauth2\GrantType\RefreshToken;
use CommerceGuys\Guzzle\Oauth2\GrantType\PasswordCredentials;
use CommerceGuys\Guzzle\Oauth2\Oauth2Subscriber;

$base_url = 'https://example.com';

$oauth2Client = new Client(['base_url' => $base_url]);

$config = [
    'username' => 'test@example.com',
    'password' => 'test password',
    'client_id' => 'test-client',
    'scope' => 'administration',
];

$token = new PasswordCredentials($oauth2Client, $config);
$refreshToken = new RefreshToken($oauth2Client, $config);

$oauth2 = new Oauth2Subscriber($token, $refreshToken);

$client = new Client([
    'defaults' => [
        'auth' => 'oauth2',
        'subscribers' => [$oauth2],
    ],
]);

/** @var \GuzzleHttp\Message\Response */
$response = $client->get('https://example.com/api/user/me');

print_r($response->json());

// Use $oauth2->getAccessToken(); to get a token that can be persisted for
// subsequent requests.

```
