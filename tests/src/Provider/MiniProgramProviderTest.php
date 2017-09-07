<?php

namespace Oakhope\OAuth2\Client\Test\Provider;

use League\OAuth2\Client\Tool\QueryBuilderTrait;
use Mockery as m;
use Oakhope\OAuth2\Client\Provider\MiniProgramProvider;
use Oakhope\OAuth2\Client\Provider\MiniProgramResourceOwner;
use Oakhope\OAuth2\Client\Token\MiniProgram\AccessToken;
use Symfony\Component\VarDumper\VarDumper;

class MiniProgramProviderTest extends \PHPUnit_Framework_TestCase
{
    use QueryBuilderTrait;

    /**
     * @var \Oakhope\OAuth2\Client\Provider\MiniProgramProvider
     */
    protected $provider;

    protected $appid;
    protected $sessionKey;
    protected $encryptedData;
    protected $iv;

    protected function setUp()
    {
        $this->appid = 'wx4f4bc4dec97d474b';
        $this->sessionKey = 'tiihtNczf5v6AKRyjwEUhQ==';
        $this->encryptedData = "CiyLU1Aw2KjvrjMdj8YKliAjtP4gsMZMQmRzooG2xrDcvSnxIMXFufNstNGTyaGS9uT5geRa0W4oTOb1WT7fJlAC+oNPdbB+3hVbJSRgv+4lGOETKUQz6OYStslQ142dNCuabNPGBzlooOmB231qMM85d2/fV6ChevvXvQP8Hkue1poOFtnEtpyxVLW1zAo6/1Xx1COxFvrc2d7UL/lmHInNlxuacJXwu0fjpXfz/YqYzBIBzD6WUfTIF9GRHpOn/Hz7saL8xz+W//FRAUid1OksQaQx4CMs8LOddcQhULW4ucetDf96JcR3g0gfRK4PC7E/r7Z6xNrXd2UIeorGj5Ef7b1pJAYB6Y5anaHqZ9J6nKEBvB4DnNLIVWSgARns/8wR2SiRS7MNACwTyrGvt9ts8p12PKFdlqYTopNHR1Vf7XjfhQlVsAJdNiKdYmYVoKlaRv85IfVunYzO0IKXsyl7JCUjCpoG20f0a04COwfneQAGGwd5oa+T8yO5hzuyDb/XcxxmK01EpqOyuxINew==";
        $this->iv = 'r7BXXKkLb8qrSNn05n0qiA==';

        $this->provider = new MiniProgramProvider([
            'appid' => $this->appid,
            'secret' => 'appsecret',
            'js_code' => 'JSCODE',
        ]);

    }

    public function tearDown()
    {
        m::close();
        parent::tearDown();
    }

    public function testAuthorizationUrl()
    {
        try {
            $url = $this->provider->getAuthorizationUrl();
        } catch (\Exception $e) {
            $this->assertInstanceOf('\LogicException', $e);
            $this->assertEquals('use wx.login(OBJECT) to get js_code',
                $e->getMessage());
        }
    }

    public function testGetResourceOwnerDetailsUrl()
    {
        try {
            $url = $this->provider->getResourceOwnerDetailsUrl(m::mock('League\OAuth2\Client\Token\AccessToken'));
        } catch (\Exception $e) {
            $this->assertInstanceOf('\LogicException', $e);
            $this->assertEquals('use wx.getUserInfo(OBJECT) to get ResourceOwnerDetails',
                $e->getMessage());
        }
    }

    public function testGetBaseAccessTokenUrl()
    {
        $params = [];

        $url = $this->provider->getBaseAccessTokenUrl($params);
        $uri = parse_url($url);

        $this->assertEquals('/sns/jscode2session', $uri['path']);
    }

    public function testGetAccessToken()
    {
        $response = m::mock('Psr\Http\Message\ResponseInterface');
        $response->shouldReceive('getBody')->andReturn('{"openid": "OPENID","session_key": "SESSIONKEY","unionid": "UNIONID"}');
        $response->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);

        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')->times(1)->andReturn($response);
        $this->provider->setHttpClient($client);

        /** @var AccessToken $token */
        $token = $this->provider->getAccessToken('authorization_code', ['js_code' => 'mock_js_code']);

        $this->assertEquals('SESSIONKEY', $token->getSessionKey());
        $this->assertEquals('OPENID', $token->getOpenId());
        $this->assertEquals('UNIONID', $token->getUnionId());
    }

    public function testUserData()
    {
        $openid = 'oGZUI0egBJY1zhBYw2KhdUfwVJJE';
        $nickname = 'Band';
        $sex = 1;
        $language = 'zh_CN';
        $province = 'Guangdong';
        $city = 'Guangzhou';
        $country = 'CN';
        $headImagurl = 'http://wx.qlogo.cn/mmopen/vi_32/aSKcBBPpibyKNicHNTMM0qJVh8Kjgiak2AHWr8MHM4WgMEm7GFhsf8OYrySdbvAMvTsw3mo8ibKicsnfN5pRjl1p8HQ/0';
        $unionid = 'ocMvos6NjeKLIBqg5Mr9QjxrP1FA';
        $timestamp = '1477314187';

        $rawData = 'RAWDATA';
        $signature = sha1($rawData.$this->sessionKey);
        $userInfo = \GuzzleHttp\json_encode(['userinfo'=>'userinfo']);

        $postResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $postResponse->shouldReceive('getBody')->andReturn('{"openid": "'.$openid.'","session_key": "'.$this->sessionKey.'","unionid": "'.$unionid.'"}');
        $postResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);


        $userResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $userResponse->shouldReceive('getBody')->andReturn('{"userInfo": '.$userInfo.',"rawData": "'.$rawData.'","signature": "'.$signature.'","encryptedData": "'.$this->encryptedData.'","iv": "'.$this->iv.'"}');
        $userResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);


        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')
            ->times(1)
            ->andReturn($postResponse, $userResponse);
        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', ['js_code' => 'mock_authorization_code']);

        $this->provider->setResponseUserInfo($userResponse->getBody());
        /** @var MiniProgramResourceOwner $user */
        $user = $this->provider->getResourceOwner($token);

        $this->assertEquals(\GuzzleHttp\json_decode($userInfo), $user->getResponseUserInfo()['userInfo']);
        $this->assertEquals($openid, $user->getDecryptData()->openId);
        $this->assertEquals($nickname, $user->getDecryptData()->nickName);
        $this->assertEquals($sex, $user->getDecryptData()->gender);
        $this->assertEquals($province, $user->getDecryptData()->province);
        $this->assertEquals($city, $user->getDecryptData()->city);
        $this->assertEquals($country, $user->getDecryptData()->country);
        $this->assertEquals($headImagurl, $user->getDecryptData()->avatarUrl);
        $this->assertEquals($unionid, $user->getDecryptData()->unionId);
        $this->assertEquals($language, $user->getDecryptData()->language);
        $this->assertEquals($timestamp, $user->getDecryptData()->watermark->timestamp);
        $this->assertEquals($this->appid, $user->getDecryptData()->watermark->appid);
    }

    public function testUserDataFails()
    {
        $errorPayloads = [
            '{"errcode":40029,"errmsg": "invalid code"}',
            '{"openid": "OPENID","session_key": "SESSIONKEY","unionid": "UNIONID"}'
        ];

        $testPayload = function ($payload) {
            $userResponse = m::mock('Psr\Http\Message\ResponseInterface');
            $userResponse->shouldReceive('getBody')->andReturn($payload);
            $userResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
            $userResponse->shouldReceive('getStatusCode')->andReturn(500);

            $client = m::mock('GuzzleHttp\ClientInterface');
            $client->shouldReceive('send')
                ->times(1)
                ->andReturn($userResponse);
            $this->provider->setHttpClient($client);

            try {
                $token = $this->provider->getAccessToken('authorization_code', ['js_code' => 'mock_authorization_code']);
                return false;
            } catch (\Exception $e) {
                $this->assertInstanceOf('\League\OAuth2\Client\Provider\Exception\IdentityProviderException', $e);
            }

            return $payload;
        };

        $this->assertCount(1, array_filter(array_map($testPayload, $errorPayloads)));
    }
}