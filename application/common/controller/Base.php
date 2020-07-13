<?php

/**
 * 控制器基类
 *
 * @author sin
 *
 */

namespace app\common\controller;

use think\Controller;

class Base extends Controller
{

    protected function initialize()
    {
        //解决跨域问题
        header('Access-Control-Allow-Origin:*');//允许所有来源访问
        header('Access-Control-Allow-Method:POST, GET, PUT, OPTIONS, DELETE');//允许访问的方式
        header("Access-Control-Allow-Headers: Origin, No-Cache, X-Requested-With, If-Modified-Since, Pragma, Last-Modified, Cache-Control, Expires, Content-Type, X-E4M-With");
        parent::initialize();
        error_reporting(E_ALL ^ E_NOTICE);              //错误等级
        //初始化配置参数，用于在模板中使用
        $this->assign('params',config('params.'));
    }

}
