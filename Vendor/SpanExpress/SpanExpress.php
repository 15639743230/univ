<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/16
 * Time: 9:45
 */

class SpanExpress
{
    //下单接口   正式地址 https://openapi.ky-express.com/kyeopenapi/AppResourceService/ElectronicWaybillPlaceOrder
               //测试地址 http://testapi.ky-express.com/kyeopenapi/AppResourceService/ElectronicWaybillPlaceOrder
    //查询运单   正式地址 https://openapi.ky-express.com/kyeopenapi/iappWebService/QueryLogisticsYD
               //测试地址 http://testapi.ky-express.com/kyeopenapi/iappWebService/QueryLogisticsYD
    //获取子单号  正式地址 https://openapi.ky-express.com/kyeopenapi/AppResourceService/Acquiringsubsingle
               //测试地址 http://testapi.ky-express.com/kyeopenapi/AppResourceService/Acquiringsubsingle

    private $kye;//组织机构代码
    private $accesskey;//密钥
    private $key;//客户端编码
    private $customerKey;//客户编码,下单用的
    private $ky_order_url;//下单地址
    private $ky_get_status_url;//获取快递状态地址
    private $ky_get_child_status_url;//获取子单号地址
    private $post_data;

    public function __construct($param)
    {
        if(empty($param))
        {
            return json_encode(
                array('result' =>
                    array(
                        'errCode' => '-1',
                        'errMsg' => '无参数可传递'
                    ))
                , JSON_UNESCAPED_UNICODE);
        }

        $this->kye = C('KYE');
        $this->accesskey = C('ACCESS_KEY');
        $this->key = C('KEY');
        $this->customerKey = C('CUSTOMER_KEY');
        $this->ky_order_url = C('KY_ORDER_URL');
        $this->ky_get_status_url = C('KY_ROUTE_URL');
        $this->ky_get_child_status_url = C('KY_CHILD_URL');
        $this->post_data = $param;

    }


    /*-------------------------下单---------------------------------*/
    public function addOrder()
    {
        $body = array();

        //基本信息
        $body['key'] = $this->key;//客户端密钥，必填
        $body['clientid'] = $this->customerKey;//客户端编码，必填
        $body['data'][0]['orderid'] = $this->post_data['orderId'];//订单的id，必填
        $body['data'][0]['servicemode'] = $this->post_data['servicemode'];//服务方式，必填，1、当天达2、次日达3、隔日达4、同城即日5、同城次日6、陆运件7、省内次日8、省内即日（填文字）
        $body['data'][0]['paytype'] = $this->post_data['paytype'];//运单付款方式，必填，只能传：寄付，到付，寄付月结，到付月结，转第三方付款，预存运费（填文字）
        $body['data'][0]['isreturn'] = isset($this->post_data['isreturn']) && !empty($this->post_data['isreturn']) ? $this->post_data['isreturn'] : '0';//是否签回单，必填，传1：签回单（面单上打印’签回单’）传0：不签回单（面单上面不用打印）
        //$body['data'][0]['receiptnumber'] = $this->post_data['receiptnumber'];//回单份数，非必填，如果回单有多份，传具体签份数  比如：回单有三份，要签第2份，就传2
        $body['data'][0]['sendthings'] = $this->post_data['sendthings'];//托寄物，必填，

        $body['data'][0]['goodtime'] = $this->post_data['goodtime'];//货好时间，必填，注：填写预约司机上门取件时间
        $body['data'][0]['IsOrder'] = isset($this->post_data['IsOrder']) && !empty($this->post_data['IsOrder']) ? $this->post_data['IsOrder'] : '0';//是否下单，必填，传“1” OR “0” 传1表示:打印电子面单同时自动下单，传0表示:打印电子面单同时不需要自动下单
        $body['data'][0]['monthlycardno'] = $this->post_data['monthlycardno'];//付款账号/月结卡号，必填，注：付款方式为转寄付月结 OR 到付月结 OR 转第三方付款 OR 预存运费，需要填写月结卡号

        if(isset($this->post_data['remark']) && !empty($this->post_data['remark']))
        {
            $body['data'][0]['remark'] = $this->post_data['remark'];//备注，非必填，
        }
        else{
            $body['data'][0]['remark'] = '';
        }

        if(isset($this->post_data['sendnumber']) && !empty($this->post_data['sendnumber']))
        {
            $body['data'][0]['sendnumber'] = $this->post_data['sendnumber'];//件数，非必填，注：传订单货物包裹件数
        }
        else{
            $body['data'][0]['sendnumber'] = '';
        }

        if(isset($this->post_data['weight']) && !empty($this->post_data['weight']))
        {
            $body['data'][0]['weight'] = $this->post_data['weight'];//件数，非必填，注：传订单货物包裹件数
        }
        else{
            $body['data'][0]['weight'] = '';
        }

        if(isset($this->post_data['supportvalue']) && !empty($this->post_data['supportvalue']) && is_numeric($this->post_data['supportvalue']))
        {
            $body['data'][0]['supportvalue'] = $this->post_data['supportvalue'];;//保价值，非必填，
        }
        else{
            $body['data'][0]['supportvalue'] = '';
        }


        //寄件方
        $body['data'][0]['jjprovince'] = $this->post_data['jjprovince'];//寄件省，必填
        $body['data'][0]['jjcity'] = $this->post_data['jjcity'];//寄件市，必填
        $body['data'][0]['jjaddress'] = $this->post_data['jjaddress'];//详细寄件地址，必填
        $body['data'][0]['sendname'] = $this->post_data['sendname'];//寄件人姓名，必填
        $body['data'][0]['sendphone'] = $this->post_data['sendphone'];//寄件人手机，必填

        if(isset($this->post_data['sendtelprenum']) && !empty($this->post_data['sendtelprenum']))
        {
            $body['data'][0]['sendtelprenum'] = $this->post_data['sendtelprenum'];//寄件电话区号，非必填
        }
        else{
            $body['data'][0]['sendtelprenum'] = '';
        }

        if(isset($this->post_data['sendtel']) && !empty($this->post_data['sendtel']))
        {
            $body['data'][0]['sendtel'] = $this->post_data['sendtel'];//寄件电话，非必填
        }
        else{
            $body['data'][0]['sendtel'] = '';
        }

        if(isset($this->post_data['sendtelsubnum']) && !empty($this->post_data['sendtelsubnum']))
        {
            $body['data'][0]['sendtelsubnum'] = $this->post_data['sendtelsubnum'];//寄件电话分机号，非必填
        }
        else{
            $body['data'][0]['sendtelsubnum'] = '';
        }

        if(isset($this->post_data['sendcompany']) && !empty($this->post_data['sendcompany']))
        {
            $body['data'][0]['sendcompany'] = $this->post_data['sendcompany'];//寄件公司，非必填
        }
        else{
            $body['data'][0]['sendcompany'] = '';
        }

        if(isset($this->post_data['receipcompany']) && !empty($this->post_data['receipcompany']))
        {
            $body['data'][0]['receipcompany'] = $this->post_data['receipcompany'];//收件公司，非必填
        }
        else{
            $body['data'][0]['receipcompany'] = '';
        }


        //收件方
        $body['data'][0]['sjprovince'] = $this->post_data['sjprovince'];//收件省，必填
        $body['data'][0]['sjcity'] = $this->post_data['sjcity'];//收件市，必填
        $body['data'][0]['sjaddress'] = $this->post_data['sjaddress'];//详细收件地址，必填
        $body['data'][0]['receiptname'] = $this->post_data['receiptname'];//收件人姓名，必填
        $body['data'][0]['receiptphone'] = $this->post_data['receiptphone'];//收件人手机，必填

        if(isset($this->post_data['receiptelprenum']) && !empty($this->post_data['receiptelprenum']))
        {
            $body['data'][0]['receiptelprenum'] = $this->post_data['receiptelprenum'];//收件人电话区号，非必填
        }
        else{
            $body['data'][0]['receiptelprenum'] = '';
        }

        if(isset($this->post_data['receiptel']) && !empty($this->post_data['receiptel']))
        {
            $body['data'][0]['receiptel'] = $this->post_data['receiptel'];//收件电话，非必填
        }
        else
        {
            $body['data'][0]['receiptel'] = '';
        }

        if(isset($this->post_data['receiptelsubnum']) && !empty($this->post_data['receiptelsubnum']))
        {
            $body['data'][0]['receiptelsubnum'] = $this->post_data['receiptelsubnum'];//收件电话分机号，非必填
        }
        else
        {
            $body['data'][0]['receiptelsubnum'] = '';
        }

        if(isset($this->post_data['cod']) && !empty($this->post_data['cod']))
        {
            $body['data'][0]['cod'] = $this->post_data['cod'];//收件电话分机号，非必填
        }
        else
        {
            $body['data'][0]['cod'] = '';
        }

        //print_r($body);die;
        $express_data = json_encode($body,JSON_UNESCAPED_UNICODE);
        $header = array(
           'Content-Type:application/json',
           'kye:'.$this->kye,
           'access-token:'.$this->createAccessToken($express_data),
        );
        //echo $this->test_order_url;die;
        $res = $this->http_request($this->ky_order_url, $express_data, $header);

        return $res;
    }


    /*-------------------------查询快递信息--------------------------------*/
    public function getStatus()
    {
        $body = array();
        $body['key'] = $this->key;//客户编码
        $body['ydnumber'] = $this->post_data['ydnumber'];// $this->post_data;//快递单号，最多传入20个，用逗号隔开

        $express_data = json_encode($body,JSON_UNESCAPED_UNICODE);
        $header = array(
            'Content-Type:application/json',
            'kye:'.$this->kye,
            'access-token:'.$this->createAccessToken($express_data),
        );

        $res = $this->http_request($this->ky_get_status_url,$express_data, $header);
        return $res;
    }


    /*-----------------------------------获取子单号----------------------------------------------*/
    public function getChildStatus()
    {

        $body['key'] = $this->key;//客户密钥
        $body['mailno'] = $this->post_data['mailno'];//运单号
        $body['parcelquantity'] = $this->post_data['parcelquantity'];//包裹件数

        $header =  array(
            'Content-Type:application/json',
            'kye:'.$this->kye,
            'access-token:'.$this->createAccessToken(json_encode($body,JSON_UNESCAPED_UNICODE))
        );

        $express_data = json_encode($body,JSON_UNESCAPED_UNICODE);
        $res = $this->http_request($this->ky_get_child_status_url, $express_data, $header );
        return $res;
    }


    /*--------------------------生成签名所用的token------------注意编码格式必须和跨越一致----------------------------*/
    public function createAccessToken($arg)
    {
        $res = json_decode($arg,true);
        $token = '';
        //将所有参数升序排序
        ksort($res);

        //过滤掉一些空值,'',"",null
        foreach ($res as $key=>$val)
        {
            if(empty($res[$key])){
               continue;
            }

            if(is_array($val))
            {
                $val = json_encode($val,JSON_UNESCAPED_UNICODE);
            }
            $token .= $key.$val;
        }
         //echo $token;die;
        //拼接上之前的密钥,进行md5加密
        $new_token = strtoupper(md5($this->accesskey.$token));//echo $new_token;die;
        //echo $new_token;die;
        return $new_token;
    }



    /*-----------------校验签名---------------------*/
    public function checkSignature($header,$body)
    {
        $flag = false;
        $token = $this->createAccessToken($body);
        if(isset($header['access_token']) && !empty($header['access_token']) && $header['access_token'] === $token)
        {
            $flag = true;
        }
        return $flag;
    }


    /**
     * 发送curl请求
     * @param $url string
     * @param $xml string
     * @param $verifyCode string
     * @return array
     */
    public function http_request( $url, $data, $header)
    {
        //$result_data = file_get_contents('php://input');
        //print_r($result_data);
        //print_r($this->header);die;
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
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch);
        $res = curl_exec($ch);
        //echo curl_getinfo($ch, CURLINFO_HEADER_OUT);
        curl_close($ch);

        return $res;

    }




}