<?php

/**
 * 顺丰BSP快递类v3.8版
 * Date: 2017/11/7
 * Time: 10:21
 */
class SFBSPExpress
{

    private $url;
    private $cusId;//顺丰月结卡号
    private $checkWord;//密匙
    private $apiCode;//接入编码
    protected $config = [
        'url' => 'http://bsp-oisp.sf-express.com/bsp-oisp/sfexpressService',
        'cusId' => '0218399560',
        'checkWord' => 'JiZLG2sv7mr4jOkpc2DN8M3L7EarBrZh',
        'apiCode' => 'SHYNWSWKJGFYXGS',
    ];

    /**
     * 不传参使用默认测试数据
     * SFBSPExpress constructor.
     * @param null $params
     */
    public function __construct($params = null)
    {
        if ($params != null) {
            $this->config = array_merge($this->config, $params);
        }
        $this->url = $this->config['url'];
        $this->cusId = $this->config['cusId'];
        $this->checkWord = $this->config['checkWord'];
        $this->apiCode = $this->config['apiCode'];
    }

    /**
     * 获取校验码verifyCode
     * @param  $xml string 完整的xml报文
     * @return string
     */
    public function getVerifyCode($xml)
    {
        $string = trim($xml) . trim($this->checkWord);
        $md5 = md5(mb_convert_encoding($string, 'UTF-8', mb_detect_encoding($string)), true);
        $verifyCode = base64_encode($md5);
        return $verifyCode;
    }

    /**
     * 拼接完整xml报文
     * @param $serviceName string
     * @param $bodyData string
     * @return string
     */
    public function buildXml($serviceName, $bodyData)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?><Request service="' . $serviceName . 'Service" lang="zh-CN">' .
            '<Head>' . $this->apiCode . '</Head>' .
            '<Body>' . $bodyData . '</Body>' .
            '</Request>';
        return $xml;
    }

    /**
     * 发送curl请求
     * @param $url string
     * @param $xml string
     * @param $verifyCode string
     * @return array
     */
    public function curlApiPost($url, $xml, $verifyCode)
    {

        $params = array(
            'xml' => $xml,
            'verifyCode' => $verifyCode
        );
        $paramBody = http_build_query($params, '', '&');
        $curlObj = curl_init();
        curl_setopt($curlObj, CURLOPT_URL, $url); // 设置访问的url
        curl_setopt($curlObj, CURLOPT_RETURNTRANSFER, 1); //curl_exec将结果返回,而不是执行
        curl_setopt($curlObj, CURLOPT_HTTPHEADER, array("Content-Type: application/x-www-form-urlencoded;charset=UTF-8"));
        curl_setopt($curlObj, CURLOPT_URL, $url);
        curl_setopt($curlObj, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curlObj, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curlObj, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);

        curl_setopt($curlObj, CURLOPT_CUSTOMREQUEST, 'POST');

        curl_setopt($curlObj, CURLOPT_POST, true);
        curl_setopt($curlObj, CURLOPT_POSTFIELDS, $paramBody);
        curl_setopt($curlObj, CURLOPT_ENCODING, 'gzip');

        $res = @curl_exec($curlObj);
        curl_close($curlObj);

        if ($res === false) {
            $errno = curl_errno($curlObj);
            if ($errno == CURLE_OPERATION_TIMEOUTED) {
                $msg = "Request Timeout:   seconds exceeded";
            } else {
                $msg = curl_error($curlObj);
            }
//            echo $msg;
            $e = new XN_TimeoutException($msg);
            throw $e;
        }
        return $res;
    }

    /**
     * 处理请求信息
     * @param $serviceName string
     * @param $bodyXml string
     * @return array|bool
     */
    public function doRequest($serviceName, $bodyXml)
    {

        $xml = $this->buildXml($serviceName, $bodyXml);
        $verifyCode = $this->getVerifyCode($xml);
        $this->saveFile($serviceName . '.xml', $xml);
        //数据请求,并校验数据结果返回
        $resXml = $this->curlApiPost($this->url, $xml, $verifyCode);
        /*if ($serviceName == 'Route') {
            $resXml = '<Response service="RouteService"><Head>OK</Head><Body><RouteResponse mailno="444003077898"><Route accept_time="2017-11-15 17:31:05" accept_address="上海" remark="顺丰速运 已收取快件" opcode="50"/><Route accept_time="2017-11-15 19:30:36" remark="快件在【上海浦东南汇营业点】装车，已发往下一站" opcode="922"/><Route accept_time="2017-11-16 01:19:32" remark="快件到达 【上海浦东集散中心2】" opcode="922"/><Route accept_time="2017-11-16 05:57:52" remark="快件在【上海浦东集散中心2】装车，已发往下一站" opcode="922"/><Route accept_time="2017-11-16 18:24:45" remark="快件到达 【济南历城集散中心】" opcode="922"/><Route accept_time="2017-11-16 22:04:49" remark="快件在【济南历城集散中心】装车，已发往下一站" opcode="922"/><Route accept_time="2017-11-17 06:55:27" remark="快件到达 【济南历下科院路营业部】" opcode="922"/><Route accept_time="2017-11-17 08:45:31" remark="快件交给罗继广，正在派送途中（联系电话：17852116028）" opcode="922"/><Route accept_time="2017-11-17 09:52:01" remark="已签收,感谢使用顺丰,期待再次为您服务" opcode="922"/></RouteResponse></Body></Response>';
        }*/
        $this->saveFile($serviceName . 'Response.xml', $resXml);
        $res = $this->LoadXml($resXml);
        return $res;
    }

    /**
     * 快速下单
     * @param $data array
     * @param $cargo
     * @param $addedService
     * @return array
     */
    public function placeOrder($data, $cargo = array(), $addedService = array())
    {
        $serviceName = 'Order';
        $body = array();

        //基本信息
        $body['orderid'] = $data['orderId'];//*客户订单号，最大长度限于 56 位，不允许重复提交
        $body['express_type'] = $data['express_type'] ? $data['express_type'] : 1;//*1顺丰标快 2顺丰特惠 3电商特惠 5顺丰次晨 6顺丰即日 7电商速配 15生鲜速配
        $body['pay_method'] = $data['pay_method'] ? $data['pay_method'] : 1;//*1寄付现结（可不传 custId）/寄付月结【默认值】(必传custId) 2收方付 3第三方月结卡号支付
        $body['custid'] = $this->cusId;//顺丰月结卡号
        $body['is_gen_bill_no'] = 1;//要求返回运单号
//        $body['is_docall'] = 1;//是否要求通过手持终端通知顺丰收派员收件：1：要求，其它为不要求
        $body['remark'] = $data['remark'];//备注

        //寄件方信息
        $body['j_company'] = $data['j_company'];//寄件方公司
        $body['j_province'] = $data['j_province'];//寄件方省份 “省”字不能省略
        $body['j_city'] = $data['j_city'];//寄件方所属城市名称，“市”不能省略
        $body['j_county'] = $data['j_county'];//寄件方所在县区 “区字”不能省略
        $body['j_address'] = $data['j_address'];//寄件方详细地址
        $body['j_contact'] = $data['j_contact'];//寄件方联系人
        $body['j_tel'] = $data['j_tel'];//寄件方联系电话
        $body['j_mobile'] = $data['j_mobile'];//寄件方手机

        //收件方信息
        $body['d_company'] = $data['d_company'];//* 收件方公司
        $body['d_province'] = $data['d_province'];//* 收件方省份 “省”字不能省略
        $body['d_city'] = $data['d_city'];//* 收件方所属城市名称，“市”不能省略
        $body['d_county'] = $data['d_county'];//* 收件方所在县区 “区字”不能省略
        $body['d_address'] = $data['d_address'];//* 收件方详细地址
        $body['d_contact'] = $data['d_contact'];//* 收件方联系人
        $body['d_tel'] = $data['d_tel'];//* 收件方联系电话
        $body['d_mobile'] = $data['d_mobile'];//* 收件方手机

        //货物信息
        $body['parcel_quantity'] = $data['parcel_quantity'];//包裹数，默认为 1,一个包裹对应一个运单号，如果是大于1 个包裹，则返回按照子母件的方式返回母运单号和子运单号。

        $bodyXml = '<Order ';

        foreach ($body as $k => $v) {
            $bodyXml .= $k . '=' . '"' . $v . '" ';
        }

        if (count($cargo) > 0 || count($addedService) > 0) {
            $bodyXml = trim($bodyXml) . '>';
            if (is_array($cargo) && count($cargo) > 0) {
                $bodyXml .= $this->Cargo($cargo);
            }
            if (is_array($addedService) && count($addedService) > 0) {
                $bodyXml .= $this->AddedService($addedService);
            }
            $bodyXml .= '</Order>';
        } else {
            $bodyXml .= ' />';
        }

        $response = $this->doRequest($serviceName, $bodyXml);
        return $response;
    }

    /**
     * 生成货物信息xml
     * @param $cargo array
     * @return string
     */
    public function Cargo($cargo)
    {
        $data = '';
        if (count($cargo) > 0) {
            foreach ($cargo as $item) {
                if (count($item) > 0) {
                    $root = '<Cargo ';
                    foreach ($item as $k => $v) {
                        $root .= $k . '="' . $v . '" ';
                    }
                    $root .= '></Cargo>';
                    $data .= $root;
                }
            }
        }
        return $data;
    }

    /**
     * 生成附加服务信息xml
     * @param $AddedService array
     * @return string
     */
    public function AddedService($AddedService)
    {
        $data = '';
        if (count($AddedService) > 0) {
            foreach ($AddedService as $item) {
                if (count($item) > 0) {
                    $root = '<AddedService ';
                    foreach ($item as $k => $v) {
                        $root .= $k . '="' . $v . '" ';
                    }
                    $root .= '></AddedService>';
                    $data .= $root;
                }
            }
        }
        return $data;
    }

    /**
     * 订单结果查询
     * @param $orderId string
     * @return array
     */
    public function getOrderResult($orderId)
    {
        $serviceName = 'OrderSearch';
        $xml = '<OrderSearch orderid="' . $orderId . '"/>';

        $response = $this->doRequest($serviceName, $xml);
        return $response;
    }


    /**
     * 申请子单号
     * @param $orderId string 客户订单号
     * @param $parcel_quantity 新增加的包裹数，最大20
     * @return array
     */
    public function getOrderChildNumber($orderId, $parcel_quantity)
    {
        $serviceName = 'OrderZD';
        $xml = '<OrderZD orderid="' . $orderId . '" ';
        $xml .= 'parcel_quantity="' . $parcel_quantity . '"/>';
        $response = $this->doRequest($serviceName, $xml);
        return $response;
    }


    /**
     * 该接口用于：
     *  客户在确定将货物交付给顺丰托运后，将运单上的一些重要信息，如快件重量通过此接口发送给顺丰。
     *  客户在发货前取消订单。
     * 注意：订单取消之后，订单号也是不能重复利用的。
     * @param $orderId string
     * @param $mailNo string
     * @param $dealType bool 1-确认 2-取消
     * @param array $options
     * @return array|bool
     */
    public function orderConfirmRequest($orderId, $mailNo, $dealType, $options = array())
    {
        $serviceName = 'OrderConfirm';
        $params = array();
        $params['orderid'] = $orderId;
        $params['mailno'] = $mailNo;
        $params['dealType'] = $dealType;

        $xml = '<OrderConfirm ';
        foreach ($params as $key => $val) {
            $xml .= $key . '=' . '"' . $val . '" ';
        }
        $xml = trim($xml) . '>';
        if (count($options) > 0) {
            $xml .= $this->orderConfirmOption($options);
        }
        $xml .= '</OrderConfirm>';

        $response = $this->doRequest($serviceName, $xml);
        return $response;
    }

    /**
     * 生成货物信息xml
     * @param $options
     * @return string
     */
    public function orderConfirmOption($options)
    {
        $xml = '';
        if (count($options) > 0) {
            foreach ($options as $item) {
                if (count($item) > 0) {
                    $root = '<OrderConfirmOption ';
                    foreach ($item as $k => $v) {
                        $root .= $k . '="' . $v . '" ';
                    }
                    $root .= '/>';
                    $xml .= $root;
                }
            }
        }
        return $xml;
    }

    /**
     * 查询快递信息（路由）
     * @param $trackingNumber string 运单号
     * @return array
     */
    public function getExpressRoute($trackingNumber)
    {
        $serviceName = 'Route';
        $xml = '<RouteRequest tracking_type="1" method_type="1" tracking_number=' . '"' . $trackingNumber . '"/>';

        $response = $this->doRequest($serviceName, $xml);
        return $response;
    }

    /**
     * @param $xml
     * @return array|mixed
     */
    protected function LoadXml($xml)
    {
        $obj = new DOMDocument();
        $obj->loadXML($xml);
        $ret = $this->xmlToArray($obj->documentElement);
        return $ret;
    }

    /**
     * @param $root
     * @return array|mixed
     */
    public function xmlToArray($root)
    {
        $result = array();

        if ($root->hasAttributes()) {
            $attrs = $root->attributes;
            foreach ($attrs as $attr) {
                $result[$attr->name] = $attr->value;
            }
        }

        if ($root->hasChildNodes()) {
            $children = $root->childNodes;
            if ($children->length == 1) {
                $child = $children->item(0);
                if ($child->nodeType == XML_TEXT_NODE) {
                    $result['_value'] = $child->nodeValue;
                    return count($result) == 1
                        ? $result['_value']
                        : $result;
                }
            }
            $groups = array();
            foreach ($children as $child) {
                if (!isset($result[$child->nodeName])) {
                    $result[$child->nodeName] = self::xmlToArray($child);
                } else {
                    if (!isset($groups[$child->nodeName])) {
                        $result[$child->nodeName] = array($result[$child->nodeName]);
                        $groups[$child->nodeName] = 1;
                    }
                    $result[$child->nodeName][] = self::xmlToArray($child);
                }
            }
        }

        return $result;
    }

    public function saveFile($fileName, $text)
    {
        if (!$fileName || !$text)
            return false;

        if (self::makeDir(dirname($fileName))) {
            if ($fp = fopen($fileName, "w")) {
                if (@fwrite($fp, $text)) {
                    fclose($fp);
                    return true;
                } else {
                    fclose($fp);
                    return false;
                }
            }
        }
        return false;
    }

    private static function makeDir($dir, $mode = "0777")
    {
        if (!$dir) return false;
        if (!file_exists($dir)) {
            return mkdir($dir, $mode, true);
        } else {
            return true;
        }
    }

}