<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/20
 * Time: 11:01
 */

class queryOverZoneInfo
{

    private $param = array();

    public function getMethod()
    {
        return 'open.api.openCommon.queryOverZoneInfo';
    }

    public function setParam($key,$val)
    {
        $this->param[$key] = $val;
    }

    public function getParam()
    {
        return json_encode($this->param,JSON_UNESCAPED_UNICODE);
    }

    public function getHeader()
    {
        $arr = array("Content-Type" => "application/json","X-from" => "openapi_app");
        return json_encode($arr,JSON_UNESCAPED_UNICODE);
    }
}