<?php

/**
 * 接口控制器基类
 *
 * @author sin
 *
 */

namespace app\common\controller;

class Api extends Base
{
    protected $userId = 0;                        //用户id
    protected function initialize()
    {
        //解决跨域问题
        header('Access-Control-Allow-Origin:*');//允许所有来源访问
        header('Access-Control-Allow-Method:POST, GET, PUT, OPTIONS, DELETE');//允许访问的方式
        header("Access-Control-Allow-Headers: Origin, No-Cache, X-Requested-With, If-Modified-Since, Pragma, Last-Modified, Cache-Control, Expires, Content-Type, X-E4M-With");
        error_reporting(E_ERROR | E_WARNING | E_PARSE);
        parent::initialize();
        //配置统一入口，只让访问init方法
        if(request()->module()!= 'api' && request()->controller() != 'Index' && request()->action() != 'index'){
            die('error');
        }


    }
    //此方法用于设置参数
    public function setInit($user_id)
    {
        $this->userId = $user_id;
        return true;
    }
}
