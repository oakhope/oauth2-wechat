<?php


namespace Oakhope\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use Oakhope\OAuth2\Client\Token\MiniProgram\AccessToken;
use Oakhope\OAuth2\Client\Support\MiniProgram\MiniProgramDataCrypt;

class MiniProgramResourceOwner implements ResourceOwnerInterface
{
    /** @var  AccessToken */
    protected $token;

    protected $appid;
    protected $responseUserInfo;
    protected $decryptData;

    public function __construct(array $response, $token, $appid)
    {
        $this->checkSignature($response, $token);
        $this->responseUserInfo = $response;
        $this->token = $token;
        $this->appid = $appid;

        if (!empty($response['encryptedData'])) {
            $this->decryptData = $this->decrypt();
        }
    }


    /**
     * @param $response
     * @param AccessToken $token
     * @throws \Exception
     */
    private function checkSignature($response, $token)
    {
        if ($response['signature'] !== sha1(
            $response['rawData'].$token->getSessionKey()
        )) {
            throw new IdentityProviderException('signature error', 0, $response);
        }
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    private function decrypt()
    {
        $dataCrypt = new MiniProgramDataCrypt(
            $this->appid,
            $this->token->getSessionKey()
        );
        $errCode = $dataCrypt->decryptData(
            $this->responseUserInfo['encryptedData'],
            $this->responseUserInfo['iv'],
            $data
        );

        if ($errCode == 0) {
            return $data;
        } else {
            throw new IdentityProviderException('decrypt error', $errCode, $this->responseUserInfo);
        }
    }

    /**
     * Returns the identifier of the authorized resource owner.
     *
     * @return mixed
     */
    public function getId()
    {
        return $this->decryptData ? $this->decryptData['openid'] : null;
    }

    public function getDecryptData()
    {
        return $this->decryptData;
    }

    public function getResponseUserInfo()
    {
        return $this->responseUserInfo;
    }

    /**
     * Return all of the owner details available as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->token->getValues();
    }
}
