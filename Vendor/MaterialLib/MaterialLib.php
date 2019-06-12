<?php

class MaterialLib
{
    private $post_data;

    public function __construct($param)
    {
        if(empty($param))
        {
            return json_encode(
                array('result' =>
                    array(
                        'errCode' => '-10001',
                        'errMsg' => '库位号不能为空'
                    ))
                , JSON_UNESCAPED_UNICODE);
        }

        $this->post_data = $param;
    }


    /*--------------------指令------------------------*/
    public function lightUp()
    {
        $hostInfo = D2('ElectronicLibrary/ElectronicLibrary')->getInfo();
        $host = $hostInfo['host'].':'.$hostInfo['port'];
        $url = 'http://'.$host.'/GoTopInterface.ashx?json=OrderListA&Jsonstring=';
        $express_data = json_encode($this->post_data,JSON_UNESCAPED_UNICODE);
        $res = $this->http_request($url, $express_data);
    }



    public function http_request( $url, $data, $header='')
    {
        if( empty( $url ) ){
            return false;
        }

        if( empty( $data ) ){
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
        //curl_setopt($ch, CURLOPT_HEADER, 1);//请求返回来的header
        //curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch);
        $res = curl_exec($ch);
        curl_close($ch);

        return $res;
    }




}