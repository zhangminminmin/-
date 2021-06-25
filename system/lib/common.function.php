<?php

//数据过滤函数库
/*
  功能：用来过滤字符串和字符串数组，防止被挂马和sql注入
  参数$data，待过滤的字符串或字符串数组，
  $force为true，忽略get_magic_quotes_gpc
 */
function in($data, $force = false) {
    if (is_string($data)) {
        $data = trim(htmlspecialchars($data)); //防止被挂马，跨站攻击
        if (($force == true) || (!get_magic_quotes_gpc())) {
            $data = addslashes($data); //防止sql注入
        }
        return $data;
    } else if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = in($value, $force);
        }
        return $data;
    } else {
        return $data;
    }
}

//用来还原字符串和字符串数组，把已经转义的字符还原回来
function out($data) {
    if (is_string($data)) {
        return $data = stripslashes($data);
    } else if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = out($value);
        }
        return $data;
    } else {
        return $data;
    }
}

//文本输入
function text_in($str) {
    $str = strip_tags($str, '<br>');
    $str = str_replace(" ", "&nbsp;", $str);
    $str = str_replace("\n", "<br>", $str);
    if (!get_magic_quotes_gpc()) {
        $str = addslashes($str);
    }
    return $str;
}

//文本输出
function text_out($str) {
    $str = str_replace("&nbsp;", " ", $str);
    $str = str_replace("<br>", "\n", $str);
    $str = stripslashes($str);
    return $str;
}

//html代码输入
function html_in($str, $xss = false) {
    $search = array("'<script[^>]*?>.*?</script>'si", // 去掉 javascript
        "'<iframe[^>]*?>.*?</iframe>'si", // 去掉iframe
    );
    $replace = array("",
        "",
    );
    if ($xss) {
        $str = @preg_replace($search, $replace, $str);
    }
    $str = htmlspecialchars($str);
    if (!get_magic_quotes_gpc()) {
        $str = addslashes($str);
    }
    return $str;
}

//html代码输出
function html_out($str) {
    if (function_exists('htmlspecialchars_decode'))
        $str = htmlspecialchars_decode($str);
    else
        $str = html_entity_decode($str);

    $str = stripslashes($str);
    return $str;
}

// 获取客户端IP地址
function get_client_ip() {
    if (getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"), "unknown")) {
        $ip = getenv("HTTP_CLIENT_IP");
    } else if (getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), "unknown")) {
        $ip = getenv("HTTP_X_FORWARDED_FOR");
    } else if (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown")) {
        $ip = getenv("REMOTE_ADDR");
    } else if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], "unknown")) {
        $ip = $_SERVER['REMOTE_ADDR'];
    } else {
        $ip = "unknown";
    }
    if (preg_match('#^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$#', $ip)) {
        $ip_array = explode('.', $ip);
        if ($ip_array[0] <= 255 && $ip_array[1] <= 255 && $ip_array[2] <= 255 && $ip_array[3] <= 255) {
            return $ip;
        }
    }
    return "unknown";
}

//中文字符串截取
function msubstr($str, $start = 0, $length, $charset = "utf-8", $suffix = true) {
    if (empty($str)) {
        return;
    }
    $sourcestr = $str;
    $cutlength = $length;
    $returnstr = '';
    $i = 0;
    $n = 0.0;
    $str_length = strlen($sourcestr); //字符串的字节数
    while (($n < $cutlength) and ($i < $str_length)) {
        $temp_str = substr($sourcestr, $i, 1);
        $ascnum = ord($temp_str);
        if ($ascnum >= 252) {
            $returnstr = $returnstr . substr($sourcestr, $i, 6);
            $i = $i + 6;
            $n++;
        } elseif ($ascnum >= 248) {
            $returnstr = $returnstr . substr($sourcestr, $i, 5);
            $i = $i + 5;
            $n++;
        } elseif ($ascnum >= 240) {
            $returnstr = $returnstr . substr($sourcestr, $i, 4);
            $i = $i + 4;
            $n++;
        } elseif ($ascnum >= 224) {
            $returnstr = $returnstr . substr($sourcestr, $i, 3);
            $i = $i + 3;
            $n++;
        } elseif ($ascnum >= 192) {
            $returnstr = $returnstr . substr($sourcestr, $i, 2);
            $i = $i + 2;
            $n++;
        } elseif ($ascnum >= 65 and $ascnum <= 90 and $ascnum != 73) {
            $returnstr = $returnstr . substr($sourcestr, $i, 1);
            $i = $i + 1;
            $n++;
        } elseif (!(array_search($ascnum, array(37, 38, 64, 109, 119)) === FALSE)) {
            $returnstr = $returnstr . substr($sourcestr, $i, 1);
            $i = $i + 1;
            $n++;
        } else {
            $returnstr = $returnstr . substr($sourcestr, $i, 1);
            $i = $i + 1;
            $n = $n + 0.5;
        }
    }
    if ($i < $str_length) {
        $returnstr = $returnstr . '...';
    }
    return $returnstr;
}

//模块之间相互调用
function module($module) {
    static $module_obj = array();
    static $config = array();
    if (isset($module_obj[$module])) {
        return $module_obj[$module];
    }
    if (!isset($config['MODULE_PATH'])) {
        $config['MODULE_PATH'] = cpConfig::get('MODULE_PATH');
        $config['MODULE_SUFFIX'] = cpConfig::get('MODULE_SUFFIX');
        $suffix_arr = explode('.', $config['MODULE_SUFFIX'], 2);
        $config['MODULE_CLASS_SUFFIX'] = $suffix_arr[0];
    }
    if (file_exists($config['MODULE_PATH'] . $module . $config['MODULE_SUFFIX'])) {
        require_once($config['MODULE_PATH'] . $module . $config['MODULE_SUFFIX']); //加载模型文件
        $classname = $module . $config['MODULE_CLASS_SUFFIX'];
        if (class_exists($classname)) {
            return $module_obj[$module] = new $classname();
        }
    } else {
        return false;
    }
}

//模型调用函数
if (!function_exists('model')) {

    function model($model) {
        static $model_obj = array();
        static $config = array();
        if (isset($model_obj[$model])) {
            return $model_obj[$model];
        }
        if (!isset($config['MODEL_PATH'])) {
            $config['MODEL_PATH'] = cpConfig::get('MODEL_PATH');
            $config['MODEL_SUFFIX'] = cpConfig::get('MODEL_SUFFIX');
            $suffix_arr = explode('.', $config['MODEL_SUFFIX'], 2);
            $config['MODEL_CLASS_SUFFIX'] = $suffix_arr[0];
        }
        if (file_exists($config['MODEL_PATH'] . $model . $config['MODEL_SUFFIX'])) {
            require_once($config['MODEL_PATH'] . $model . $config['MODEL_SUFFIX']); //加载模型文件
            $classname = $model . $config['MODEL_CLASS_SUFFIX'];
            if (class_exists($classname)) {
                return $model_obj[$model] = new $classname();
            }
        }
        return false;
    }

}

// 检查字符串是否是UTF8编码,是返回true,否则返回false
function is_utf8($string) {
    if (!empty($string)) {
        $ret = json_encode(array('code' => $string));
        if ($ret == '{"code":null}') {
            return false;
        }
    }
    return true;
}

// 自动转换字符集 支持数组转换
function auto_charset($fContents, $from = 'gbk', $to = 'utf-8') {
    $from = strtoupper($from) == 'UTF8' ? 'utf-8' : $from;
    $to = strtoupper($to) == 'UTF8' ? 'utf-8' : $to;
    if (strtoupper($from) === strtoupper($to) || empty($fContents) || (is_scalar($fContents) && !is_string($fContents))) {
        //如果编码相同或者非字符串标量则不转换
        return $fContents;
    }
    if (is_string($fContents)) {
        if (function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($fContents, $to, $from);
        } elseif (function_exists('iconv')) {
            return iconv($from, $to, $fContents);
        } else {
            return $fContents;
        }
    } elseif (is_array($fContents)) {
        foreach ($fContents as $key => $val) {
            $_key = auto_charset($key, $from, $to);
            $fContents[$_key] = auto_charset($val, $from, $to);
            if ($key != $_key)
                unset($fContents[$key]);
        }
        return $fContents;
    }
    else {
        return $fContents;
    }
}

// 浏览器友好的变量输出
function dump($var, $exit = false) {
    $output = print_r($var, true);
    $output = "<pre>" . htmlspecialchars($output, ENT_QUOTES) . "</pre>";
    echo $output;
    if ($exit)
        exit();
}

//获取微秒时间，常用于计算程序的运行时间
function utime() {
    list($usec, $sec) = explode(" ", microtime());
    return ((float) $usec + (float) $sec);
}

//生成唯一的值
function cp_uniqid() {
    return md5(uniqid(rand(), true));
}

//加密函数，可用cp_decode()函数解密，$data：待加密的字符串或数组；$key：密钥；$expire 过期时间
function cp_encode($data, $key = '', $expire = 0) {
    $string = serialize($data);
    $ckey_length = 4;
    $key = md5($key);
    $keya = md5(substr($key, 0, 16));
    $keyb = md5(substr($key, 16, 16));
    $keyc = substr(md5(microtime()), -$ckey_length);

    $cryptkey = $keya . md5($keya . $keyc);
    $key_length = strlen($cryptkey);

    $string = sprintf('%010d', $expire ? $expire + time() : 0) . substr(md5($string . $keyb), 0, 16) . $string;
    $string_length = strlen($string);
    $result = '';
    $box = range(0, 255);

    $rndkey = array();
    for ($i = 0; $i <= 255; $i++) {
        $rndkey[$i] = ord($cryptkey[$i % $key_length]);
    }

    for ($j = $i = 0; $i < 256; $i++) {
        $j = ($j + $box[$i] + $rndkey[$i]) % 256;
        $tmp = $box[$i];
        $box[$i] = $box[$j];
        $box[$j] = $tmp;
    }

    for ($a = $j = $i = 0; $i < $string_length; $i++) {
        $a = ($a + 1) % 256;
        $j = ($j + $box[$a]) % 256;
        $tmp = $box[$a];
        $box[$a] = $box[$j];
        $box[$j] = $tmp;
        $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
    }
    return $keyc . str_replace('=', '', base64_encode($result));
}

//cp_encode之后的解密函数，$string待解密的字符串，$key，密钥
function cp_decode($string, $key = '') {
    $ckey_length = 4;
    $key = md5($key);
    $keya = md5(substr($key, 0, 16));
    $keyb = md5(substr($key, 16, 16));
    $keyc = substr($string, 0, $ckey_length);

    $cryptkey = $keya . md5($keya . $keyc);
    $key_length = strlen($cryptkey);

    $string = base64_decode(substr($string, $ckey_length));
    $string_length = strlen($string);

    $result = '';
    $box = range(0, 255);

    $rndkey = array();
    for ($i = 0; $i <= 255; $i++) {
        $rndkey[$i] = ord($cryptkey[$i % $key_length]);
    }

    for ($j = $i = 0; $i < 256; $i++) {
        $j = ($j + $box[$i] + $rndkey[$i]) % 256;
        $tmp = $box[$i];
        $box[$i] = $box[$j];
        $box[$j] = $tmp;
    }

    for ($a = $j = $i = 0; $i < $string_length; $i++) {
        $a = ($a + 1) % 256;
        $j = ($j + $box[$a]) % 256;
        $tmp = $box[$a];
        $box[$a] = $box[$j];
        $box[$j] = $tmp;
        $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
    }
    if ((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26) . $keyb), 0, 16)) {
        return unserialize(substr($result, 26));
    } else {
        return '';
    }
}

//遍历删除目录和目录下所有文件
function del_dir($dir) {
    if (!is_dir($dir)) {
        return false;
    }
    $handle = opendir($dir);
    while (($file = readdir($handle)) !== false) {
        if ($file != "." && $file != "..") {
            is_dir("$dir/$file") ? del_dir("$dir/$file") : @unlink("$dir/$file");
        }
    }
    if (readdir($handle) == false) {
        closedir($handle);
        @rmdir($dir);
    }
}

//如果json_encode没有定义，则定义json_encode函数，常用于返回ajax数据
if (!function_exists('json_encode')) {

    function format_json_value(&$value) {
        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        } else if (is_int($value)) {
            $value = intval($value);
        } else if (is_float($value)) {
            $value = floatval($value);
        } else if (defined($value) && $value === null) {
            $value = strval(constant($value));
        } else if (is_string($value)) {
            $value = '"' . addslashes($value) . '"';
        }
        return $value;
    }

    function json_encode($data) {
        if (is_object($data)) {
            //对象转换成数组
            $data = get_object_vars($data);
        } else if (!is_array($data)) {
            // 普通格式直接输出
            return format_json_value($data);
        }
        // 判断是否关联数组
        if (empty($data) || is_numeric(implode('', array_keys($data)))) {
            $assoc = false;
        } else {
            $assoc = true;
        }
        // 组装 Json字符串
        $json = $assoc ? '{' : '[';
        foreach ($data as $key => $val) {
            if (!is_null($val)) {
                if ($assoc) {
                    $json .= "\"$key\":" . json_encode($val) . ",";
                } else {
                    $json .= json_encode($val) . ",";
                }
            }
        }
        if (strlen($json) > 1) {// 加上判断 防止空数组
            $json = substr($json, 0, -1);
        }
        $json .= $assoc ? '}' : ']';
        return $json;
    }

}

//POST表单处理函数,$post_array:POST的数据,$null_value:是否删除空表单,$delete_value:删除指定表单
function postinput($post_array, $null_value = null, $delete_value = array()) {
    //清除值为空或者为0的元素
    if ($null_value) {
        foreach ($post_array as $key => $value) {
            $value = in($value);
            if ($value == '') {
                unset($post_array[$key]);
            }
        }
    }
    //清除不需要的元素
    $default_value = array('action', 'button', 'fid', 'submit');
    $clear_array = array_merge($default_value, $delete_value);
    foreach ($post_array as $key => $value) {
        if (in_array($key, $clear_array)) {
            unset($post_array[$key]);
        }
    }
    return $post_array;
}

//复制目录
function copy_dir($sourceDir, $aimDir) {
    $succeed = true;
    if (!file_exists($aimDir)) {
        if (!mkdir($aimDir, 0777)) {
            return false;
        }
    }
    $objDir = opendir($sourceDir);
    while (false !== ($fileName = readdir($objDir))) {
        if (($fileName != ".") && ($fileName != "..")) {
            if (!is_dir("$sourceDir/$fileName")) {
                if (!copy("$sourceDir/$fileName", "$aimDir/$fileName")) {
                    $succeed = false;
                    break;
                }
            } else {
                copy_dir("$sourceDir/$fileName", "$aimDir/$fileName");
            }
        }
    }
    closedir($objDir);
    return $succeed;
}

//判断ajax提交
function is_ajax() {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
        return true;
    if (isset($_POST['ajax']) || isset($_GET['ajax']))
        return true;
    return false;
}

function my_sort($arrays, $sort_key, $sort_order = SORT_ASC, $sort_type = SORT_NUMERIC) {
    if (is_array($arrays)) {
        foreach ($arrays as $array) {
            if (is_array($array)) {
                $key_arrays[] = $array[$sort_key];
            } else {
                return false;
            }
        }
    } else {
        return false;
    }
    array_multisort($key_arrays, $sort_order, $sort_type, $arrays);
    return $arrays;
}

/**
 * 数组转换为字符串，主要用于把分隔符调整到第二个参数
 * @param  array  $arr  要连接的数组
 * @param  string $glue 分割符
 * @return string
 */
function arr2str($arr, $glue = ',') {
    return implode($glue, $arr);
}

/**
 * 字符串转换为数组，主要用于把分隔符调整到第二个参数
 * @param  string $str  要分割的字符串
 * @param  string $glue 分割符
 * @return array
 */
function str2arr($str, $glue = ',') {
    return explode($glue, $str);
}

/**
 * 把返回的数据集转换成Tree
 * @param array $list 要转换的数据集
 * @param string $pid parent标记字段
 * @return array

 */
function list_to_tree($list, $pk = 'id', $pid = 'pid', $child = '_child', $root = 0) {
// 创建Tree
    $tree = array();
    if (is_array($list)) {
// 创建基于主键的数组引用
        $refer = array();
        foreach ($list as $key => $data) {
            $refer[$data[$pk]] = & $list[$key];
        }
        foreach ($list as $key => $data) {
// 判断是否存在parent
            $parentId = $data[$pid];
            if ($root == $parentId) {
                $tree[] = & $list[$key];
            } else {
                if (isset($refer[$parentId])) {
                    $parent = & $refer[$parentId];
                    $parent[$child][] = & $list[$key];
                }
            }
        }
    }
    return $tree;
}

function arr_unset($arr) {//循环去除数组的空值 $arr 数组
    foreach ($arr as $k => $v) {
        if (!$v)
            unset($arr[$k]);
    }
    return $arr;
}

//其中$list为传值过来的二维数组转换为指定键的一维数组，$default为键，$k为指定的表字段 
function arr1tag($list, $default = 'id', $k = 'name') {
    $tmp = '';
    if (is_array($list)) {
        foreach ($list as $k1 => $v1) {
            $tmp[$v1[$default]] = strtolower($v1[$k]); //转换为小写
        }
    }
    return $tmp;
}

// 返回访问凭证
function getAccessToken($appId, $appSecret) {
    // access_token 应该全局存储与更新，以下代码以写入到文件中做示例
    $filename = $_SERVER['DOCUMENT_ROOT'] . '/data/' . "access_token.json";
    if (file_exists($filename)) {
        // 文件已经存在
        $data = json_decode(file_get_contents($filename));
    } else {
        // 文件不存在
        $data = json_decode('{"expire_time":0,"access_token":""}');
    }
	$date = time();
    if ($data->expire_time < $date) {
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . $appId . "&secret=" . $appSecret;
        $res = json_decode(httpGet($url));
        $access_token = $res->access_token;
        if ($access_token) {
            $data->expire_time = $date + 7000;
            $data->access_token = $access_token;
            // 将access_token写入文件
            $fp = fopen($_SERVER['DOCUMENT_ROOT'] . '/data/' . "access_token.json", "w");
            fwrite($fp, json_encode($data));
            fclose($fp);
			
			// 生成ticket
			$data = json_decode('{"expire_time":0,"jsapi_ticket":""}');
			$url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$access_token";
			$res = json_decode(httpGet($url));
			$ticket = $res->ticket;
			if ($ticket) {
				$data->expire_time = $date + 7000;
				$data->jsapi_ticket = $ticket;
				$filename = $_SERVER['DOCUMENT_ROOT'] . '/data/' . "jsapi_ticket.json";
				$fp = fopen($filename, "w");
				fwrite($fp, json_encode($data));
				fclose($fp);
			}
        } else {
            return FALSE;
        }
    } else {
        $access_token = $data->access_token;
    }
    return $access_token;
}

function httpGet($url, $method = 'get', $data = '') {

    $ch = curl_init();
    $headers = array('Accept-Charset: utf-8');
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible;MSIE 5.01;Windows NT 5.0)');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $temp = curl_exec($ch);
    return $temp;
}
function httpGet2($url, $method = 'get', $data = '') {

    $ch = curl_init();
    $headers = array('Accept-Charset: utf-8');
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible;MSIE 5.01;Windows NT 5.0)');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $temp = curl_exec($ch);
    //return $temp;
}

function api_notice_increment($url, $data) {
    $ch = curl_init();
    $headers = array('Accept-Charset: utf-8');

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible;MSIE 5.01;Windows NT 5.0)');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $tmpInfo = curl_exec($ch);
    $errorno = curl_errno($ch);
    if ($errorno) {
        return '发生错误：curl error' . $errorno;
    } else {

        $js = json_decode($tmpInfo, true);

        if (isset($js['ticket'])) {
            return $js['ticket'];
        } else {
            return '发生错误：错误代码' . $js['errcode'] . ',微信返回错误信息：' . $js['errmsg'];
        }
    }
}

function postCurl($url, $data) {
    $ch = curl_init();
    $header = "Accept-Charset: utf-8";
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $tmpInfo = curl_exec($ch);
    $errorno = curl_errno($ch);
    if ($errorno) {
       // return array('rt' => false, 'errorno' => $errorno);
    } else {
        $js = json_decode($tmpInfo, 1);
        if ($js['errcode'] == '0') {
          //  return array('rt' => true, 'errorno' => 0);
        } else {
            //$this->error('模板消息发送失败。错误代码'.$js['errcode'].',错误信息：'.$js['errmsg']);
           // return array('rt' => false, 'errorno' => $js['errcode'], 'errmsg' => $js['errmsg']);
        }
    }
}


/** 
* 图片地址替换成绝对路径
* @param string $content 内容 
* @param string $prefix 域名前缀
*/  
function getImgThumbUrl($content="",$suffix){

    $pregRule = "/<[img|IMG].*?src=[\'|\"](.*?(?:[\.jpg|\.jpeg|\.png|\.gif|\.bmp]))[\'|\"].*?[\/]?>/";
    $content = preg_replace($pregRule, '<img src="'.$suffix.'${1}">', htmlspecialchars_decode($content));
    return $content;  
}  

//格式化图片路径
function formatAppImageUrl($url,$suffix){
    if(!$url){
        return false;
    }
    $result = isRemoteUrl($url);
    if(!$result){
        return $suffix.$url;
    }else{
        return $url;
    }
    
}

function isRemoteUrl($url){
    return stripos($url, 'http://')  === 0 || stripos($url, 'https://') === 0;
}

/*
*   处理图片上传，form_data
*/

function imageUpload($data,$folder,$type=""){
    $images = arrayColumn($data);
    //print_r($images);
    $return = array();
    if($images){
        foreach($images as $key=>$val){
            if($type == 1) {
              check_upload_image($val);
            }
            $dir = __ROOTDIR__.'/upload/'.$folder.'/'.date("Ymd",time()).'/';
            $arr = explode(".", $val['name']);
            $t = $arr[count($arr)-1];
            $file = $dir.time().MD5($val['name']).".".$t;
            $path = '/upload/'.$folder.'/'.date("Ymd",time()).'/'.time().MD5($val['name']).".".$t;
            if(!is_dir($dir)){
                @mkdir($dir,0777,true);
            }
            move_uploaded_file($val['tmp_name'],$file);
            $return[] = $path;
        }
    }
    return $return;
}

//二维数组根据字数组键名重组
function arrayColumn($array,$column = ''){
    $result = array();
    if(!$array){
        return $array;
    }
    foreach($array as $key=>$val){
        if(is_array($val)){
            foreach($val as $k=>$v){
                $result[$k][$key] = $v;
            }
        }else{
            $result[0][$key] = $val;
        }
    }
    return $column ? $result[$column] : $result;
}

function check_upload_image($image){
    $image_name = $image['name'];
    $image_ext = get_file_extension($image_name);
    //echo $image_ext;
    $ext_arr = array('jpg','png','jpeg','JPG','JPEG','PNG','gif','GIF');
    if(!in_array($image_ext,$ext_arr)){
        ajaxReturn(202,'请上传jpg或png格式的图片');
    }
    return true;
}

function get_file_extension($filename){
    return pathinfo($filename, PATHINFO_EXTENSION);
}

function sendSmscode($mobile,$content){
    $flag = 0;
    $params=''; //要post的数据

    $sign = '2611';
    $templateId = '3377';
    $mobile = $mobile;

    $nation_code = substr($mobile, 0, 2);
    if ($nation_code == '86') {
        $sign = '141125';
        $templateId = '171823';
        $mobile = substr($mobile, -11);
    }
    $argv = array(
        'accesskey'=>"QUG8vY7OCfk0ebnE",     //平台分配给用户的accesskey，登录系统首页可点击"我的秘钥"查看
        'secret'=>"ciKkzSeqqKtyNs0XEMXDwnOgFdqn3dfq",     //平台分配给用户的secret，登录系统首页可点击"我的秘钥"查看
        'sign'=>$sign,   // 2611    141125 平台上申请的接口短信签名或者签名ID（须审核通过），采用utf8编码
        'templateId'=>$templateId,   // 3377   171823平台上申请的接口短信模板Id（须审核通过）
        'mobile'=>$mobile,   //接收短信的手机号码(只支持单个手机号)
        'content'=> $content  ,   //发送的短信内容是模板变量内容，多个变量中间用##或者$$隔开，采用utf8编码
    );

    //构造要post的字符串
    foreach ($argv as $key=>$value) {
        if ($flag!=0) {
            $params .= "&";
            $flag = 1;
        }
        $params.= $key."="; $params.= urlencode($value);// urlencode($value);
        $flag = 1;
    }
    if ($nation_code == '86') {
        $url = "http://api.1cloudsp.com/api/v2/single_send?".$params; //提交的url地址
    }else{
        $url = "http://api.1cloudsp.com/intl/api/v2/send?".$params; //提交的url地址
    }
    $con= substr( file_get_contents($url), 0,100 );  //获取信息发送后的状态
    $con_code = json_decode($con, true);
    if($con_code['code'] == '0'){
        return true;
    }else{
        return false;
    }

}

// 生成随机验证码
function getRandChar($length){
    $str = '0123456789abcdefghijklmnopqrstuvwxyz';
    $len = strlen($str);
    $return = '';
    for($i=0;$i<$length;$i++){
        $num = mt_rand(0,$len-1);
        $return .= $str{$num};
    }
    return strtoupper($return);
}

function arrayToXml($arr){
    $xml = "<xml>";
    foreach ($arr as $key=>$val)
    {
         if (is_numeric($val))
         {
            $xml.="<".$key.">".$val."</".$key.">"; 
         }
         else{
            $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";  
         } 
    }
    $xml.="</xml>";
    return $xml; 
}

function xmlToArray($xml){  
    if(!$xml){
        return array();
    }
    //将XML转为array
    //禁止引用外部xml实体
    libxml_disable_entity_loader(true);
    $values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);      
    return $values;
}

    function ajaxReturn($val, $msg='',$content="") {
        $res['ret'] = $val;
        $res['msg'] = $msg;
        $res['content'] = $content;
        echo json_encode($res);
        exit;
    }

?>