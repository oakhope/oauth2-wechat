<?php namespace Oakhope\OAuth2\Client\Test\Provider;

use League\OAuth2\Client\Tool\QueryBuilderTrait;
use Mockery as m;
use Oakhope\OAuth2\Client\Provider\WebProvider;
use Oakhope\OAuth2\Client\Provider\WebResourceOwner;
use Symfony\Component\VarDumper\VarDumper;

class WebProviderTest extends \PHPUnit_Framework_TestCase
{
    use QueryBuilderTrait;

    /**
     * @var \Oakhope\OAuth2\Client\Provider\WebProvider
     */
    protected $provider;

    protected function setUp()
    {
        $this->provider = new WebProvider([
            'appid' => 'YOU_APPID',
            'secret' => 'appsecret',
            'redirect_uri' => 'http://example.com/your-redirect-url/',
        ]);
    }

    public function tearDown()
    {
        m::close();
        parent::tearDown();
    }

    public function testAuthorizationUrl()
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);
        parse_str($uri['query'], $query);
        $this->assertArrayHasKey('appid', $query);
        $this->assertArrayHasKey('redirect_uri', $query);
        $this->assertArrayHasKey('response_type', $query);
        $this->assertArrayHasKey('scope', $query);
        $this->assertArrayHasKey('state', $query);
        $this->assertNotNull($this->provider->getState());
    }

    public function testScopes()
    {
        $scopeSeparator = ',';
        $options = ['scope' => [uniqid(), uniqid()]];
        $query = ['scope' => implode($scopeSeparator, $options['scope'])];
        $url = $this->provider->getAuthorizationUrl($options);
        $encodedScope = $this->buildQueryString($query);
        $this->assertContains($encodedScope, $url);
    }

    public function testGetAuthorizationUrl()
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);

        $this->assertEquals('/connect/qrconnect', $uri['path']);
    }

    public function testGetBaseAccessTokenUrl()
    {
        $params = [];

        $url = $this->provider->getBaseAccessTokenUrl($params);
        $uri = parse_url($url);

        $this->assertEquals('/sns/oauth2/access_token', $uri['path']);
    }

    public function testGetAccessToken()
    {
        $response = m::mock('Psr\Http\Message\ResponseInterface');
        $response->shouldReceive('getBody')->andReturn('{"access_token": "ACCESS_TOKEN","expires_in": 7200,"refresh_token": "REFRESH_TOKEN","openid": "OPENID","scope": "SCOPE","unionid": "o6_bmasdasdsad6_2sgVt7hMZOPfL"}');
        $response->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);

        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')->times(1)->andReturn($response);
        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);

        $this->assertEquals('ACCESS_TOKEN', $token->getToken());
        $this->assertLessThanOrEqual(time() + 7200, $token->getExpires());
        $this->assertGreaterThanOrEqual(time(), $token->getExpires());
        $this->assertEquals('REFRESH_TOKEN', $token->getRefreshToken());
        $this->assertEquals('OPENID', $token->getValues()['openid']);
        $this->assertEquals('SCOPE', $token->getValues()['scope']);
        $this->assertEquals('o6_bmasdasdsad6_2sgVt7hMZOPfL', $token->getValues()['unionid']);
        $this->assertNull($token->getResourceOwnerId());
    }

    public function testUserData()
    {
        $openid = uniqid();
        $nickname = uniqid();
        $sex = random_int(1, 2);
        $province = uniqid();
        $city = uniqid();
        $country = uniqid();
        $headImagurl = uniqid();
        $privilege = '['.uniqid().','.uniqid().']';
        $unionid = uniqid();

        $postResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $postResponse->shouldReceive('getBody')->andReturn('{"access_token": "ACCESS_TOKEN","expires_in": 7200,"refresh_token": "REFRESH_TOKEN","openid": "OPENID","scope": "SCOPE","unionid": "o6_bmasdasdsad6_2sgVt7hMZOPfL"}');
        $postResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);


        $userResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $userResponse->shouldReceive('getBody')->andReturn('{"openid": "'.$openid.'","nickname": "'.$nickname.'","sex": "'.$sex.'","province": "'.$province.'","city": "'.$city.'","country": "'.$country.'","headimgurl": "'.$headImagurl.'","privilege": "'.$privilege.'","unionid": "'.$unionid.'"}');
        $userResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);


        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')
            ->times(2)
            ->andReturn($postResponse, $userResponse);
        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);

        /** @var WebResourceOwner $user */
        $user = $this->provider->getResourceOwner($token);

        $this->assertEquals($openid, $user->getId());
        $this->assertEquals($nickname, $user->getNickname());
        $this->assertEquals($sex, $user->getSex());
        $this->assertEquals($province, $user->getProvince());
        $this->assertEquals($city, $user->getCity());
        $this->assertEquals($country, $user->getCountry());
        $this->assertEquals($headImagurl, $user->getHeadimgurl());
        $this->assertEquals($privilege, $user->getPrivilege());
        $this->assertEquals($unionid, $user->getUnionid());
    }

    public function testUserDataFails()
    {
        $errorPayloads = [
            '{"errcode":40003,"errmsg": "invalid openid"}',
            '{"foo":"bar"}'
        ];

        $testPayload = function ($payload) {
            $postResponse = m::mock('Psr\Http\Message\ResponseInterface');
            $postResponse->shouldReceive('getBody')->andReturn('{"access_token": "ACCESS_TOKEN","expires_in": 7200,"refresh_token": "REFRESH_TOKEN","openid": "OPENID","scope": "SCOPE","unionid": "o6_bmasdasdsad6_2sgVt7hMZOPfL"}');
            $postResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);

            $userResponse = m::mock('Psr\Http\Message\ResponseInterface');
            $userResponse->shouldReceive('getBody')->andReturn($payload);
            $userResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
            $userResponse->shouldReceive('getStatusCode')->andReturn(500);

            $client = m::mock('GuzzleHttp\ClientInterface');
            $client->shouldReceive('send')
                ->times(2)
                ->andReturn($postResponse, $userResponse);
            $this->provider->setHttpClient($client);

            $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);

            try {
                $user = $this->provider->getResourceOwner($token);
                return false;
            } catch (\Exception $e) {
                $this->assertInstanceOf('\League\OAuth2\Client\Provider\Exception\IdentityProviderException', $e);
            }

            return $payload;
        };

        $this->assertCount(1, array_filter(array_map($testPayload, $errorPayloads)));
    }

}