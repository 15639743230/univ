<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/20
 * Time: 9:57
 */

class RSASign
{

    private $public_key = '';
    private $private_key = '';
    public function __construct()
    {}

    public function setPublicKey($public_key)
    {
        $this->public_key = $public_key;
    }

    public function setPrivateKey($private_key)
    {
        $this->private_key = $private_key;
    }

    /**
     * 生成签名
     * @param    string     $signString 待签名字符串
     * @param    [type]     $priKey     私钥
     * @return   string     base64结果值
     */
    public function getSign($param,$priKey){
        $signString = $this->getSignString($param);
        $privKeyId = openssl_pkey_get_private($priKey);
        $signature = '';
        openssl_sign($signString, $signature, $privKeyId);
        openssl_free_key($privKeyId);
        return base64_encode($signature);
    }

    /**
     * 校验签名
     * @param    string     $pubKey 公钥
     * @param    string     $sign   签名
     * @param    string     $toSign 待签名字符串
     * @return   bool
     */
    public function checkSign($pubKey,$sign,$toSign){
        $publicKeyId = openssl_pkey_get_public($pubKey);
        $result = openssl_verify($toSign, base64_decode($sign), $publicKeyId);
        openssl_free_key($publicKeyId);
        return $result === 1 ? true : false;
    }


    /**
     * 获取待签名字符串
     * @param    array     $params 参数数组
     * @return   string
     */
    public function getSignString($params)
    {
        unset($params['sign']);
        ksort($params);
        reset($params);

        $pairs = array();
        foreach ($params as $k => $v) {
            if (!empty($v)) {
                $pairs[] = "$k=$v";
            }
        }

        return implode('&', $pairs);

    }

    //验签公钥
    public function redVerifykey()
    {
        //拼接验签路径
        $verifyKeyPath="D:/DEMO/verify.cer";
        $verifyKey4Server = file_get_contents($verifyKeyPath);

        $pem = chunk_split(base64_encode($verifyKey4Server),64,"\n");//转换为pem格式的公钥
        $pem = "-----BEGIN CERTIFICATE-----\n".$pem."-----END CERTIFICATE-----\n";
        $verifyKey = openssl_pkey_get_public($pem);
        return $verifyKey;
    }

    //公钥加密
    public function pubkeyEncrypt($source_data, $pu_key) {
        $data = "";
        $dataArray = str_split($source_data, 117);
        foreach ($dataArray as $value) {
            $encryptedTemp = "";
            openssl_public_encrypt($value,$encryptedTemp,$pu_key);//公钥加密
            $data .= base64_encode($encryptedTemp);
        }
        return $data;
    }

    //私钥解密
    public function pikeyDecrypt($eccryptData,$decryptKey) {
        $decrypted = "";
        $decodeStr = base64_decode($eccryptData);
        $enArray = str_split($decodeStr, 256);

        foreach ($enArray as $va) {
            openssl_private_decrypt($va,$decryptedTemp,$decryptKey);//私钥解密
            $decrypted .= $decryptedTemp;
        }
        return $decrypted;
    }

}