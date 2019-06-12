<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/20
 * Time: 11:01
 */

class queryRoute
{

    private $param = array();

    public function getMethod()
    {
        return 'open.api.openCommon.queryRoute';
    }

    public function setParam($key,$val)
    {
        $this->param[$key] = $val;
    }

    public function getParam()
    {
        return json_encode($this->param,JSON_UNESCAPED_UNICODE);
    }

}