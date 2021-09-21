<?php


namespace Maoxp\Tool\core\util;


/**
 *
 * Class MiniProgram
 * @link https://developers.weixin.qq.com/miniprogram/dev/api-backend/open-api/access-token/auth.getAccessToken.html
 * @package Maoxp\Tool\core\util
 */
class MinProgramUtil
{
    protected const code2SessionUrl = 'https://api.weixin.qq.com/sns/jscode2session?appid=APPID&secret=SECRET&js_code=JSCODE&grant_type=authorization_code';
    protected const getAccessTokenUrl = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=APPID&secret=APPSECRET';
    protected const appID = 'wx3ce0353dd213b065';
    protected const appSecret = 'a5ad39c07dab6df458f9b6e0f80810a9';

    /**
     * error code 说明.
     * <ul>
     *    <li>-41000: session_key 非法</li>
     *    <li>-41001: encodingAesKey 非法</li>
     *    <li>-41003: aes 解密失败</li>
     *    <li>-41004: 解密后得到的buffer非法</li>
     *    <li>-41005: base64加密失败</li>
     *    <li>-41016: base64解密失败</li>
     * </ul>
     */
    public static $OK = 0;
    public static $IllegalSessionKey = -41000;
    public static $IllegalAesKey = -41001;
    public static $IllegalIv = -41002;
    public static $IllegalBuffer = -41003;
    public static $DecodeBase64Error = -41004;

    /**
     * expired 2 个小时
     * @var null
     */
    public $accessToken = null;
    /**
     * default 7200 second
     * @var int
     */
    protected $during = 7200;

    public function __construct(string $config = '')
    {
        if (empty($config)) {
            $this->init();
        }
    }

    protected function init(): void
    {
        $url = str_replace(['APPID', 'APPSECRET'], [self::appID, self::appSecret], self::getAccessTokenUrl);
        $response = CurlUtil::get($url, null, ['Content-Type: application/json']);
        $body = json_decode($response['body'], true);

        if (isset($body['errcode']) && $body['errcode'] !== 0) {
            throw new \RuntimeException("errcode: {$body['errcode']}, errorMsg: {$body['errmsg']}");
        }
        $this->accessToken = $body['access_token'];
        $this->during = $body['expires_in'];
    }

    /**
     * 登录凭证校验
     *
     * @param string $code
     * @return array|null
     * @throws \Exception
     */
    public function CertifyVerifyWithLogin(string $code): ?array
    {
        $url = str_replace(['APPID', 'SECRET', 'JSCODE'], [self::appID, self::appSecret, $code], self::code2SessionUrl);
        $response = CurlUtil::get($url, null, ['Content-Type: application/json']);
        $body = json_decode($response['body'], true);
        if (isset($body['errcode']) && $body['errcode'] !== 0) {
            throw new \RuntimeException("errcode: {$body['errcode']}, errorMsg: {$body['errmsg']}");
        }

        return [
            'session_key' => $body['session_key'],  //会话密钥
            'openid' => $body['openid'],            //用户唯一标识
            'unionid' => $body['unionid'] ?? '',    //用户在开放平台的唯一标识符
        ];
    }

    /**
     * 解密账号个人信息
     *
     * @param string $sessionKey
     * @param string $encryptedData
     * @param string $iv
     * @param array $data
     * @return int
     */
    public function wxBizDataCrypt(string $sessionKey, string $encryptedData, string $iv, array &$data): int
    {
        if (empty($sessionKey)) {
            return self::$IllegalSessionKey;
        }
        if (strlen($sessionKey) !== 24) {
            return self::$IllegalAesKey;
        }
        if (strlen($iv) !== 24) {
            return self::$IllegalIv;
        }

        $aesKey = base64_decode($sessionKey);
        $aesIV = base64_decode($iv);
        $aesCipher = base64_decode($encryptedData);

        //算法为 AES-128-CBC，数据采用PKCS#7填充
        $result = openssl_decrypt($aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV);

        $dataObj = json_decode($result, true);
        if ($dataObj === NULL) {
            return self::$IllegalBuffer;
        }
        if ($dataObj['watermark']['appid'] !== self::appID) {
            return self::$IllegalBuffer;
        }
        $data = $dataObj;
        return self::$OK;
    }
}