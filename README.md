# Wechat Provider for OAuth 2.0 Client

[![Latest Version](https://img.shields.io/github/release/oakhope/oauth2-wechat.svg?style=flat-square)](https://github.com/oakhope/oauth2-wechat/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Build Status](https://img.shields.io/travis/oakhope/oauth2-wechat/master.svg?style=flat-square)](https://travis-ci.org/oakhope/oauth2-wechat)
[![Coverage Status](https://img.shields.io/scrutinizer/coverage/g/oakhope/oauth2-wechat.svg?style=flat-square)](https://scrutinizer-ci.com/g/oakhope/oauth2-wechat/code-structure)
[![Quality Score](https://img.shields.io/scrutinizer/g/oakhope/oauth2-wechat.svg?style=flat-square)](https://scrutinizer-ci.com/g/oakhope/oauth2-wechat)
[![Total Downloads](https://img.shields.io/packagist/dt/oakhope/oauth2-wechat.svg?style=flat-square)](https://packagist.org/packages/oakhope/oauth2-wechat)

This package provides Wechat OAuth 2.0 support for the PHP League's [OAuth 2.0 Client](https://github.com/thephpleague/oauth2-client).

## Installation

To install, use composer:

```
composer require oakhope/oauth2-wechat
```

## Usage

Usage is the same as The League's OAuth client, using `\Oakhope\OAuth2\Client\Provider\Wechat` as the provider.

### Authorization Code Flow

```php
$provider = new Oakhope\OAuth2\Client\Provider\Wechat([
    'clientId'          => '{wechat-client-id}',
    'clientSecret'      => '{wechat-client-secret}',
    'redirectUri'       => 'https://example.com/callback-url'
]);

if (!isset($_GET['code'])) {

    // If we don't have an authorization code then get one
    $authUrl = $provider->getAuthorizationUrl();
    $_SESSION['oauth2state'] = $provider->getState();
    header('Location: '.$authUrl);
    exit;

// Check given state against previously stored one to mitigate CSRF attack
} elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {

    unset($_SESSION['oauth2state']);
    exit('Invalid state');

} else {

    // Try to get an access token (using the authorization code grant)
    $token = $provider->getAccessToken('authorization_code', [
        'code' => $_GET['code']
    ]);

    // Optional: Now you have a token you can look up a users profile data
    try {

        // We got an access token, let's now get the user's details
        $user = $provider->getResourceOwner($token);

        // Use these details to create a new profile
        printf('Hello %s!', $user->getId());

    } catch (Exception $e) {

        // Failed to get user details
        exit('Oh dear...');
    }

    // Use this to interact with an API on the users behalf
    echo $token->getToken();
}
```

## Testing

``` bash
$ ./vendor/bin/phpunit
```

## Contributing

Please see [CONTRIBUTING](https://github.com/oakhope/oauth2-wechat/blob/master/CONTRIBUTING.md) for details.


## Credits

- [Benji Wang](https://github.com/oakhope)
- [All Contributors](https://github.com/oakhope/oauth2-wechat/contributors)


## License

The MIT License (MIT). Please see [License File](https://github.com/oakhope/oauth2-wechat/blob/master/LICENSE) for more information.