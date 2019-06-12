<?php

/**
 * 顺丰开放平台快递类v1版
 * Date: 2017/11/7
 * Time: 10:21
 */
class SFExpress
{
    private $prod_host = 'https://open-prod.sf-express.com';//生产域名
    private $sbox_host = 'https://open-sbox.sf-express.com';//沙盒域名
    private $version = 'v1.0';//版本号
    private $sf_appid = '';
    private $sf_appkey = '';
    private $cusId = '0218399560';//顺丰月结卡号
    private $access_token;
    private $refresh_token;
    public function __construct()
    {
        $this->access_token = S('sf_access_token');
        if(empty($this->access_token)){
            $this->access_token = $this->get_access_token();
            S('sf_access_token', $this->access_token, 3500);
        }

    }

    /**
     * 获取token
     * @return array
     */
    public function get_access_token()
    {
        $url = '/public/'.$this->version.'/security/access_token';
        $transType = 300;
        $body = array();
        $response = $this->dealRequest($url, $body, $transType);
        if($response['head']['code'] == 'EX_CODE_OPENAPI_0200'){
            return $response['body']['accessToken'];
        }
    }

    /**
     * 刷新token
     * @param $access_token string
     * @param $refresh_token string
     * @return array
     */
    public function refresh_access_token($access_token, $refresh_token)
    {
        $baseUrl = '/public/'.$this->version.'/security/refresh_token/access_token/'.$access_token.'/refresh_token/'.$refresh_token;
        $url = $this->getRequestUrl($baseUrl);
        $transType = 302;
        $body = array();
        $response = $this->dealRequest($url, $body, $transType);
        if($response['head']['code'] == 'EX_CODE_OPENAPI_0200'){
            return $response['body']['accessToken'];
        }else{
            exit($response['head']['code'].$response['head']['message']);
        }
    }

    /**
     * 发送curl请求
     * @param $url string
     * @param $data array
     * @return array
     */
    public function curlApiPost($url, $data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HEADER,1);
        curl_setopt($ch, CURLOPT_TIMEOUT,5);
        curl_setopt($ch,CURLOPT_POST, true);
        $header = $this->FormatHeader($url,$data);
        curl_setopt($ch, CURLOPT_HTTPHEADER,$header);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
        curl_setopt($ch, CURLOPT_HEADER, 0);//返回response头部信息
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

    /**
     * 封装header头部信息
     * @param $url string
     * @param $data string
     * @return array
     */
    public function FormatHeader($url, $data){
        $temp = parse_url($url);
        $query = isset($temp['query']) ? $temp['query'] : '';
        $path = isset($temp['path']) ? $temp['path'] : '/';
        $header = array (
            "POST {$path}?{$query} HTTP/1.1",
            "Host: {$temp['host']}",
            "Content-Type: application/json",
            "Content-length: ".strlen($data),
            "Connection: Close"
        );
        return $header;
    }

    /**
     * 处理请求数据和请求结果数据
     * @param $url string
     * @param $body array 请求报文体
     * @param $transType string 请求编码
     * @return array
     */
    public function dealRequest($url, $body, $transType){
        if(!$transType){
            $ret['code'] = -10001;
            $ret['message'] = '请求编码参数异常';
            return $ret;
        }
        $head['transType'] = $transType;
        $head['transMessageId'] = $this->getTransMessageId();
        $data['head'] = $head;
        if($body){
            $data['body'] = $body;
        }
        $response = json_decode($this->curlApiPost($url, json_encode($data)), true);
        return $response;
    }

    /**
     * 快速下单
     * @param $data array
     * @return array
     */
    public function confirmOrder($data)
    {
        $baseUrl = '/rest/'.$this->version.'/order/access_token/'.$this->access_token;
        $url = $this->getRequestUrl($baseUrl);
        $transType = 200;
        $body = array();

        //基本信息
        $body['orderId'] = $data['orderId'];//*客户订单号，最大长度限于 56 位，不允许重复提交
        $body['expressType'] = $data['expressType']?$data['expressType']:1;//*1顺丰标快 2顺丰特惠 3电商特惠 5顺丰次晨 6顺丰即日 7电商速配 15生鲜速配
        $body['payMethod'] = $data['payMethod']?$data['payMethod']:1;//*1寄付现结（可不传 custId）/寄付月结【默认值】(必传custId) 2收方付 3第三方月结卡号支付
        $body['cusId'] = $this->cusId;//顺丰月结卡号
        $body['remark'] = $data['remark'];//备注

        //寄件方信息
        $body['deliverInfo']['company'] = $data['deliverCompany'];//寄件方公司
        $body['deliverInfo']['province'] = $data['deliverProvince'];//寄件方省份 “省”字不能省略
        $body['deliverInfo']['city'] = $data['deliverCity'];//寄件方所属城市名称，“市”不能省略
        $body['deliverInfo']['county'] = $data['deliverCounty'];//寄件方所在县区 “区字”不能省略
        $body['deliverInfo']['address'] = $data['deliverAddress'];//寄件方详细地址
        $body['deliverInfo']['contact'] = $data['deliverContact'];//寄件方联系人
        $body['deliverInfo']['tel'] = $data['deliverTel'];//寄件方手机

        //收件方信息
        $body['consigneeInfo']['company'] = $data['consignCompany'];//* 收件方公司
        $body['consigneeInfo']['province'] = $data['consignProvince'];//* 收件方省份 “省”字不能省略
        $body['consigneeInfo']['city'] = $data['consignCity'];//* 收件方所属城市名称，“市”不能省略
        $body['consigneeInfo']['county'] = $data['consignCounty'];//* 收件方所在县区 “区字”不能省略
        $body['consigneeInfo']['address'] = $data['consignAddress'];//* 收件方详细地址
        $body['consigneeInfo']['contact'] = $data['consignContact'];//* 收件方联系人
        $body['consigneeInfo']['tel'] = $data['consignTel'];//* 收件方手机

        //货物信息
        $body['cargoInfo']['parcelQuantity'] = $data['parcelQuantity'];//包裹数，默认为 1,一个包裹对应一个运单号，如果是大于1 个包裹，则返回按照子母件的方式返回母运单号和子运单号。
        $body['cargoInfo']['cargo'] = $data['cargo'];//货物名称，如果有多个货物，以英文逗号分隔，如：“手机,IPAD,充电器”
        $body['cargoInfo']['cargoCount'] = $data['cargoCount'];//货物数量，多个货物时以英文逗号分隔，且与货物名称一一对应如：2,1,3

        $response = $this->dealRequest($url, $body, $transType);
        return $response;
    }

    /**
     * 订单结果查询
     * @param $orderId string
     * @return array
     */
    public function getOrderResult($orderId)
    {
        $baseUrl = '/rest/'.$this->version.'/order/query/access_token/'.$this->access_token;
        $url = $this->getRequestUrl($baseUrl);
        $transType = 203;
        $body['orderId'] = $orderId;
        $response = $this->dealRequest($url, $body, $transType);
        return $response;
    }

    /**
     * 订单筛选：用于判断客户的收、派地址是否属于顺丰的收派范围
     * @param $data array
     * @return array
     */
    public function getOrderFilter($data)
    {
        $baseUrl = '/rest/'.$this->version.'/filter/access_token/'.$this->access_token;
        $url = $this->getRequestUrl($baseUrl);
        $transType = 204;
        $body = array();

        $body['deliverAddress'] = $data['deliverAddress'];
        $body['deliverProvince'] = $data['deliverProvince'];
        $body['deliverCity'] = $data['deliverCity'];
        $body['deliverCounty'] = $data['deliverCounty'];
        $body['deliverCountry'] = $data['deliverCountry'];

        $body['consigneeAddress'] = $data['consigneeAddress'];
        $body['consigneeProvince'] = $data['consigneeProvince'];
        $body['consigneeCity'] = $data['consigneeCity'];
        $body['consigneeCounty'] = $data['consigneeCounty'];
        $body['consigneeCountry'] = $data['consigneeCountry'];

        $response = $this->dealRequest($url, $body, $transType);
        return $response;
    }

    /**
     * 查询快递信息（路由）
     * @param $trackingNumber string 运单号
     * @return array
     */
    public function getExpressInfo($trackingNumber)
    {
        $baseUrl = '/rest/'.$this->version.'/route/query/access_token/'.$this->access_token;
        $url = $this->getRequestUrl($baseUrl);
        $transType = 501;
        $body['trackingType'] = 1;
        $body['trackingNumber'] = $trackingNumber;//运单号
        $body['methodType'] = 1;

        $response = $this->dealRequest($url, $body, $transType);
        return $response;
    }

    /**
     * 电子运单下载
     * @param $orderId string
     * @return array
     */
    public function getOrderWaybill($orderId)
    {
        $baseUrl = '/rest/'.$this->version.'/waybill/images/access_token/'.$this->access_token;
        $url = $this->getRequestUrl($baseUrl);
        $transType = 205;
        $body['orderId'] = $orderId;

        $response = $this->dealRequest($url, $body, $transType);
        return $response;
    }

    /**
     * 生成交易流水号
     * @return string
     */
    public function getTransMessageId()
    {
        return date('YmdHis', time()).sprintf('%04s', mt_rand(0,10000));
    }

    public function getRequestUrl($baseUrl)
    {
        return $this->sbox_host.$baseUrl.'/sf_appid/'.$this->sf_appid.'/sf_appkey/'.$this->sf_appkey;
    }

}