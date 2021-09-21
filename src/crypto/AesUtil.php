<?php


namespace Maoxp\Tool\crypto;


class AesUtil
{
    /**
     * @var string $iv
     */
    protected $iv;

    /**
     * @var string $method
     */
    protected $method;

    /**
     * AesUtil constructor.
     * @param string $method
     */
    public function __construct(string $method)
    {
        if (empty($method)) {
            $this->method = $method = 'AES-128-CBC';
        }
        $this->method = $method;

        if (!in_array($method, ['AES-128-CBC', 'AES-256-ECB'], true)) {
            throw new \RuntimeException('method has problem.');
        }
        $ivLength = openssl_cipher_iv_length($method);
        if (!isset($this->iv)) {
//            $this->iv = str_repeat(chr(0), $ivLength);
            $this->iv = openssl_random_pseudo_bytes($ivLength);;
        }
    }

    /**
     * 数据加密未采用PKCS#7填充
     *
     * @param string $data
     * @param string $key
     * @return string
     */
    public function encrypt(string $data, string $key): string
    {
        return openssl_encrypt($data, $this->method, $key, 0, $this->iv);
    }

    /**
     * aes解密
     *
     * @param string $data
     * @param string $key
     * @return string
     */
    public function decrypt(string $data, string $key): string
    {
        return openssl_decrypt($data, $this->method, $key, 0, $this->iv);
    }
}