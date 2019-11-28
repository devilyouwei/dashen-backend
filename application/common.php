<?php
// +----------------------------------------------------------------------
// | 大神
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 zhongbang
// +----------------------------------------------------------------------
// +----------------------------------------------------------------------
// | Author: 黄有为 <devilyouwei@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
// 全局变量文件

define("GD_KEY","9362a25da292f5ded272051acd0cf80f");//高德地图全局开发者key

//获取第三方api注册登录用户数据
function get_api_open_userinfo($openid,$token,$app_name){
    $url = "";
    switch($app_name){
    case 'weixin':
        $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=$token&openid=$openid&lang=zh_CN";
        break;
    case 'qq':
        break;
    case '':
        break;
    default:
        return false;
    }
    return curl_json($url);

    //return $api_json;  
}

//curl请求，获取json
function curl_json($url){
    $timeout = 5;
    //发送请求到指定接口
    $ch = curl_init();  
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查  
    curl_setopt($ch, CURLOPT_URL, $url);  
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);  
    curl_setopt($ch, CURLOPT_HEADER, 0);
    $json = curl_exec($ch);  //获取到json数据
    curl_close($ch);  
    return json($json);
}


//获取客户端IP
function get_client_ip() {
    if (getenv ( "HTTP_CLIENT_IP" ) && strcasecmp ( getenv ( "HTTP_CLIENT_IP" ), "unknown" ))
        $ip = getenv ( "HTTP_CLIENT_IP" );
    else if (getenv ( "HTTP_X_FORWARDED_FOR" ) && strcasecmp ( getenv ( "HTTP_X_FORWARDED_FOR" ), "unknown" ))
        $ip = getenv ( "HTTP_X_FORWARDED_FOR" );
    else if (getenv ( "REMOTE_ADDR" ) && strcasecmp ( getenv ( "REMOTE_ADDR" ), "unknown" ))
        $ip = getenv ( "REMOTE_ADDR" );
    else if (isset ( $_SERVER ['REMOTE_ADDR'] ) && $_SERVER ['REMOTE_ADDR'] && strcasecmp ( $_SERVER ['REMOTE_ADDR'], "unknown" ))
        $ip = $_SERVER ['REMOTE_ADDR'];
    else
        $ip = "unknown";
    return ($ip);
}

function get_rand_salt($length=8){
    $str = null;
    $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
    $max = strlen($strPol)-1;
    for($i=0;$i<$length;$i++){
        $str.=$strPol[rand(0,$max)];    //rand($min,$max)生成介于min和max两个数之间的一个随机整数
    }
    return $str;
}

function encrypt_pwd($pwd, $salt, $type='md5'){
    $en_pwd = "";
    switch($type){
    case 'md5':
        $en_pwd = md5($pwd.$salt);
        break;
    case 'crypt':
        $en_pwd = crypt($pwd, $salt);
        break;
    case 'sha1':
        $en_pwd = sha1($pwd.$salt);
        break;
    case 'phpass':
        $hasher = new \encrypt\phpass\PasswordHash(8, false);  
        $en_pwd = $hasher->HashPassword($pwd.$salt);
        break;
    default:
        $en_pwd = $pwd.$salt;//不加密
    }

    return $en_pwd;
}

//邮件格式验证的函数
function check_email($email)
{
    if(!preg_match("/^\w+((-\w+)|(\.\w+))*\@[A-Za-z0-9]+((\.|-)[A-Za-z0-9]+)*\.[A-Za-z0-9]+$/",$email))
    {
        return false;
    }
    else
        return true;
}

/**
 * 获取客户端浏览器信息 添加win10 edge浏览器判断
 * @param  null
 * @author  Jea杨
 * @return string 
 */
function get_client_browser(){
    $sys = $_SERVER['HTTP_USER_AGENT'];  //获取用户代理字符串
    if (stripos($sys, "Firefox/") > 0) {
        preg_match("/Firefox\/([^;)]+)+/i", $sys, $b);
        $exp[0] = "Firefox";
        $exp[1] = $b[1];  //获取火狐浏览器的版本号
    } elseif (stripos($sys, "Maxthon") > 0) {
        preg_match("/Maxthon\/([\d\.]+)/", $sys, $aoyou);
        $exp[0] = "傲游";
        $exp[1] = $aoyou[1];
    } elseif (stripos($sys, "MSIE") > 0) {
        preg_match("/MSIE\s+([^;)]+)+/i", $sys, $ie);
        $exp[0] = "IE";
        $exp[1] = $ie[1];  //获取IE的版本号
    } elseif (stripos($sys, "OPR") > 0) {
        preg_match("/OPR\/([\d\.]+)/", $sys, $opera);
        $exp[0] = "Opera";
        $exp[1] = $opera[1];  
    } elseif(stripos($sys, "Edge") > 0) {
        //win10 Edge浏览器 添加了chrome内核标记 在判断Chrome之前匹配
        preg_match("/Edge\/([\d\.]+)/", $sys, $Edge);
        $exp[0] = "Edge";
        $exp[1] = $Edge[1];
    } elseif (stripos($sys, "Chrome") > 0) {
        preg_match("/Chrome\/([\d\.]+)/", $sys, $google);
        $exp[0] = "Chrome";
        $exp[1] = $google[1];  //获取google chrome的版本号
    } elseif(stripos($sys,'rv:')>0 && stripos($sys,'Gecko')>0){
        preg_match("/rv:([\d\.]+)/", $sys, $IE);
        $exp[0] = "IE";
        $exp[1] = $IE[1];
    }else {
        $exp[0] = "未知浏览器";
        $exp[1] = ""; 
    }
    return $exp[0].'('.$exp[1].')';
}

/**
 * 获取客户端操作系统信息包括win10
 * @param  null
 * @author  Jea杨
 * @return string 
 */
function get_os(){
    $agent = $_SERVER['HTTP_USER_AGENT'];
    $os = false;

    if (preg_match('/win/i', $agent) && strpos($agent, '95'))
    {
        $os = 'Windows 95';
    }
    else if (preg_match('/win 9x/i', $agent) && strpos($agent, '4.90'))
    {
        $os = 'Windows ME';
    }
    else if (preg_match('/win/i', $agent) && preg_match('/98/i', $agent))
    {
        $os = 'Windows 98';
    }
    else if (preg_match('/win/i', $agent) && preg_match('/nt 6.0/i', $agent))
    {
        $os = 'Windows Vista';
    }
    else if (preg_match('/win/i', $agent) && preg_match('/nt 6.1/i', $agent))
    {
        $os = 'Windows 7';
    }
    else if (preg_match('/win/i', $agent) && preg_match('/nt 6.2/i', $agent))
    {
        $os = 'Windows 8';
    }else if(preg_match('/win/i', $agent) && preg_match('/nt 10.0/i', $agent))
    {
        $os = 'Windows 10';#添加win10判断
    }else if (preg_match('/win/i', $agent) && preg_match('/nt 5.1/i', $agent))
    {
        $os = 'Windows XP';
    }
    else if (preg_match('/win/i', $agent) && preg_match('/nt 5/i', $agent))
    {
        $os = 'Windows 2000';
    }
    else if (preg_match('/win/i', $agent) && preg_match('/nt/i', $agent))
    {
        $os = 'Windows NT';
    }
    else if (preg_match('/win/i', $agent) && preg_match('/32/i', $agent))
    {
        $os = 'Windows 32';
    }
    else if (preg_match('/linux/i', $agent))
    {
        $os = 'Linux';
    }
    else if (preg_match('/unix/i', $agent))
    {
        $os = 'Unix';
    }
    else if (preg_match('/sun/i', $agent) && preg_match('/os/i', $agent))
    {
        $os = 'SunOS';
    }
    else if (preg_match('/ibm/i', $agent) && preg_match('/os/i', $agent))
    {
        $os = 'IBM OS/2';
    }
    else if (preg_match('/Mac/i', $agent) && preg_match('/PC/i', $agent))
    {
        $os = 'Macintosh';
    }
    else if (preg_match('/PowerPC/i', $agent))
    {
        $os = 'PowerPC';
    }
    else if (preg_match('/AIX/i', $agent))
    {
        $os = 'AIX';
    }
    else if (preg_match('/HPUX/i', $agent))
    {
        $os = 'HPUX';
    }
    else if (preg_match('/NetBSD/i', $agent))
    {
        $os = 'NetBSD';
    }
    else if (preg_match('/BSD/i', $agent))
    {
        $os = 'BSD';
    }
    else if (preg_match('/OSF1/i', $agent))
    {
        $os = 'OSF1';
    }
    else if (preg_match('/IRIX/i', $agent))
    {
        $os = 'IRIX';
    }
    else if (preg_match('/FreeBSD/i', $agent))
    {
        $os = 'FreeBSD';
    }
    else if (preg_match('/teleport/i', $agent))
    {
        $os = 'teleport';
    }
    else if (preg_match('/flashget/i', $agent))
    {
        $os = 'flashget';
    }
    else if (preg_match('/webzip/i', $agent))
    {
        $os = 'webzip';
    }
    else if (preg_match('/offline/i', $agent))
    {
        $os = 'offline';
    }
    else
    {
        $os = '未知操作系统';
    }
    return $os;  
}
//随机生成器
function get_rand(){
    $now = $_SERVER['REQUEST_TIME'];//当前系统时间，比time()多5秒
    return rand().$now;
}

//保存用户密码，多个模块使用
function savePwd($id, $pwd, $encrypt)
{
    $salt = get_rand_salt(); // 获取随机盐值
    $user_pwd = encrypt_pwd($pwd, $salt, $encrypt); // 实行密码加密
    // 需要更新加密后的密码，盐值，还有加密方式
    $res = db("user")->where('id', $id)->update(['user_pwd' => $user_pwd, 'salt' => $salt, 'encryption' => $encrypt]);
    if ($res == 0)
        return false;
    else
        return true;
}

//仅发送短信验证码
//不对手机号码验证，不对用户进行登记，请在控制器中验证，登记
//返回false为失败，返回验证码为成功
function sendCode($phone){

    //初始化必填
    $options['accountsid']='0301af23e4518f7b426ab97a888f6cb7';
    $options['token']='21287edd45f02b766fd28e863c8c6e43';

    $ucpass = new \ucpass\Ucpaas($options);

    //短信验证码（模板短信）,默认以65个汉字（同65个英文）为一条（可容纳字数受您应用名称占用字符影响），超过长度短信平台将会自动分割为多条发送。分割后的多条短信将按照具体占用条数计费。
    $appId = "f3ea835f9fb14078a1ee69d8ce571f7d";
    $to = $phone;
    $templateId = '126273';
    $param=rand(1000,9999);

    $obj = json_decode($ucpass->templateSMS($appId,$to,$templateId,$param),true);
    if($obj['resp']['respCode']=="000000"){
        return $param;
    }else{
        //如果发送失败，返回false
        return false;
    }
}

//将文件上传至阿里oss
//$file 件路径
//$rename 文件名
//@return 真为转储oss成功，false为异常，失败
function uploadToOSS($file,$rename){
    $accessKeyId = 'LTAIi1ZLALvQ9Gzf';
    $accessKeySecret = '9evvz05ai1oBm9rBxgX3CHdIFjwa6M';
    $endpoint = 'oss-cn-beijing.aliyuncs.com';
    $bucketName="zhongbang";
    try {
        $ossClient = new OSS\OssClient($accessKeyId, $accessKeySecret, $endpoint);
        $ossClient->uploadFile($bucketName, $rename,$file);
        return true;
    } catch (OssException $e) {
        return false;
    }
}


/*
 * http request tool
 * get method
 */
function http_get($url, $param=array()){
    if(!is_array($param)){
        throw new Exception("参数必须为array");
    }
    $p='';
    foreach($param as $key => $value){
        $p=$p.$key.'='.$value.'&';
    }
    if(preg_match('/\?[\d\D]+/',$url)){//matched ?c
        $p='&'.$p;
    }else if(preg_match('/\?$/',$url)){//matched ?$
        $p=$p;
    }else{
        $p='?'.$p;
    }
    $p=preg_replace('/&$/','',$p);
    $url=$url.$p;
    //echo $url;
    $httph =curl_init($url);
    curl_setopt($httph, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($httph, CURLOPT_SSL_VERIFYHOST, 1);
    curl_setopt($httph,CURLOPT_RETURNTRANSFER,1);
    curl_setopt($httph, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0)");

    curl_setopt($httph, CURLOPT_RETURNTRANSFER,1);
    curl_setopt($httph, CURLOPT_HEADER,1);
    $rst=curl_exec($httph);
    curl_close($httph);
    return $rst;
}

/*
 * post method
 */
function http_post($url, $param=array()){
    if(!is_array($param)){
        throw new Exception("参数必须为array");
    }
    $httph =curl_init($url);
    curl_setopt($httph, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($httph, CURLOPT_SSL_VERIFYHOST, 1);
    curl_setopt($httph,CURLOPT_RETURNTRANSFER,1);
    curl_setopt($httph, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0)");
    curl_setopt($httph, CURLOPT_POST, 1);//设置为POST方式 
    curl_setopt($httph, CURLOPT_POSTFIELDS, $param);
    curl_setopt($httph, CURLOPT_RETURNTRANSFER,1);
    curl_setopt($httph, CURLOPT_HEADER,1);
    $rst=curl_exec($httph);
    curl_close($httph);
    return $rst;
}

//格式化货币
function format_money($money){
    return number_format($money,2,".","");
}

function https_get($url){
	$ch = curl_init();
	curl_setopt($ch,CURLOPT_URL,$url); //请求的URL
	curl_setopt($ch,CURLOPT_HEADER,false); //是否显示头部
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,true); //是否直接输出到屏幕
	//上面true 和 false 也可以用0、1，但我习惯用这个。由于只是取数据，没必要显示到屏幕上
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //https请求 不验证证书 其实只用这个就可以了
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); //https请求 不验证HOST
	//curl_setopt($ch,CURLOPT_POST,true); //是否以post方式
	$accToken = curl_exec($ch);
	$obj = json_decode($accToken);
	curl_close($ch);
	return $obj;
}

define("APP_ID",'wxacb04a3e4ce7e251');
define("APP_SECRET",'1fa8ad50edbeea5a88a691487af17cb5');
//获取微信token
function getWxToken(){
	$url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.APP_ID.'&secret='.APP_SECRET;
	return https_get($url)->access_token;
}
//加密字段
define("TIME_STAMP",'1414587457');
define("NONCESTR","Wm3WZYTPz0wzccnW");
//微信浏览器js许可
function wxJsApi(){
	//动态获取url地址
	$host_url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
	$acc_tok = getWxToken();
	//请求js-tickets
	$tickUrl = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token='.$acc_tok.'&type=jsapi';
	$ticketStr = https_get($tickUrl)->ticket;
	//return $ticketStr;
	$str = 'jsapi_ticket='.$ticketStr.'&noncestr='.NONCESTR.'&timestamp='.TIME_STAMP.'&url='.$host_url;
	return sha1($str);
}

//从微信获取文件
//$image_id 媒体id
//$type 文件类型 voice img
//返回文件名
function wxGetFile($media_id,$type='img'){
	$acc_tok = getWxToken();
	$url ="https://api.weixin.qq.com/cgi-bin/media/get?access_token=".$acc_tok."&media_id=".$media_id;
	$info = http_wx_file($url,ROOT_PATH . 'public' . DS . 'uploads',$type);
	if(!$info)
		return false;
	return $info['file_name'];
}

/*
 * 传入：
 * $url 微信路径
 * $save_dir 保存路径
 * $type 文件类型，默认图片jpg
 */
function http_wx_file($url,$save_dir='',$type='img',$filename=''){  
	$ext = '.jpg';
	if($type == 'img')
    		$ext=".jpg";//以jpg的格式结尾  
    	else
    		$ext=".amr";

    if(trim($url)==''){  
        return array('file_name'=>'','save_path'=>'','error'=>1);  
    }  
    if(trim($save_dir)==''){  
        $save_dir='./';  
    }  
    if(trim($filename)==''){//保存文件名  
        $filename=get_rand().$ext;  
    }else{  
        $filename = $filename.$ext;  
    }  
    if(0!==strrpos($save_dir,'/')){  
        $save_dir.='/';  
    }  
    //创建保存目录  
    if(!is_dir($save_dir)){//文件夹不存在，则新建  
        //print_r($save_dir."文件不存在");  
        mkdir(iconv("UTF-8", "GBK", $save_dir),0777,true);  
        //mkdir($save_dir,0777,true);  
    }  

	$ch = curl_init();
	curl_setopt($ch,CURLOPT_URL,$url); //请求的URL
	curl_setopt($ch,CURLOPT_HEADER,false); //是否显示头部
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,true); //是否直接输出到屏幕
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //https请求 不验证证书 其实只用这个就可以了
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); //https请求 不验证HOST
	$img = curl_exec($ch);

	$size = strlen($img);
    //文件大小   
    //var_dump("文件大小:".$size);  
    $fp2=@fopen($save_dir.$filename,'w');  
    fwrite($fp2,$img,$size);  
    fclose($fp2);  
    unset($img,$url);  
    $return = array('file_name'=>$filename,'save_path'=>$save_dir.$filename,'error'=>0);  
    $f = uploadToOSS($return['save_path'],$filename);
    if($f)
    		unlink($return['save_path']);
    	else
    		return false;
    return $return;
}  

