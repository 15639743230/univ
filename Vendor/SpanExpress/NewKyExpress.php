<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/16
 * Time: 9:45
 */

class NewKyExpress
{
    private $common_url = '';//统一使用的方法，生产环境
    private $token_url = '';//获取token的url
    private $refresh_token_url = '';//刷新token
    private $app_key = '';
    private $app_secret = '';
    private $token;

    public function __construct($param = array())
    {
        $ky_new_info = C('NEW_KY_INFO');
        $this->common_url = $ky_new_info['common_url'];
        $this->token_url = $ky_new_info['token_url'];
        $this->refresh_token_url = $ky_new_info['refresh_token_url'];
        $this->app_key = $ky_new_info['app_key'];
        $this->app_secret = $ky_new_info['app_secret'];
        $this->token = $this->getToken();
    }

    public function getHeader($request)
    {
        $header = array(
            "token:".$this->token,
            'sign:',
            'appkey:'.$this->app_key,
            'method:'.$request->getMethod(),
            'timestamp:'.time(),
            'format:json',
            'Content-Type:application/json',
        );
        return $header;
    }

    public function executeRequest($request)
    {
        $header = $this->getHeader($request);
        $param = $request->getParam();
        $res = $this->http_request($this->common_url,$param,$header);
        $res = json_decode($res,true);
        return $res;
    }

    //获得token值
    public function getToken()
    {
        $ky_token_info = D2('SpanExpress/KyToken')->getInfo();
        if(!empty($ky_token_info))
        {
            if(($ky_token_info['expire_time'] + $ky_token_info['add_time'] + 20) < time())
            {
                $access_token = $this->createToken();
                if($access_token['code'] == 0 && !empty($access_token['data']))
                {
                    //重新入库
                    $token = $access_token['data']['token'];
                    $save_data['token'] = $token;
                    $save_data['refresh_token'] = $access_token['data']['refresh_token'];
                    $save_data['add_time'] = time();
                    $save_data['expire_time'] = $access_token['data']['expire_time'];
                    D2('SpanExpress/KyToken')->updateData(array('id' => $ky_token_info['id']),$save_data);
                    return $token;
                }
                else
                {
                    return null;
                }
            }
            else
            {
                return $ky_token_info['token'];
            }
        }

        $access_token = $this->createToken();
        if($access_token['code'] == 0 && !empty($access_token['data']))
        {
            //重新入库
            $token = $access_token['data']['token'];
            $add_data['token'] = $token;
            $add_data['refresh_token'] = $access_token['data']['refresh_token'];
            $add_data['add_time'] = time();
            $add_data['expire_time'] = $access_token['data']['expire_time'];
            D2('SpanExpress/KyToken')->addData($add_data);
            return $token;
        }
        else
        {
            return null;
        }
    }

    //生成token值
    public function createToken()
    {
        $param['appkey'] = $this->app_key;
        $param['appsecret'] = $this->app_secret;
        $header = array("Content-Type:application/json","X-from:openapi_app");
        $url = $this->token_url;
        $res = $this->http_request($url,json_encode($param),$header);
        $res = json_decode($res,true);
        return $res;
    }

    //刷新token
    public function refreshToken($refreshToken)
    {
        $header = array("Content-Type:application/json","X-from:openapi_app");
        $param['refresh_token'] = $refreshToken;
        $res = $this->http_request($this->refresh_token_url,json_encode($param),$header);
        $res = json_decode($res,true);
        return $res;
    }

    /*-----------------获得签名---------------------*/
    public function getSign($param)
    {
        //先得到跨越公钥
      /*  $ky_public_key = '';
        vendor('SpanExpress.RSASign');
        $signObj = new RSASign();
        $sign = $signObj->getSign($param,$ky_public_key);
        return $sign;*/

      $signs = $this->app_secret.time();
      $params = '';
      foreach ($param as $key => $val)
      {
          $params .= $key.$val;
      }
      return strtoupper(md5($signs.$params));
    }


    /**
     * 发送curl请求
     * @param $url string
     * @param $xml string
     * @param $verifyCode string
     * @return array
     */
    public function http_request($url, $data, $header)
    {
        if(empty($url))
        {
            return false;
        }

        if(empty($data))
        {
            return false;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        curl_setopt($ch, CURLINFO_HEADER_OUT, true);//开启追踪请求头信息，返回发送的header
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch);
        $res = curl_exec($ch);
        curl_close($ch);

        return $res;
    }

}