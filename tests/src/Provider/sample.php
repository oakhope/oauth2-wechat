<?php
$ini_array = parse_ini_file(__DIR__."/config.ini");

$appid = $ini_array['appid'];
$appsecret = $ini_array['secret'];
$redirect_uri = $ini_array['redirect_uri'];

require_once (__DIR__."/../../../vendor/autoload.php");
session_start();

$provider = new \Oakhope\OAuth2\Client\Provider\WebProvider(
    [
        'appid' => $appid,
        'secret' => $appsecret,
        'redirect_uri' => $redirect_uri
    ]
);

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
            ]
        );

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

        // The provider provides a way to get an authenticated API request for
        // the service, using the access token; it returns an object conforming
        // to Psr\Http\Message\RequestInterface.
        // -----------
//        $request = $provider->getAuthenticatedRequest(
//            'GET',
//            'https://api.weixin.qq.com/sns/oauth2/refresh_token',
//            $accessToken,
//            ['scope' => 'snsapi_base']
//        );
//        $client = new \GuzzleHttp\Client();
//        $res = $client->send($request);
//        echo "<br/><br/>";
//        var_export($res->getBody());


    } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {

        // Failed to get the access token or user details.
        echo "error:";
        exit($e->getMessage());

    }


}