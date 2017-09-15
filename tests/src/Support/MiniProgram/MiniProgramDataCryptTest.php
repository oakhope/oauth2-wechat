<?php


namespace Oakhope\OAuth2\Client\Test\Support\MiniProgram;

use Mockery as m;
use Oakhope\OAuth2\Client\Support\MiniProgram\MiniProgramDataCrypt;

class MiniProgramDataCryptTest extends \PHPUnit_Framework_TestCase
{
    private $appid;
    private $sessionKey;
    private $encryptedData;
    private $iv;

    protected function setUp()
    {
        $this->appid = 'wx4f4bc4dec97d474b';
        $this->sessionKey = 'tiihtNczf5v6AKRyjwEUhQ==';
        $this->encryptedData = "CiyLU1Aw2KjvrjMdj8YKliAjtP4gsMZM
                QmRzooG2xrDcvSnxIMXFufNstNGTyaGS
                9uT5geRa0W4oTOb1WT7fJlAC+oNPdbB+
                3hVbJSRgv+4lGOETKUQz6OYStslQ142d
                NCuabNPGBzlooOmB231qMM85d2/fV6Ch
                evvXvQP8Hkue1poOFtnEtpyxVLW1zAo6
                /1Xx1COxFvrc2d7UL/lmHInNlxuacJXw
                u0fjpXfz/YqYzBIBzD6WUfTIF9GRHpOn
                /Hz7saL8xz+W//FRAUid1OksQaQx4CMs
                8LOddcQhULW4ucetDf96JcR3g0gfRK4P
                C7E/r7Z6xNrXd2UIeorGj5Ef7b1pJAYB
                6Y5anaHqZ9J6nKEBvB4DnNLIVWSgARns
                /8wR2SiRS7MNACwTyrGvt9ts8p12PKFd
                lqYTopNHR1Vf7XjfhQlVsAJdNiKdYmYV
                oKlaRv85IfVunYzO0IKXsyl7JCUjCpoG
                20f0a04COwfneQAGGwd5oa+T8yO5hzuy
                Db/XcxxmK01EpqOyuxINew==";
        $this->iv = 'r7BXXKkLb8qrSNn05n0qiA==';
    }

    public function tearDown()
    {
        m::close();
        parent::tearDown();
    }

    public function testDecryptData()
    {
        $dataCrypt = new MiniProgramDataCrypt($this->appid, $this->sessionKey);
        $errCode = $dataCrypt->decryptData($this->encryptedData, $this->iv, $data);

        $this->assertEquals(0, $errCode);
        $this->assertEquals('oGZUI0egBJY1zhBYw2KhdUfwVJJE', $data->openId);
        $this->assertEquals('Band', $data->nickName);
        $this->assertEquals(1, $data->gender);
        $this->assertEquals('zh_CN', $data->language);
        $this->assertEquals('Guangzhou', $data->city);
        $this->assertEquals('Guangdong', $data->province);
        $this->assertEquals('CN', $data->country);
        $this->assertEquals('http://wx.qlogo.cn/mmopen/vi_32/aSKcBBPpibyKNicHNTMM0qJVh8Kjgiak2AHWr8MHM4WgMEm7GFhsf8OYrySdbvAMvTsw3mo8ibKicsnfN5pRjl1p8HQ/0', $data->avatarUrl);
        $this->assertEquals('ocMvos6NjeKLIBqg5Mr9QjxrP1FA', $data->unionId);
        $this->assertEquals('1477314187', $data->watermark->timestamp);
        $this->assertEquals('wx4f4bc4dec97d474b', $data->watermark->appid);
    }
}