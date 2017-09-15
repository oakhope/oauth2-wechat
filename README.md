# Wechat Provider for OAuth 2.0 Client

[![Latest Version](https://img.shields.io/github/release/oakhope/oauth2-wechat.svg?style=flat-square)](https://github.com/oakhope/oauth2-wechat/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Build Status](https://img.shields.io/travis/oakhope/oauth2-wechat/master.svg?style=flat-square)](https://travis-ci.org/oakhope/oauth2-wechat)
[![Coverage Status](https://img.shields.io/scrutinizer/coverage/g/oakhope/oauth2-wechat.svg?style=flat-square)](https://scrutinizer-ci.com/g/oakhope/oauth2-wechat/code-structure)
[![Quality Score](https://img.shields.io/scrutinizer/g/oakhope/oauth2-wechat.svg?style=flat-square)](https://scrutinizer-ci.com/g/oakhope/oauth2-wechat)
[![Total Downloads](https://img.shields.io/packagist/dt/oakhope/oauth2-wechat.svg?style=flat-square)](https://packagist.org/packages/oakhope/oauth2-wechat)

This package provides Wechat OAuth 2.0 support for the PHP League's [OAuth 2.0 Client](https://github.com/thephpleague/oauth2-client).

- DONE:
    > Website SDK, Mini Programs

- TODO: 
    > Mobile App SDK 

## Installation

To install, use composer:

```
composer require oakhope/oauth2-wechat
```

## Usage

Usage is the same as The League's OAuth client, using `\Oakhope\OAuth2\Client\Provider\{WebProvider}` as the provider.

### Authorization Code Flow

```php
$provider = new \Oakhope\OAuth2\Client\Provider\WebProvider([
        'appid' => '{wechat-client-id}',
        'secret' => '{wechat-client-secret}',
        'redirect_uri' => 'https://example.com/callback-url'
    ]);

// If we don't have an authorization code then get one
if (!isset($_GET['code'])) {

    // Fetch the authorization URL from the provider; this returns the
    // urlAuthorize option and generates and applies any necessary parameters
    // (e.g. state).
    $authorizationUrl = $provider->getAuthorizationUrl();

    // Get the state generated for you and store it to the session.
    $_SESSION['oauth2state'] = $provider->getState();

    // Redirect the user to the authorization URL.
    header('Location: '.$authorizationUrl);
    exit;

// Check given state against previously stored one to mitigate CSRF attack
} elseif (empty($_GET['state']) || ($_GET['state'] !== rtrim($_SESSION['oauth2state'], '#wechat_redirect'))) {

    unset($_SESSION['oauth2state']);
    exit('Invalid state');

} else {

    try {

        // Try to get an access token using the authorization code grant.
        $accessToken = $provider->getAccessToken(
            'authorization_code',
            [
                'code' => $_GET['code'],
            ]);

        // We have an access token, which we may use in authenticated
        // requests against the service provider's API.
        echo "token: ".$accessToken->getToken()."<br/>";
        echo "refreshToken: ".$accessToken->getRefreshToken()."<br/>";
        echo "Expires: ".$accessToken->getExpires()."<br/>";
        echo ($accessToken->hasExpired() ? 'expired' : 'not expired')."<br/><br/>";

        // Using the access token, we may look up details about the
        // resource owner.
        $resourceOwner = $provider->getResourceOwner($accessToken);

        var_export($resourceOwner->toArray());
        
    } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {

        // Failed to get the access token or user details.
        echo "error:";
        exit($e->getMessage());
    }
}
```


### Refreshing a Token

Once your application is authorized, you can refresh an expired token using a refresh token rather than going through the entire process of obtaining a brand new token. To do so, simply reuse this refresh token from your data store to request a refresh.

_This example uses [Brent Shaffer's](https://github.com/bshaffer) demo OAuth 2.0 application named **Lock'd In**. See authorization code example above, for more details._

```php
$provider = new \Oakhope\OAuth2\Client\Provider\WebProvider([
        'appid' => '{wechat-client-id}',
        'secret' => '{wechat-client-secret}',
        'redirect_uri' => 'https://example.com/callback-url'
    ]);

$existingAccessToken = getAccessTokenFromYourDataStore();

if ($existingAccessToken->hasExpired()) {
    $newAccessToken = $provider->getAccessToken('refresh_token', [
        'refresh_token' => $existingAccessToken->getRefreshToken()
    ]);

    // Purge old access token and store new access token to your data store.
}
```

## Testing

``` bash
$ ./vendor/bin/phpunit --colors tests
```

## Contributing

Please see [CONTRIBUTING](https://github.com/oakhope/oauth2-wechat/blob/master/CONTRIBUTING.md) for details.


## Credits

- [Benji Wang](https://github.com/oakhope)
- [All Contributors](https://github.com/oakhope/oauth2-wechat/contributors)


## License

The MIT License (MIT). Please see [License File](https://github.com/oakhope/oauth2-wechat/blob/master/LICENSE) for more information.