<?php


namespace Oakhope\OAuth2\Client\Support\Common;

class AESEncoder
{
    /**
     * @param string $text
     * @param string $key
     * @param string $iv
     * @param int    $option
     *
     * @return string
     */
    public static function encrypt($text, $key, $iv, $option = OPENSSL_RAW_DATA)
    {
        self::validateKey($key);
        self::validateIv($iv);

        return openssl_encrypt($text, self::getMode($key), $key, $option, $iv);
    }

    /**
     * @param string $cipherText
     * @param string $key
     * @param string $iv
     * @param int    $option
     *
     * @return string
     */
    public static function decrypt($cipherText, $key, $iv, $option = OPENSSL_RAW_DATA)
    {
        self::validateKey($key);
        self::validateIv($iv);

        return openssl_decrypt($cipherText, self::getMode($key), $key, $option, $iv);
    }

    /**
     * @param string $key
     *
     * @return string
     */
    public static function getMode($key)
    {
        return 'aes-'.(8 * strlen($key)).'-cbc';
    }

    /**
     * @param string $key
     */
    public static function validateKey($key)
    {
        if (!in_array(strlen($key), [16, 24, 32], true)) {
            throw new \InvalidArgumentException(
                sprintf('Key length must be 16, 24, or 32 bytes; got key len (%s)
                .', strlen($key))
            );
        }
    }

    /**
     * @param string $iv
     *
     * @throws \InvalidArgumentException
     */
    public static function validateIv($iv)
    {
        if (strlen($iv) !== 16) {
            throw new \InvalidArgumentException('IV length must be 16 bytes.');
        }
    }
}
