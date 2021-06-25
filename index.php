<?php
header("content-type:text/html; charset=utf-8");
@date_default_timezone_set('PRC');

if(!empty($_SERVER['HTTP_X_REWRITE_URL']) ){
	$_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_REWRITE_URL'];
} else if (!isset($_SERVER['REQUEST_URI'])) {
	if (isset($_SERVER['argv']))
	{
		$_SERVER['REQUEST_URI'] = $_SERVER['PHP_SELF'] .'?'. $_SERVER['argv'][0]; 
	}else{
		$_SERVER['REQUEST_URI'] = $_SERVER['PHP_SELF'] .'?'. $_SERVER['QUERY_STRING']; 
	} 
}

$origin = isset($_SERVER['HTTP_ORIGIN'])? $_SERVER['HTTP_ORIGIN'] : '';  
file_put_contents("1.txt", $origin);
$allow_origin = array(  
    'http://192.168.1.163:8080',
);
if(in_array($origin, $allow_origin)) {
    
    header('Access-Control-Allow-Credentials:true');
    header('Access-Control-Allow-Origin:' . $origin);
    header('Access-Control-Allow-Headers:Access-Control-Allow-Origin, Content-Type, Access-Control-Allow-Credentials, Authorization, X-Requested-With');
}
//定义框架目录
define('CP_PATH', dirname(__file__) . '/system/'); //指定内核目录
require (dirname(__file__) . '/inc/config.php');
require (CP_PATH . 'core/cpApp.class.php');

//处理多语言
$lang=$config['LANG_DEFAULT'];
if($config['LANG_OPEN']){
	$lang_file=scandir($config['LANG_PACK_PATH']);
	$url=$_SERVER['REQUEST_URI'];
	foreach ($lang_file as $value) {
		$url=strtolower($url);
		if(strstr($url,'/'.$value)){
			$lang=$value;
			$url = preg_replace('`/'.$value.'`', '', $url,1);
			break;
		}
	}
	$_SERVER['REQUEST_URI']=$url;
}
define('__LANG__', $lang);

//处理手机版
if($config['MOBILE_OPEN']&&$config['MOBILE_DOMAIN']){
	if($config['MOBILE_DOMAIN']==$_SERVER["HTTP_HOST"]){
		define('MOBILE', true);
	}else{
		if(isMobile()){
			header('location:http://'. $config['MOBILE_DOMAIN'].$_SERVER["REQUEST_URI"]);
		}else{
			define('MOBILE', false);
		}
	}
}else{
	define('MOBILE', false);
}

//定义自定义目录
$root = $config['URL_HTTP_HOST'] . str_replace(basename($_SERVER["SCRIPT_NAME"]), '', $_SERVER["SCRIPT_NAME"]);
define('__ROOT__', substr($root, 0, -1));
define('__ROOTDIR__', strtr(dirname(__FILE__),'\\','/'));
define('__UPDIR__', strtr(dirname(__FILE__),'\\','/upload/'));
define('__TPL__', __ROOT__.'/'.$config['TPL_TEMPLATE_PATH']);
define('__UPL__', __ROOT__.'/upload/');

//实例化入口
$app = new cpApp($config);
Lang::init($config);
$app->run();


function isMobile(){

    // 如果有HTTP_X_WAP_PROFILE则一定是移动设备

    if (isset ($_SERVER['HTTP_X_WAP_PROFILE']))

    {

        return true;

    }

    // 如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息

    if (isset ($_SERVER['HTTP_VIA']))

    {

        // 找不到为flase,否则为true

        return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;

    }

    // 脑残法，判断手机发送的客户端标志,兼容性有待提高

    if (isset ($_SERVER['HTTP_USER_AGENT'])){

        $clientkeywords = array ('nokia',

            'sony',

            'ericsson',

            'mot',

            'samsung',

            'htc',

            'sgh',

            'lg',

            'sharp',

            'sie-',

            'philips',

            'panasonic',

            'alcatel',

            'lenovo',

            'iphone',

            'ipod',

            'blackberry',

            'meizu',

            'android',

            'netfront',

            'symbian',

            'ucweb',

            'windowsce',

            'palm',

            'operamini',

            'operamobi',

            'openwave',

            'nexusone',

            'cldc',

            'midp',
            'wap',
            'mobile'
            );
        // 从HTTP_USER_AGENT中查找手机浏览器的关键字
        if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT'])))
        {
            return true;
        }
    }
    // 协议法，因为有可能不准确，放到最后判断
    if (isset ($_SERVER['HTTP_ACCEPT']))
    {
        // 如果只支持wml并且不支持html那一定是移动设备
        // 如果支持wml和html但是wml在html之前则是移动设备
        if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html'))))
        {
            return true;
        }
    }
    return false;
}
?>