guzzle-oauth2-plugin
====================

Provides an OAuth2 plugin for [Guzzle](http://guzzlephp.org/).

Features
--------

- Acquires access tokens via one of the supported grant types (code, client credentials,
  user credentials, refresh token). Or you can set an access token yourself.
- Supports refresh tokens (stores them and uses them to get new access tokens).
- Handles token expiration (acquires new tokens and retries failed requests).

Ways to use it:

Just set an access token and make a request.
```php
use Guzzle\Http\Client;
use CommerceGuys\Guzzle\Plugin\Oauth2\Oauth2Plugin;

$accessToken = array(
  'access_token' => 'e72758a43e1646969f9f7bd7737d0cd637ed17ae',
  'expires' => '1391617481', // Optional.
);
$oauth2Plugin = new Oauth2Plugin();
$oauth2Plugin->setAccessToken($accessToken);

$client = new Client('https://mysite.com/api');
$client->addSubscriber($oauth2Plugin);
$request = $client->get('me');
```

Or use a grant type.
Optionally, add a refresh token grant type, used to refresh expired access tokens.
```php
use Guzzle\Http\Client;
use CommerceGuys\Guzzle\Plugin\Oauth2\Oauth2Plugin;
use CommerceGuys\Guzzle\Plugin\Oauth2\GrantType\PasswordCredentials;
use CommerceGuys\Guzzle\Plugin\Oauth2\GrantType\RefreshToken;

$oauth2Client = new Client('https://mysite.com/oauth2/token');
$config = array(
    'username' => 'myusername',
    'password' => 'mypassword',
    'client_id' => 'myclient',
    'client_secret' => 'mysecret',
    'scope' => 'administration', // Optional.
);
$grantType = new PasswordCredentials($oauth2Client, $config);
$refreshTokenGrantType = new RefreshToken($oauth2Client, $config);
$oauth2Plugin = new Oauth2Plugin($grantType, $refreshTokenGrantType);

$client = new Client('https://mysite.com/api');
$client->addSubscriber($oauth2Plugin);
$request = $client->get('me');
// $oauth2Plugin->getAccessToken() and $oauth2Plugin->getRefreshToken() can be
// used to export the tokens so that they can be persisted for next time.
```
