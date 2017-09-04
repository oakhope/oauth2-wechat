<?php


namespace Oakhope\OAuth2\Client\Provider;

use League\OAuth2\Client\Grant\AbstractGrant;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\ArrayAccessorTrait;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\VarDumper\VarDumper;

class WebProvider extends AbstractProvider
{
    use ArrayAccessorTrait;

    protected $appid;
    protected $secret;
    protected $redirect_uri;

    /**
     * Returns the base URL for authorizing a client.
     *
     * @return string
     */
    public function getBaseAuthorizationUrl()
    {
        return 'https://open.weixin.qq.com/connect/qrconnect';
    }

    /**
     * Returns authorization parameters based on provided options.
     *
     * @param  array $options
     * @return array Authorization parameters
     */
    protected function getAuthorizationParameters(array $options)
    {
        $options += [
            'appid' => $this->appid
        ];

        if (!isset($options['redirect_uri'])) {
            $options['redirect_uri'] = $this->redirect_uri;
        }

        $options += [
            'response_type'   => 'code'
        ];

        if (empty($options['scope'])) {
            $options['scope'] = 'snsapi_login';
        }

        if (is_array($options['scope'])) {
            $separator = $this->getScopeSeparator();
            $options['scope'] = implode($separator, $options['scope']);
        }

        if (empty($options['state'])) {
            $options['state'] = $this->getRandomState().'#wechat_redirect';
        }

        // Store the state as it may need to be accessed later on.
        $this->state = $options['state'];

        return $options;
    }

    /**
     * Returns the base URL for requesting an access token.
     *
     * @param array $params
     * @return string
     */
    public function getBaseAccessTokenUrl(array $params)
    {
        return 'https://api.weixin.qq.com/sns/oauth2/access_token';
    }

    /**
     * Requests an access token using a specified grant and option set.
     *
     * @param  mixed $grant
     * @param  array $options
     * @return AccessToken
     */
    public function getAccessToken($grant, array $options = [])
    {
        $grant = $this->verifyGrant($grant);
        $params = [
            'appid'     => $this->appid,
            'secret' => $this->secret
        ];

        $params   = $grant->prepareRequestParameters($params, $options);
        $request  = $this->getAccessTokenRequest($params);
        $response = $this->getParsedResponse($request);
        $prepared = $this->prepareAccessTokenResponse($response);
        $token    = $this->createAccessToken($prepared, $grant);

        return $token;
    }

    /**
     * Creates an access token from a response.
     *
     * The grant that was used to fetch the response can be used to provide
     * additional context.
     *
     * @param  array $response
     * @param  AbstractGrant $grant
     * @return AccessToken
     */
    protected function createAccessToken(array $response, AbstractGrant $grant)
    {
        return new AccessToken($response);
    }

    /**
     * Returns the URL for requesting the resource owner's details.
     *
     * @param AccessToken $token
     * @return string
     */
    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        return 'https://api.weixin.qq.com/sns/userinfo?access_token='.
            $token->getToken().'&openid='.$token->getValues()['openid'];
    }

    /**
     * Returns the default scopes used by this provider.
     *
     * This should only be the scopes that are required to request the details
     * of the resource owner, rather than all the available scopes.
     *
     * @return array
     */
    protected function getDefaultScopes()
    {
       return ['snsapi_userinfo'];
    }

    /**
     * Checks a provider response for errors.
     *
     * @throws IdentityProviderException
     * @param  ResponseInterface $response
     * @param  array|string $data Parsed response data
     * @return void
     */
    protected function checkResponse(ResponseInterface $response, $data)
    {
        $errors = [
            'errcode',
            'errmsg',
        ];
        array_map(function ($error) use ($response, $data) {
            if ($message = $this->getValueByKey($data, $error)) {
                throw new IdentityProviderException($message, $response->getStatusCode(), $response);
            }
        }, $errors);
    }

    /**
     * Generates a resource owner object from a successful resource owner
     * details request.
     *
     * @param  array $response
     * @param  AccessToken $token
     * @return ResourceOwnerInterface
     */
    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new WebResourceOwner($response);
    }

}