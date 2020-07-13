<?php

namespace app\common\controller;

class Wechat extends Base
{
    public function index()
    {
        header("Content-type: text/html; charset=utf-8");
        echo '重庆百家文化直播联盟欢迎您';exit();
    }
}