<?php
// 应用公共文件
use think\Cache;
use think\Config;
use think\Cookie;
use think\Db;
use think\Debug;
use think\exception\HttpException;
use think\exception\HttpResponseException;
use think\Lang;
use think\Loader;
use think\Log;
use think\Model;
use think\Request;
use think\Response;
use think\Session;
use think\Url;
use think\View;
use think\Container;
use app\common\model\Operation;
use app\common\model\Area;
use app\common\model\Payments;
use app\common\model\Logistics;

//毫秒数
//返回当前的毫秒时间戳
function msectime() {
    list($tmp1, $tmp2) = explode(' ', microtime());
    return sprintf('%.0f', (floatval($tmp1) + floatval($tmp2)) * 1000);
}
/**
 * 获取客户端IP地址
 * @param integer $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
 * @param boolean $adv 是否进行高级模式获取（有可能被伪装）
 * @return mixed
 */
function get_client_ip($type = 0,$adv=false) {
    $type       =  $type ? 1 : 0;
    static $ip  =   NULL;
    if ($ip !== NULL) return $ip[$type];
    if($adv){
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $arr    =   explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $pos    =   array_search('unknown',$arr);
            if(false !== $pos) unset($arr[$pos]);
            $ip     =   trim($arr[0]);
        }elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip     =   $_SERVER['HTTP_CLIENT_IP'];
        }elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip     =   $_SERVER['REMOTE_ADDR'];
        }
    }elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip     =   $_SERVER['REMOTE_ADDR'];
    }
    // IP地址合法验证
    $long = sprintf("%u",ip2long($ip));
    $ip   = $long ? array($ip, $long) : array('0.0.0.0', 0);
    return $ip[$type];
}

//判断前端浏览器类型
function get_client_broswer(){
    $ua = $_SERVER['HTTP_USER_AGENT'];

    //微信内置浏览器
    if (stripos($ua, 'MicroMessenger')) {
        //preg_match('/MicroMessenger\/([\d\.]+)/i', $ua, $match);
        return "weixin";
    }
    //支付宝内置浏览器
    if (stripos($ua, 'AlipayClient')) {
        //preg_match('/AlipayClient\/([\d\.]+)/i', $ua, $match);
        return "alipay";
    }
    return false;
}
//生成编号
function get_sn($type){
    switch ($type)
    {
        case 1:         //订单编号
            $str = $type.substr(msectime().rand(0,9),1);
            break;
        case 2:         //支付单编号
            $str = $type.substr(msectime().rand(0,9),1);
            break;
        case 3:         //商品编号
            $str = 'G'.substr(msectime().rand(0,5),1);
            break;
        case 4:         //货品编号
            $str = 'P'.substr(msectime().rand(0,5),1);
            break;
        case 5:         //售后单编号
            $str = $type.substr(msectime().rand(0,9),1);
            break;
        case 6:         //退款单编号
            $str = $type.substr(msectime().rand(0,9),1);
            break;
        case 7:         //退货单编号
            $str = $type.substr(msectime().rand(0,9),1);
            break;
        case 8:         //发货单编号
            $str = $type.substr(msectime().rand(0,9),1);
            break;
        case 9:         //提货单号
            $str = 'T'.$type.substr(msectime().rand(0,5), 1);
            break;
        case 10:         //提现流水号
            $str = 'W'.$type.substr(msectime().rand(0,5), 1);
            break;
        default:
            $str = substr(msectime().rand(0,9),1);
    }
    return $str;
}


/**
 * 获取hash值
 * @return string
 */
function get_hash()
{
    $chars   = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()+-';
    $random  = $chars[mt_rand(0,73)] . $chars[mt_rand(0,73)] . $chars[mt_rand(0,73)] . $chars[mt_rand(0,73)] . $chars[mt_rand(0,73)];
    $content = uniqid() . $random;
    return sha1($content);
}

/**
 * @param $filename
 * @return string
 * User: wjima
 * Email:1457529125@qq.com
 * Date: 2018-01-09 11:32
 */
function get_file_extension($filename)
{
    $pathinfo = pathinfo($filename);
    return strtolower($pathinfo['extension']);
}

/***
 * 获取HASH目录
 * @param $name
 * @return string
 * User: wjima
 * Email:1457529125@qq.com
 * Date: 2018-01-09 15:26
 */
function get_hash_dir($name='default')
{
    $ident = sha1(uniqid('',true) . $name . microtime());
    $dir   = DIRECTORY_SEPARATOR . $ident{0} . $ident{1} . DIRECTORY_SEPARATOR . $ident{2} . $ident{3} . DIRECTORY_SEPARATOR . $ident{4} . $ident{5} . DIRECTORY_SEPARATOR;
    return $dir;
}

/**
 *
 * +--------------------------------------------------------------------
 * Description 递归创建目录
 * +--------------------------------------------------------------------
 * @param  string $dir 需要创新的目录
 * +--------------------------------------------------------------------
 * @return 若目录存在,或创建成功则返回为TRUE
 * +--------------------------------------------------------------------
 * @author gongwen
 * +--------------------------------------------------------------------
 */
function mkdirs($dir,$mode = 0777)
{
    if(is_dir($dir) || mkdir($dir,$mode,true)) return true;
    if(!mkdirs(dirname($dir),$mode)) return false;
    return mkdir($dir,$mode,true);
}


/**
 * 返回图片地址
 * TODO 水印，裁剪，等操作
 * @param $image_id
 * @param $type
 * @return string
 * User: wjima
 * Email:1457529125@qq.com
 * Date: 2018-01-09 18:34
 */
function _sImage($image_id, $type = 's')
{
    if (!$image_id) {
        $image_id = getSetting('shop_default_image');//系统默认图片
        if (!$image_id) {
            return config('jshop.default_image');//默认图片
        }
    }

    if (stripos($image_id, 'http') !== false || stripos($image_id, 'https') !== false) {
        return $image_id;
    }

    $image_obj = new \app\common\model\Images();
    $image     = $image_obj->where([
        'id' => $image_id
    ])->field('url')->find();
    if ($image) {
        if (stripos($image['url'], 'http') !== false || stripos($image['url'], 'https') !== false) {
            return str_replace("\\", "/", $image['url']);
        } else {
            return request()->domain() . str_replace("\\", "/", $image['url']);
        }
    } else {
        return request()->domain() . '/' . config('jshop.default_image');//默认图片
    }
}
function master_imgUrl_base64($url){
    if($url){
        $header = array('User-Agent: Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:45.0) Gecko/20100101 Firefox/45.0','Accept-Language: zh-CN,zh;q=0.8,en-US;q=0.5,en;q=0.3','Accept-Encoding: gzip, deflate',);
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
//        curl_setopt($curl, CURLOPT_ENCODING, 'gzip');
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        $data = curl_exec($curl);
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        if ($code == 200) {//把URL格式的图片转成base64_encode格式的！
            $imgBase64Code = "data:image/jpeg;base64," . base64_encode($data);
            return $imgBase64Code;
        }else{
            return false;
        }
    }else{
        return false;
    }
}


/**
 * 相对地址转换为绝对地址
 */
function getRealUrl($url='')
{
    if(stripos($url,'http')!==false||stripos($url,'https')!==false) {
        return $url;
    }else{
        $storage_params = getSetting('image_storage_params');
        if (isset($storage_params['domain']) && $storage_params['domain']) {
            return $storage_params['domain'] . $url;
        }
        if(config('jshop.image_storage.domain')){
            return config('jshop.image_storage.domain').$url;
        }
        return request()->domain().$url;
    }
}
function get_date_dir()
{
    $dir = '/' . date('Y') . '/' . date('m') . '/' . date('d');
    return $dir;
}
/*function getRealUrl($url='')
{
        $storage_params = getSetting('image_storage_params');
        if (isset($storage_params['domain']) && $storage_params['domain']) {
            return $storage_params['domain'] . $url;
        }
        if(config('jshop.image_storage.domain')){
            return config('jshop.image_storage.domain').$url;
        }
        return $storage_params['endpoint'].$url;
}*/
/**
 * 格式化数据化手机号码
 */
function format_mobile($mobile)
{
    return substr($mobile,0,5)."****".substr($mobile,9,2);
}

//如果没有登陆的情况下，记录来源url，并跳转到登陆页面
function redirect_url()
{
    if(cookie('?redirect_url')){
        $str = cookie('redirect_url');
        cookie('redirect_url',null);
    }else{
        $str = '/';
    }
    return $str;
}
//如果没有登陆的情况下，记录来源url，并跳转到登陆页面
function redirect_manage_url()
{
    if(cookie('?redirect_url')){
        $str = cookie('redirect_url');
        cookie('redirect_url',null);
    }else{
        $str = \url('manage/index/index');
    }
    return $str;
}
//返回用户信息
function get_user_info($user_id,$field = 'mobile')
{
    $user = app\common\model\User::get($user_id);
    if($user){
        if($field == 'nickname') {
            $nickname = $user['nickname'];
            if ($nickname == '') {
                $nickname = format_mobile($user['mobile']);
            }
            return $nickname;
        }else{
            return $user->$field;
        }
    }else{
        return "";
    }
}
//返回商品信息
function get_goods_info($goods_id,$field = 'name')
{
    $goodsModel = new \app\common\model\Goods();
    $info = $goodsModel->where(['id'=>$goods_id])->find();
    if($info){
        if($field == 'image_id'){
            return _sImage($info[$field]);
        }else{
            return $info[$field];
        }
    }else{
        return '';
    }
}
//返回用户信息
function get_user_id($mobile)
{
    $userModel = new app\common\model\User();
    $user = $userModel->where(array('mobile'=>$mobile))->find();
    if($user){
        return $user->id;
    }else{
        return false;
    }
}

/**
 * 根据operation_id 取得链接地址
 * @param $id
 * @return string|Url
 */
function get_operation_url($id)
{
    $operationModel = new Operation();
    $actionInfo = $operationModel->where(array('id' => $id))->find();
    if (!$actionInfo) {
        return "";
    }
    $controllerInfo = $operationModel->where(array('id' => $actionInfo['parent_id']))->find();
    if (!$controllerInfo) {
        return "";
    }

    $modueInfo = $operationModel->where(array('id' => $controllerInfo['parent_id']))->find();
    if (!$modueInfo) {
        return "";
    }
    return url($modueInfo['code'] . '/' . $controllerInfo['code'] . '/' . $actionInfo['code']);
}
/**
 * 获取转换后金额
 * @param int $money
 * @return string
 * User: wjima
 * Email:1457529125@qq.com
 * Date: 2018-02-01 15:32
 */
function getMoney($money = 0)
{
    return sprintf("%.2f", $money);
}

//根据支付方式编码取支付方式名称等
function get_payment_info($payment_code,$field = 'name')
{
    $paymentModel = new Payments();
    $paymentInfo = $paymentModel->where(['code'=>$payment_code])->find();
    if($paymentInfo){
        return $paymentInfo[$field];
    }else{
        return $payment_code;
    }
}

//根据物流编码取物流名称等信息
function get_logi_info($logi_code,$field='logi_name')
{
    $logisticsModel = new Logistics();
    $logiInfo = $logisticsModel->where(['logi_code'=>$logi_code])->find();
    if($logiInfo){
        return $logiInfo[$field];
    }else{
        return $logi_code;
    }
}

/**
 * 根据地区id取省市区的信息
 * @param $area_id
 * @return string
 */
function get_area($area_id)
{
    $areaModel = new Area();
    $data = $areaModel->getArea($area_id);
    $parse = "";
    foreach($data as $v){
        if(isset($v['info'])){
            $parse .= $v['info']['name']." ";
        }
    }
    return $parse;
}
function error_code($code,$mini = false)
{
    $result = [
        'status' => false,
        'data' => 10000,
        'msg' => config('error.10000')
    ];
    if(config('?error.'.$code)){
        $result['data'] = $code;
        $result['msg'] = config('error.'.$code);
    }
    if($mini){
        return $result['msg'];
    }else{
        return $result;
    }
}

/**
 * 删除数组中指定值
 * @param $arr
 * @param $value
 * @return mixed
 */
function unsetByValue($arr, $value){
    $keys = array_keys($arr, $value);
    if(!empty($keys)){
        foreach ($keys as $key) {
            unset($arr[$key]);
        }
    }
    return $arr;
}

/**
 * 删除图片
 * @param $image_id
 * @return bool
 */
function delImage($image_id){
    $image_obj= new \app\common\model\Images();
    $image = $image_obj->where(['id'=>$image_id])->find();
    if($image){
        //删除图片数据
        $res = $image_obj->where(['id'=>$image_id])->delete();
        if($image['type']=='local'){
            $dd = @unlink($image['path']);
        }
        if($res){
            return true;
        }
        //默认本地存储，返回本地域名图片地址
    }else{
        return false;
    }
}

/**
 * 查询标签
 * @param $ids
 * @return array
 */
function getLabel($ids)
{
    if(!$ids){
        return [];
    }
    $label_obj = new \app\common\model\Label();
    $labels = $label_obj->field('name,style')->where('id', 'in', $ids)->select();
    if (!$labels->isEmpty()) {
        return $labels->toArray();
    }
    return [];
}

function getLabelStyle($style){
    $label_style='';
    switch ($style) {
        case 'red':
            $label_style = "";
            break;
        case 'green':
            $label_style = "layui-bg-green";
            break;
        case 'orange':
            $label_style = "layui-bg-orange";
            break;
        case 'blue':
            $label_style = "layui-bg-blue";
            break;
        default :
            $label_style = '';
    }
    return $label_style;
}

/* 单位自动转换函数 */
function getRealSize($size)
{
    $kb = 1024;         // Kilobyte
    $mb = 1024 * $kb;   // Megabyte
    $gb = 1024 * $mb;   // Gigabyte
    $tb = 1024 * $gb;   // Terabyte

    if($size < $kb)
    {
        return $size . 'B';
    }
    else if($size < $mb)
    {
        return round($size/$kb, 2) . 'KB';
    }
    else if($size < $gb)
    {
        return round($size/$mb, 2) . 'MB';
    }
    else if($size < $tb)
    {
        return round($size/$gb, 2) . 'GB';
    }
    else
    {
        return round($size/$tb, 2) . 'TB';
    }
}

/**
 * url参数转换为数组
 * @param $query
 * @return array
 */
function convertUrlQuery($query)
{
    $queryParts = explode('&', $query);
    $params = array();
    foreach ($queryParts as $param) {
        $item = explode('=', $param);
        $params[$item[0]] = $item[1];
    }
    return $params;
}

/**
 * bool型转义
 * @param string $value
 * @return mixed
 */
function getBool($value='1'){
    $bool = ['1'=>'是','2'=>'否'];
    return $bool[$value];
}

/**
 * 时间格式化
 * @param int $time
 * @return false|string
 */
function getTime($time = 0){
    return date('Y-m-d H:i:s',$time);
}

/**
 * 标签转换
 * @param array $labels
 * @return string
 */
function getExportLabel($labels = []){
    $labelString = '';
    foreach((array)$labels as $v){
        $labelString = $v['name'].',';
    }
    return substr($labelString,0,-1);
}

/**
 * 上下架状态转换
 * @param string $status
 * @return string
 */
function getMarketable($marketable='1'){
    $status = ['1'=>'上架','2'=>'下架'];
    return $status[$marketable];
}



/**
 * 数组转xml
 * @param $arr
 * @param string $root
 * @return string
 */
function arrayToXml($arr, $root = "root")
{
    $xml = "<" . $root . ">";
    foreach ($arr as $key => $val) {
        if (is_array($val)) {
            $xml .= "<" . $key . ">" . arrayToXml($val) . "</" . $key . ">";
        } else {
            $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
        }
    }
    $xml .= "</" . $root . ">";
    return $xml;
}

/**
 * 在模板中，有时候，新增的时候，要设置默认值
 * @param $val
 * @param $default
 * @return mixed
 */
function setDefault($val,$default)
{
    return $val?$val:$default;
}


/**
 * xml转数组
 * @param $xml
 * @return mixed
 */
function xmlToArray($xml)
{
    libxml_disable_entity_loader(true);
    $values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    return $values;
}

/**
 * 判断url是否内网ip
 * @param string $url
 * @return bool
 */
function isIntranet($url = '')
{
    $params = parse_url($url);
    $host = gethostbynamel($params['host']);
    if (is_array($host)) {
        foreach ($host as $key => $val) {
            if (!filter_var($val, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return true;
            }
        }
    }
    return false;
}

/**
 * 获取微信操作对象（单例模式）
 * @staticvar array $wechat 静态对象缓存对象
 * @param type $type 接口名称 ( Card|Custom|Device|Extend|Media|Oauth|Pay|Receive|Script|User )
 * @return \Wehcat\WechatReceive 返回接口对接
 */
function & load_wechat($type = '') {

    static $wechat = array();
    $index = md5(strtolower($type));
    if (!isset($wechat[$index])) {
        // 从数据库获取配置信息
        $options = array(
            'token'           => getSetting('wx_official_token'), // 填写你设定的key
            'appid'           => getSetting('wx_official_appid'), // 填写高级调用功能的app id, 请在微信开发模式后台查询
            'appsecret'       => getSetting('wx_official_app_secret'), // 填写高级调用功能的密钥
            'encodingaeskey'  => getSetting('wx_official_encodeaeskey'), // 填写加密用的EncodingAESKey（可选，接口传输选择加密时必需）
            'mch_id'          => '', // 微信支付，商户ID（可选）
            'partnerkey'      => '', // 微信支付，密钥（可选）
            'ssl_cer'         => '', // 微信支付，双向证书（可选，操作退款或打款时必需）
            'ssl_key'         => '', // 微信支付，双向证书（可选，操作退款或打款时必需）
            'cachepath'       => '', // 设置SDK缓存目录（可选，默认位置在Wechat/Cache下，请保证写权限）
        );
        \Wechat\Loader::config($options);
        $wechat[$index] = \Wechat\Loader::get($type);
    }
    return $wechat[$index];

}

/**
 * 获取最近天数的日期和数据
 * @param $day
 * @param $data
 * @return array
 */
function get_lately_days($day, $data)
{
    $day = $day-1;
    $days = [];
    $d = [];
    for($i = $day; $i >= 0; $i --)
    {
        $d[] = date('d', strtotime('-'.$i.' day')).'日';
        $days[date('Y-m-d', strtotime('-'.$i.' day'))] = 0;
    }
    foreach($data as $v)
    {
        $days[$v['day']] = $v['nums'];
    }
    $new = [];
    foreach ($days as $v)
    {
        $new[] = $v;
    }
    return ['day' => $d, 'data' => $new];
}

/**
 * 商家发送信息助手
 * @param $user_id
 * @param $code
 * @param $params
 * @return array
 */
function sendMessage($user_id, $code, $params)
{
    $messageCenter = new \app\common\model\MessageCenter();
    return $messageCenter->sendMessage($user_id, $code, $params);
}


/**
 * 根据商户id和用户id获取openid (废弃方法)
 * @param $user_id
 * @return bool|array
 */
function getUserWxInfo($user_id)
{
    $wxModel = new \app\common\model\UserWx();
    $filter[] = ['user_id','eq',$user_id];
    $wxInfo = $wxModel->field('id,user_id,openid,unionid,avatar,nickname')->where($filter)->find();
    if($wxInfo){
        return $wxInfo->toArray();
    }else{
        return false;
    }
}



/**
 * 判断用户是否有新消息，用于前端显示小红点
 * @param $user_id
 */
function hasNewMessage($user_id)
{
    $messageModel = new \app\common\model\Message();
    $re = $messageModel->hasNew($user_id);
    return $re;
}

//格式化银行卡号，前四位和最后显示原样的，其他隐藏
function bankCardNoFormat($cardNo){
    $n = strlen($cardNo);
    //判断尾部几位显示原型
    if($n%4 == 0){
        $j = 4;
    }else{
        $j = $n%4;
    }
    $str = "";
    for($i=0;$i<$n;$i++){
        if($i <4 || $i> $n-$j-1){
            $str .= $cardNo[$i];
        }else{
            $str .= "*";
        }
        if($i%4 == 3){
            $str .=" ";
        }
    }
    return $str;
}

/**
 * 获取系统设置
 * @param string $key
 * @return array
 */
function getSetting($key = ''){
    $systemSettingModel = new \app\common\model\Setting();
    return $systemSettingModel->getValue($key);
}

/***
 * 获取插件配置信息
 * @param string $name 插件名称
 * @return array
 */
function getAddonsConfig($name = ''){
    if(!$name){
        return [];
    }
    $addonModel = new \app\common\model\Addons();
    return $addonModel->getSetting($name);
}
//货品上的多规格信息，自动拆分成二维数组
function getProductSpesDesc($str_spes_desc){
    if($str_spes_desc == ""){
        return [];
    }
    $spes = explode(',',$str_spes_desc);
    $re = [];
    foreach($spes as $v){
        $val = explode(':',$v);
        $re[$val[0]] = $val[1];
    }
    return $re;
}

//返回管理员信息
function get_manage_info($manage_id,$field = 'username')
{
    $user = app\common\model\Manage::get($manage_id);
    if($user){
        if($field == 'nickname') {
            $nickname = $user['nickname'];
            if ($nickname == '') {
                $nickname = format_mobile($user['mobile']);
            }
            return $nickname;
        }else{
            return $user->$field;
        }
    }else{
        return "";
    }
}

/**
 * 数组倒排序，取新的键
 * @param array $array
 * @return array
 */
function _krsort($array = [])
{
    krsort($array);
    if (is_array($array)) {
        $i          = 0;
        $temp_array = [];
        foreach ($array as $val) {
            $temp_array[$i] = $val;
            $i++;
        }
        return $temp_array;
    } else {
        return $array;
    }
}

/**
 * 判断钩子是否有插件
 * @param string $hookname
 * @return bool
 */
function checkAddons($hookname = '')
{
    $hooksModel = new \app\common\model\Hooks();
    $addons     = $hooksModel->where(['name' => $hookname])->field('addons')->find();
    if (isset($addons['addons']) && !empty($addons['addons'])) {
        return true;
    } else {
        return false;
    }
}

/**
 * 判断商品是否参加团购
 * @param int $gid
 * @return array
 */
function isInGroup($gid = 0, &$promotion_id = 0)
{
    if (!$gid) {
        return false;
    }

    $promotion = new app\common\model\Promotion();

    $where[]   = ['p.status', 'eq', $promotion::STATUS_OPEN];
    $where[]   = ['p.stime', 'lt', time()];
    $where[]   = ['p.etime', 'gt', time()];
    $where[]   = ['pc.params', 'like', '%"' . $gid . '"%'];
    $where[]   = ['p.type', 'in', [$promotion::TYPE_GROUP, $promotion::TYPE_SKILL]];
    $condition = $promotion->field('p.id as id')
        ->alias('p')
        ->join('promotion_condition pc', 'pc.promotion_id = p.id')
        ->where($where)
        ->find();

    if ($condition) {
        $promotion_id = $condition['id'];
        return true;
    }
    return false;
}

/***
 * 判断是否json
 * @param $str
 * @return bool
 */
function isjson($str){
    return is_null(json_decode($str))?false:true;
}

/**
 * 判断是否手机号
 * @param $mobile
 * @return bool
 */
function isMobile($mobile = ''){
    if (preg_match("/^1[345678]{1}\d{9}$/", $mobile)) {
        return true;
    } else {
        return false;
    }
}

/**
 * 秒转换为天，小时，分钟
 * @param int $second
 * @return string
 */
function secondConversion($second = 0)
{
    $newtime = '';
    $d = floor($second / (3600*24));
    $h = floor(($second % (3600*24)) / 3600);
    $m = floor((($second % (3600*24)) % 3600) / 60);
    if($d>'0'){
        if($h == '0' && $m == '0'){
            $newtime= $d.'天';
        }else{
            $newtime= $d.'天'.$h.'小时'.$m.'分';
        }
    }else{
        if($h!='0'){
            if($m == '0'){
                $newtime= $h.'小时';
            }else{
                $newtime= $h.'小时'.$m.'分';
            }
        }else{
            $newtime= $m.'分';
        }
    }
    return $newtime;
}

/**
 * 获取推流地址
 * 如果不传key和过期时间，将返回不含防盗链的url
 * @param domain 您用来推流的域名
 *  streamName 您用来区别不同推流地址的唯一流名称
 *  key 安全密钥
 *  time 过期时间 sample 2016-11-12 12:00:00
 * user_record：用户可以录制的时长默认10分钟
 * storage_time：指定文件保存的时长，单位为秒s
 * record：指定录制格式 flv,hls,mp4等
 * @return String url
 */
function getPushUrl($domain , $streamName, $key, $user_record = 6000,$time = null){
    $key =  'c2cb0646eb8d406642b19d77814aef90';
    if($key && $time){
        $txTime = strtoupper(base_convert(strtotime($time),10,16));
        //txSecret = MD5( KEY + streamName + txTime )
        $txSecret = md5($key.$streamName.$txTime);
        $ext_str = "?".http_build_query(array(
                "txSecret"=> $txSecret,
                "txTime"=> $txTime
            ));
    }
    return "rtmp://".'pushadu.rziqee.cn'."/live/".$streamName . (isset($ext_str) ? $ext_str.'&record_interval='.$user_record : "");
}


/**
 * @param string $msg   信息
 * @param string $data  返回数据
 * @param bool $status  状态
 * @param $code 状态码:
 * 200-成功
 * 201-会员下单成功需要走支付流程
 * 202-下单成功不需要走支付流程
 * 400-登陆失效
 * 500-失败
 * @return array
 */
function result($msg='失败',$data='',$status=false,$code=200){
    return [
        'status' => $status,
        'data' => $data,
        'msg' => $msg,
        'code'=> $code
    ];
}

function des_encrypt($key, $encrypt)
{
    return base64_encode(openssl_encrypt($encrypt, "DES-ECB", $key, OPENSSL_RAW_DATA));
}
function des_decrypt($key, $decrypt)
{
    return openssl_decrypt(base64_decode($decrypt), "DES-ECB", $key, OPENSSL_RAW_DATA);

}

/**
 * 日志---在public/log文件夹下生成文件
 * @param $file 可以是0205.html 也可以是ss/fd/0206.html
 */
function logs($desc,$append=false,$msg='',$file=null){
    if(!$file){
        $file=date('md').'.html';
    }
    //日志内容
    $str="\n\n<br><br>".$msg.' '.date('Y-m-d H:i:s').'==='.microtime()."\n<br>";
    if(is_array($desc)||is_object($desc)){
        $desc=var_export($desc,TRUE);
        $desc="<pre>".$desc."</pre>";
    }else{
        $desc="<xmp>".$desc."</xmp>";
    }
    $str.=$desc;
    //写文件
    $filename=ROOT_PATH.'/public/log/'.$file;
    $filename=str_replace('\\','/',$filename);
    $dirname=dirname($filename);
    if(!is_dir($dirname)){
        mkdir($dirname,0777,true);
        chmod($dirname,0777);
    }
    if($append){
        if(is_file($filename)){
            $old_filecontent=file_get_contents($filename);
            if(strpos($old_filecontent,'utf-8')===false){
                $str="<meta charset='utf-8'>".$str;
            }
        }else{
            $str="<meta charset='utf-8'>".$str;
        }
        file_put_contents($filename,$str,FILE_APPEND);
    }else{
        $str="<meta charset='utf-8'>".$str;
        file_put_contents($filename,$str);
    }
}
/**
 * 检查是否有违规词汇
 * @param $str 检查的字符串
 * @param $forbidWord  禁用词汇字符串
 * @return int
 */
function checkForbidWord($str,$forbidWord){
    $forbidWord_arr = array_filter(explode(',',$forbidWord));
    $forbid = 0;
    foreach ($forbidWord_arr as $val){
        if(strstr($str,$val)){
            $forbid = 1;
        }
    }
    return $forbid;
}
// 过滤掉emoji表情
function filterEmoji($str)
{
    $str = preg_replace_callback( '/./u',
        function (array $match) {
            return strlen($match[0]) >= 4 ? '' : $match[0];
        },
        $str);
    return $str;
}
//获取周几
function weekNumUpper($str){
    switch ($str){
        case 0:
            $week = '日';
            break;
        case 1:
            $week = '一';
            break;
        case 2:
            $week = '二';
            break;
        case 3:
            $week = '三';
            break;
        case 4:
            $week = '四';
            break;
        case 5:
            $week = '五';
            break;
        case 6:
            $week = '六';
            break;
        default:
            $week = '';
            break;
    }
    return $week;
}

/**
 * 获取某个月份第一天和最后一天
 * @param $date
 */
function monthLastDay($date){
    $firstday = date('Y-m-01 00:00:00', strtotime($date));
    $lastday = date('Y-m-d 23:59:59', strtotime("$firstday +1 month -1 day"));
    return array($firstday, $lastday);
}
/**
 * 公用的方法  返回json数据，进行信息的提示
 * @param $status 状态
 * @param string $message 提示信息
 * @param array $data 返回数据
 */
function showMsg($status,$message = '',$data = array()){
    $result = array(
        'status' => $status,
        'message' =>$message,
        'data' =>$data
    );
    exit(json_encode($result));
}
function    is_getRed($uid){
    $is_lock = Db::name('alliance_lock')
        ->whereTime('time','yesterday')
        ->whereTime('otime', 'today')
        ->where([['user_id','=',$uid], ['status','in','1,2']])->find();
    if ($is_lock  == null)  return  false;
    else    return  true;
}
/**
 * 匹配红包展示用户
 * @return array
 */
function  getrandMixuid($uid){
    $mt = $mt1 = $mt_num = $mt_num1 = $ret = [];
    $r = [1.08,1.68,5.20,6.66,8.88];
    if (is_getRed($uid) == true){
        $m_map[] = ['user_id','neq',$uid];
    }
    if (is_getRed($uid) == false){
        $m_map[] = ['end_time','gt',strtotime('yesterday')];
        $m_map[] = ['ctime','lt',strtotime(date("Y-m-d"),time())];
        $vip_ids = Db::name('manage')->where($m_map)->field('user_id')->select();
        $uids =   array_column($vip_ids,'user_id');
        $mt_uids=sizeof($uids);
        $mt_uidss=$uids;
        $i =floor($mt_uids/100);
        if ( !is_array($mt_uidss)){
            return false;
        }
        if (!is_numeric($mt_uids))   {
            return false;
        }
        if (($mt_uids<=0))   {
            return false;
        }
        if ($i>=2){
            if ($i==2){
                if ($mt_uids<=16){
                    return  false;
                }
                $uidarr = array_rand($mt_uidss, 15);
                foreach ($uidarr as $key => $value) {
                    array_push($mt,$mt_uidss[$value]);
                }
                for ($x=0;$x<=6;$x++){
                    $ret[] = ['num'=>$r[0],'uid'=>$mt[$x]];
                }
                for ($x=7;$x<=8;$x++){
                    $ret[] = ['num'=>$r[1],'uid'=>$mt[$x]];
                }
                for ($x=9;$x<=10;$x++){
                    $ret[] = ['num'=>$r[2],'uid'=>$mt[$x]];
                }
                for ($x=11;$x<=13;$x++){
                    $ret[] = ['num'=>$r[0],'uid'=>$mt[$x]];
                }
                if ($mt[14]){$ret[] = ['num'=>$r[3],'uid'=>$mt[14]];}
                if ($mt[15]){$ret[] = ['num'=>$r[4],'uid'=>$mt[15]];}
            }else{
                $k = $i/2;
                $uidarr = array_rand($mt_uidss, $k);
                foreach ($uidarr as $key => $value) {
                    array_push($mt,$mt_uidss[$value]);
                }
                for ($x=0;$x<=($i/2)*3;$x++){
                    $ret[] = ['num'=>$r[0],'uid'=>$mt[$x]];
                }
                $u1 = $x=count($mt_uidss)-($i/2)*3;
                for ($u1;$x<=($i/2)*1;$x++){
                    $ret[] = ['num'=>$r[1],'uid'=>$mt[$x]];
                }
                $u2 = $x=count($mt_uidss)-$u1;
                for ($x=$u2;$x<=($i/2)*1;$x++){
                    $ret[] = ['num'=>$r[2],'uid'=>$mt[$x]];
                }
                ###############2
                $u3 = $x=count($mt_uidss)-$u2;
                for ($x=$u3;$x<=(($i-1)/2)*3;$x++){
                    $ret[] = ['num'=>$r[0],'uid'=>$mt[$x]];
                }
                $u4 = $x=count($mt_uidss)-$u3;
                for ($x=$u4;$x<=(($i-1)/2)*1;$x++){
                    $ret[] = ['num'=>$r[3],'uid'=>$mt[$x]];
                }
                $u5 = $x=count($mt_uidss)-$u4;
                for ($x=$u5;$x<=(($i-1)/2)*1;$x++){
                    $ret[] = ['num'=>$r[4],'uid'=>$mt[$x]];
                }
            }
        }else{
            shuffle($mt_uidss);
            $mt = $mt_uidss;
            if (count($mt)<=5){
                for ($x=3;$x<=count($mt);$x++){
                    $ret[$x] = ['num'=>$r[0],'uid'=>$mt[$x]];
                }
                $ret[0] = ['num'=>$r[1],'uid'=>$mt[1]];
                $ret[1] = ['num'=>$r[2],'uid'=>$mt[2]];
                ksort($ret);
            }else{
                for ($x=3;$x<=count($mt)-1;$x++){
                    $ret[$x] = ['num'=>$r[0],'uid'=>$mt[$x]];
                }
                $ret[0] = ['num'=>$r[1],'uid'=>$mt[1]];
                $ret[1] = ['num'=>$r[2],'uid'=>$mt[2]];
                ksort($ret);
            }
        }
    }
    return  $ret;
}
/**统计平台会员uid
 * @return int|string
 */
function   getVipids(){
    $m_map[] = ['end_time','gt',strtotime('yesterday')];
    $m_map[] = ['ctime','lt',strtotime(date("Y-m-d"),time())];
    $vip_ids = Db::name('manage')->where($m_map)->field('user_id')->select();
    return  array_column($vip_ids,'user_id');
}
/**
 * 统计平台会员数量
 * @return int|string
 */
function getVipnum(){
    $m_map[] = ['end_time','gt',strtotime('yesterday')];
    $m_map[] = ['ctime','lt',strtotime(date("Y-m-d",time()))];
    $vip_num = Db::name('manage')->where($m_map)->count();
    return  $vip_num;
}
/**
 * 统计平台会员数量,截止到当前时间
 * @return int|string
 */
function getTodayVipNum(){
    $m_map[] = ['end_time','>',strtotime('today')];
    $vip_num = Db::name('manage')->where($m_map)->count();
    return  $vip_num;
}
/**
 * 获取打卡奖励金额
 */
function getClockMoney($vipNum){
    $num = $vipNum < 2000 ? 1 : intval($vipNum/1000);
    $money = getSetting('clock_share_money');
    $money = $money*$num;
    return $money;
}

/**阳历农历互换
 * @param $riqi
 * @return string
 */
function getNongli($riqi)
{
    $nian=date('Y',strtotime($riqi));
    $yue=date('m',strtotime($riqi));
    $ri=date('d',strtotime($riqi));

    #农历每月的天数
    $everymonth=array(
        0=>array(8,0,0,0,0,0,0,0,0,0,0,0,29,30,7,1),
        1=>array(0,29,30,29,29,30,29,30,29,30,30,30,29,0,8,2),
        2=>array(0,30,29,30,29,29,30,29,30,29,30,30,30,0,9,3),
        3=>array(5,29,30,29,30,29,29,30,29,29,30,30,29,30,10,4),
        4=>array(0,30,30,29,30,29,29,30,29,29,30,30,29,0,1,5),
        5=>array(0,30,30,29,30,30,29,29,30,29,30,29,30,0,2,6),
        6=>array(4,29,30,30,29,30,29,30,29,30,29,30,29,30,3,7),
        7=>array(0,29,30,29,30,29,30,30,29,30,29,30,29,0,4,8),
        8=>array(0,30,29,29,30,30,29,30,29,30,30,29,30,0,5,9),
        9=>array(2,29,30,29,29,30,29,30,29,30,30,30,29,30,6,10),
        10=>array(0,29,30,29,29,30,29,30,29,30,30,30,29,0,7,11),
        11=>array(6,30,29,30,29,29,30,29,29,30,30,29,30,30,8,12),
        12=>array(0,30,29,30,29,29,30,29,29,30,30,29,30,0,9,1),
        13=>array(0,30,30,29,30,29,29,30,29,29,30,29,30,0,10,2),
        14=>array(5,30,30,29,30,29,30,29,30,29,30,29,29,30,1,3),
        15=>array(0,30,29,30,30,29,30,29,30,29,30,29,30,0,2,4),
        16=>array(0,29,30,29,30,29,30,30,29,30,29,30,29,0,3,5),
        17=>array(2,30,29,29,30,29,30,30,29,30,30,29,30,29,4,6),
        18=>array(0,30,29,29,30,29,30,29,30,30,29,30,30,0,5,7),
        19=>array(7,29,30,29,29,30,29,29,30,30,29,30,30,30,6,8),
        20=>array(0,29,30,29,29,30,29,29,30,30,29,30,30,0,7,9),
        21=>array(0,30,29,30,29,29,30,29,29,30,29,30,30,0,8,10),
        22=>array(5,30,29,30,30,29,29,30,29,29,30,29,30,30,9,11),
        23=>array(0,29,30,30,29,30,29,30,29,29,30,29,30,0,10,12),
        24=>array(0,29,30,30,29,30,30,29,30,29,30,29,29,0,1,1),
        25=>array(4,30,29,30,29,30,30,29,30,30,29,30,29,30,2,2),
        26=>array(0,29,29,30,29,30,29,30,30,29,30,30,29,0,3,3),
        27=>array(0,30,29,29,30,29,30,29,30,29,30,30,30,0,4,4),
        28=>array(2,29,30,29,29,30,29,29,30,29,30,30,30,30,5,5),
        29=>array(0,29,30,29,29,30,29,29,30,29,30,30,30,0,6,6),
        30=>array(6,29,30,30,29,29,30,29,29,30,29,30,30,29,7,7),
        31=>array(0,30,30,29,30,29,30,29,29,30,29,30,29,0,8,8),
        32=>array(0,30,30,30,29,30,29,30,29,29,30,29,30,0,9,9),
        33=>array(5,29,30,30,29,30,30,29,30,29,30,29,29,30,10,10),
        34=>array(0,29,30,29,30,30,29,30,29,30,30,29,30,0,1,11),
        35=>array(0,29,29,30,29,30,29,30,30,29,30,30,29,0,2,12),
        36=>array(3,30,29,29,30,29,29,30,30,29,30,30,30,29,3,1),
        37=>array(0,30,29,29,30,29,29,30,29,30,30,30,29,0,4,2),
        38=>array(7,30,30,29,29,30,29,29,30,29,30,30,29,30,5,3),
        39=>array(0,30,30,29,29,30,29,29,30,29,30,29,30,0,6,4),
        40=>array(0,30,30,29,30,29,30,29,29,30,29,30,29,0,7,5),
        41=>array(6,30,30,29,30,30,29,30,29,29,30,29,30,29,8,6),
        42=>array(0,30,29,30,30,29,30,29,30,29,30,29,30,0,9,7),
        43=>array(0,29,30,29,30,29,30,30,29,30,29,30,29,0,10,8),
        44=>array(4,30,29,30,29,30,29,30,29,30,30,29,30,30,1,9),
        45=>array(0,29,29,30,29,29,30,29,30,30,30,29,30,0,2,10),
        46=>array(0,30,29,29,30,29,29,30,29,30,30,29,30,0,3,11),
        47=>array(2,30,30,29,29,30,29,29,30,29,30,29,30,30,4,12),
        48=>array(0,30,29,30,29,30,29,29,30,29,30,29,30,0,5,1),
        49=>array(7,30,29,30,30,29,30,29,29,30,29,30,29,30,6,2),
        50=>array(0,29,30,30,29,30,30,29,29,30,29,30,29,0,7,3),
        51=>array(0,30,29,30,30,29,30,29,30,29,30,29,30,0,8,4),
        52=>array(5,29,30,29,30,29,30,29,30,30,29,30,29,30,9,5),
        53=>array(0,29,30,29,29,30,30,29,30,30,29,30,29,0,10,6),
        54=>array(0,30,29,30,29,29,30,29,30,30,29,30,30,0,1,7),
        55=>array(3,29,30,29,30,29,29,30,29,30,29,30,30,30,2,8),
        56=>array(0,29,30,29,30,29,29,30,29,30,29,30,30,0,3,9),
        57=>array(8,30,29,30,29,30,29,29,30,29,30,29,30,29,4,10),
        58=>array(0,30,30,30,29,30,29,29,30,29,30,29,30,0,5,11),
        59=>array(0,29,30,30,29,30,29,30,29,30,29,30,29,0,6,12),
        60=>array(6,30,29,30,29,30,30,29,30,29,30,29,30,29,7,1),
        61=>array(0,30,29,30,29,30,29,30,30,29,30,29,30,0,8,2),
        62=>array(0,29,30,29,29,30,29,30,30,29,30,30,29,0,9,3),
        63=>array(4,30,29,30,29,29,30,29,30,29,30,30,30,29,10,4),
        64=>array(0,30,29,30,29,29,30,29,30,29,30,30,30,0,1,5),
        65=>array(0,29,30,29,30,29,29,30,29,29,30,30,29,0,2,6),
        66=>array(3,30,30,30,29,30,29,29,30,29,29,30,30,29,3,7),
        67=>array(0,30,30,29,30,30,29,29,30,29,30,29,30,0,4,8),
        68=>array(7,29,30,29,30,30,29,30,29,30,29,30,29,30,5,9),
        69=>array(0,29,30,29,30,29,30,30,29,30,29,30,29,0,6,10),
        70=>array(0,30,29,29,30,29,30,30,29,30,30,29,30,0,7,11),
        71=>array(5,29,30,29,29,30,29,30,29,30,30,30,29,30,8,12),
        72=>array(0,29,30,29,29,30,29,30,29,30,30,29,30,0,9,1),
        73=>array(0,30,29,30,29,29,30,29,29,30,30,29,30,0,10,2),
        74=>array(4,30,30,29,30,29,29,30,29,29,30,30,29,30,1,3),
        75=>array(0,30,30,29,30,29,29,30,29,29,30,29,30,0,2,4),
        76=>array(8,30,30,29,30,29,30,29,30,29,29,30,29,30,3,5),
        77=>array(0,30,29,30,30,29,30,29,30,29,30,29,29,0,4,6),
        78=>array(0,30,29,30,30,29,30,30,29,30,29,30,29,0,5,7),
        79=>array(6,30,29,29,30,29,30,30,29,30,30,29,30,29,6,8),
        80=>array(0,30,29,29,30,29,30,29,30,30,29,30,30,0,7,9),
        81=>array(0,29,30,29,29,30,29,29,30,30,29,30,30,0,8,10),
        82=>array(4,30,29,30,29,29,30,29,29,30,29,30,30,30,9,11),
        83=>array(0,30,29,30,29,29,30,29,29,30,29,30,30,0,10,12),
        84=>array(10,30,29,30,30,29,29,30,29,29,30,29,30,30,1,1),
        85=>array(0,29,30,30,29,30,29,30,29,29,30,29,30,0,2,2),
        86=>array(0,29,30,30,29,30,30,29,30,29,30,29,29,0,3,3),
        87=>array(6,30,29,30,29,30,30,29,30,30,29,30,29,29,4,4),
        88=>array(0,30,29,30,29,30,29,30,30,29,30,30,29,0,5,5),
        89=>array(0,30,29,29,30,29,29,30,30,29,30,30,30,0,6,6),
        90=>array(5,29,30,29,29,30,29,29,30,29,30,30,30,30,7,7),
        91=>array(0,29,30,29,29,30,29,29,30,29,30,30,30,0,8,8),
        92=>array(0,29,30,30,29,29,30,29,29,30,29,30,30,0,9,9),
        93=>array(3,29,30,30,29,30,29,30,29,29,30,29,30,29,10,10),
        94=>array(0,30,30,30,29,30,29,30,29,29,30,29,30,0,1,11),
        95=>array(8,29,30,30,29,30,29,30,30,29,29,30,29,30,2,12),
        96=>array(0,29,30,29,30,30,29,30,29,30,30,29,29,0,3,1),
        97=>array(0,30,29,30,29,30,29,30,30,29,30,30,29,0,4,2),
        98=>array(5,30,29,29,30,29,29,30,30,29,30,30,29,30,5,3),
        99=>array(0,30,29,29,30,29,29,30,29,30,30,30,29,0,6,4),
        100=>array(0,30,30,29,29,30,29,29,30,29,30,30,29,0,7,5),
        101=>array(4,30,30,29,30,29,30,29,29,30,29,30,29,30,8,6),
        102=>array(0,30,30,29,30,29,30,29,29,30,29,30,29,0,9,7),
        103=>array(0,30,30,29,30,30,29,30,29,29,30,29,30,0,10,8),
        104=>array(2,29,30,29,30,30,29,30,29,30,29,30,29,30,1,9),
        105=>array(0,29,30,29,30,29,30,30,29,30,29,30,29,0,2,10),
        106=>array(7,30,29,30,29,30,29,30,29,30,30,29,30,30,3,11),
        107=>array(0,29,29,30,29,29,30,29,30,30,30,29,30,0,4,12),
        108=>array(0,30,29,29,30,29,29,30,29,30,30,29,30,0,5,1),
        109=>array(5,30,30,29,29,30,29,29,30,29,30,29,30,30,6,2),
        110=>array(0,30,29,30,29,30,29,29,30,29,30,29,30,0,7,3),
        111=>array(0,30,29,30,30,29,30,29,29,30,29,30,29,0,8,4),
        112=>array(4,30,29,30,30,29,30,29,30,29,30,29,30,29,9,5),
        113=>array(0,30,29,30,29,30,30,29,30,29,30,29,30,0,10,6),
        114=>array(9,29,30,29,30,29,30,29,30,30,29,30,29,30,1,7),
        115=>array(0,29,30,29,29,30,29,30,30,30,29,30,29,0,2,8),
        116=>array(0,30,29,30,29,29,30,29,30,30,29,30,30,0,3,9),
        117=>array(6,29,30,29,30,29,29,30,29,30,29,30,30,30,4,10),
        118=>array(0,29,30,29,30,29,29,30,29,30,29,30,30,0,5,11),
        119=>array(0,30,29,30,29,30,29,29,30,29,29,30,30,0,6,12),
        120=>array(4,29,30,30,30,29,30,29,29,30,29,30,29,30,7,1)
    );
##############################
    #农历天干
    $mten=array("null","甲","乙","丙","丁","戊","己","庚","辛","壬","癸");
    #农历地支
    $mtwelve=array("null","子(鼠)","丑(牛)","寅(虎)","卯(兔)","辰(龙)",
        "巳(蛇)","午(马)","未(羊)","申(猴)","酉(鸡)","戌(狗)","亥(猪)");
    #农历月份
    $mmonth=array("闰","正","二","三","四","五","六",
        "七","八","九","十","十一","十二","月");
    #农历日
    $mday=array("null","初一","初二","初三","初四","初五","初六","初七","初八","初九","初十",
        "十一","十二","十三","十四","十五","十六","十七","十八","十九","二十",
        "廿一","廿二","廿三","廿四","廿五","廿六","廿七","廿八","廿九","三十");
##############################
    #星期
    $weekday = array("星期日","星期一","星期二","星期三","星期四","星期五","星期六");
    #阳历总天数 至1900年12月21日
    $total=11;
    #阴历总天数
    $mtotal=0;
##############################
    #获得当日日期
    //$today=getdate(); //获取今天的日期
    if($nian<1901 || $nian>2020) die("年份出错！");
    //$cur_wday=$today["wday"]; //星期中第几天的数字表示
    for($y=1901;$y<$nian;$y++) { //计算到所求日期阳历的总天数-自1900年12月21日始,先算年的和
        $total+=365;
        if ($y%4==0) $total++;
    }
    switch($yue) { //再加当年的几个月
        case 12:
            $total+=30;
        case 11:
            $total+=31;
        case 10:
            $total+=30;
        case 9:
            $total+=31;
        case 8:
            $total+=31;
        case 7:
            $total+=30;
        case 6:
            $total+=31;
        case 5:
            $total+=30;
        case 4:
            $total+=31;
        case 3:
            $total+=28;
        case 2:
            $total+=31;
    }
    if($nian%4 == 0 && $yue>2) $total++; //如果当年是闰年还要加一天
    $total=$total+$ri-1; //加当月的天数
    $flag1=0; //判断跳出循环的条件
    $j=0;
    while ($j<=120){ //用农历的天数累加来判断是否超过阳历的天数
        $i=1;
        while ($i<=13){
            $mtotal+=$everymonth[$j][$i];
            if ($mtotal>=$total){
                $flag1=1;
                break;
            }
            $i++;
        }
        if ($flag1==1) break;
        $j++;
    }
    if($everymonth[$j][0]<>0 and $everymonth[$j][0]<$i){ //原来错在这里，对闰月没有修补
        $mm=$i-1;
    }
    else{
        $mm=$i;
    }
    if($i==$everymonth[$j][0]+1 and $everymonth[$j][0]<>0) {
        $nlmon=$mmonth[0].$mmonth[$mm];#闰月
    }
    else {
        $nlmon=$mmonth[$mm].$mmonth[13];
    }
    #计算所求月份1号的农历日期
    $md=$everymonth[$j][$i]-($mtotal-$total);
    if($md > $everymonth[$j][$i])
        $md-=$everymonth[$j][$i];
    $nlday=$mday[$md];

    //$nowday=date("Y年n月j日 ")."w".$weekday[$cur_wday]." ".$mten[$everymonth[$j][14]].$mtwelve[$everymonth[$j][15]]."年".$nlmon.$nlday;
    $nowday=$mten[$everymonth[$j][14]].$mtwelve[$everymonth[$j][15]]."年 ".$nlmon.$nlday;
    return $nowday;
}

/**
 * 获取指定时间的星期几
 * @param $time
 */
function    getW($time){
    if (isset($time) && $time != ""){
        $weekarray=array("日","一","二","三","四","五","六");
        return "星期".$weekarray[date("w",$time)];
    }else{
        return false;
    }
}
/**
 * 生成6位数，不足前面补0
 * @param $ID
 * @return bool|string
 */
function    formatGroupID($ID){
    if (empty($ID) or $ID<=0){
        return  false;
    }
    return  sprintf("%06d", $ID);
}

/**
 * 获取对应等级推荐金额
 */
function getTjMoney($tj_level){
    $money = 0;
    switch ($tj_level){
        case 0://初级
            $money = getSetting('vip_tj_pri');
            break;
        case 1://高级
            $money = getSetting('vip_tj_sen');
            break;
        case 2://盟主
            $money = getSetting('vip_tj_mz');
            break;
    }
    return $money;
}

/**
 * 获取推荐盟主一次性奖金
 */
function getOnceMoney($level){
    switch ($level){
        case 0:
            $money = getSetting('once_money_pri');
            break;
        case 1:
            $money = getSetting('once_money_sen');
            break;
        case 2:
            $money = getSetting('once_money_mz');
            break;
        case 3:
            $money = getSetting('once_money_par');
            break;
        default:
            $money = 0;
            break;
    }
    return $money;
}
/**
 * 清除会员发放奖励
 */
function clearAwardDistribution($uid){
    $rs = Db::name('user_recharge')->where([
        ['status','in',['1','2']],
        ['user_id','eq',$uid]
    ])->update([
        'status'=>4,
        'utime'=>0
    ]);
    if($rs){
        return result('成功','',true);
    }else{
        return result('清除会员发放奖励失败');
    }
}
/**
 * 获取支付金额
 */
function getPayMoney($level,$num){
    switch ($level){
        case 1:
            $money = 49*$num;
            break;
        case 2:
            $money = 199*$num;
            break;
        case 3:
            $money = 2988*$num;
            break;
        default:
            $money = 0;
            break;
    }
    return $money;
}

/**
 * 红包是否中奖
 */
function redEnvelope($uid){
    $return = [
        'status'=>false,//是否中奖：true中奖 false没中奖
        'money'=>0//中奖金额
    ];
    //判断是否已经开过红包
    $is_lock = Db::name('alliance_lock')
        ->whereTime('time','yesterday')
        ->where([['user_id','=',$uid], ['status','eq',0]])->find();
    if(empty($is_lock)){
        return $return;
    }
    //计算红包池
    $vip_num = getVipnum();
    $remainEnvelopeArr = remainEnvelopeArr($vip_num);
    //统计未拆红包人数
    $clock_num = Db::name('alliance_lock')
        ->whereTime('time','yesterday')
        ->where(['status'=>0])
        ->count();
    if(count($remainEnvelopeArr) == 0){
        //红包池剩余数量为0，无奖可中
        return $return;
    }elseif($clock_num <= count($remainEnvelopeArr)){
        //未拆红包人数小于等于红包池剩余数量，必中奖
        shuffle($remainEnvelopeArr);
        $key = round(0,count($remainEnvelopeArr)-1);
        $return['status'] = true;
        $return['money'] = $remainEnvelopeArr[$key];
        return $return;
    }else{
        $envelopeArrNow = array_merge($remainEnvelopeArr,array_fill(0,$clock_num-count($remainEnvelopeArr),0));
        shuffle($envelopeArrNow);
        $key = round(0,count($envelopeArrNow)-1);
        $money = $envelopeArrNow[$key];
        if($money == 0){
            return $return;
        }else{
            $return['status'] = true;
            $return['money'] = $money;
            return $return;
        }
    }
}

/**
 * 红包池
 * @param $vip_num
 * @param int $type
 * @return array
 */
function envelopeArr($vip_num,$type=1){
    $arr = [];
    if($vip_num > 0){
        if($type == 1){
            $envelope = [1.08,1.08,1.08,1.68,5.20];
            $num = $vip_num < 200 ? 1 : intval($vip_num/100);
        }else if($type == 2){
            $envelope = [1.08,1.08,1.08,6.66,8.88];
            $num = $vip_num < 200 ? 0 : intval($vip_num/200);
        }
        for($i=0;$i<$num;$i++){
            $arr = array_merge($arr,$envelope);
        }
    }
    return $arr;
}
/**
 * 红包池剩余数量
 */
function remainEnvelopeArr($vip_num){
    $arr1 = envelopeArr($vip_num,1);
    $arr2 = envelopeArr($vip_num,2);
    $remainEnvelopeArr = array_merge($arr1,$arr2);
    //今日已领取的红包
    $today_envelope = Db::name('alliance_lock')->alias('a')
        ->join('user_balance b','a.id = b.source_id','left')
        ->where([
            ['a.status','eq',1],
            ['b.chg_type','eq',17]
        ])->whereTime('a.time','yesterday')
        ->whereTime('a.otime','today')->column('b.money');

    foreach ($today_envelope as $v){
        $key = array_search($v,$remainEnvelopeArr);
        if($key !== false){
            unset($remainEnvelopeArr[$key]);
        }
    }

    return $remainEnvelopeArr;
}

