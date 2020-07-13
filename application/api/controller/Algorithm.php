<?php
// +----------------------------------------------------------------------
// | 研究员：rz-------->
// | email:torucc@foxmail.com
// | blog:http://blog.rziqee.cn
// +----------------------------------------------------------------------
// | Copyright (c) 2020  All rights reserved.
// +----------------------------------------------------------------------
// | Author: rz <torucc@foxmail.com>
// +----------------------------------------------------------------------
namespace app\api\controller;
use app\common\controller\Api;
use think\Db;
class Algorithm extends Api
{

    public function posterInfo(){
        $input = input('param.');
        $uid = $input['userId'];
        if(empty($uid)){
            return result('请登录',400);
        }
        $info = Db::name('user_poster')->where(['user_id'=>$uid])->field('ads1,ads2,logo')->find();
        if(empty($info)){
            $info = [];
        }
        return result('成功',$info,true);
    }
}