<?php
/*
 * @Description 返回JSON格式的数据（用户异步回调数据的返回，或返回数据至APP等..）
 * @Copyright  ahaiba.com
 * @Author  Jeff<jeff.chou@aliyun.com>    2016-10-28
 * 
 * @调用示例如下：
 * $data['id'] = 1;
 * $data['name'] = 'Jeff';
 * Response::json(200, $data);
 */

class Response {
	
	/**
	* $code 返回的提示码
	* $message 返回的提示信息
	* $data 返回的数据
	*/
	public static function json($code, $data = array()){
		if(!is_numeric($code)){
			return '';
		}
		
		$result = array(
			'code'=>$code,
			'message'=>Response::getStatusCodeMessage($code),
			'data'=>$data
		);
		
		//header('content-type:application/json;charset=utf8');
		//echo jsonFormat($result);
		echo json_encode($result);
		exit;
	}
	
	private static function getStatusCodeMessage($code) {
		$codes = Array(
			200 => 'OK',
			
			400 => 'Bad Request',
			401 => 'Unauthorized',
			402 => 'Payment Required',
			403 => 'Forbidden',
			404 => 'Not Found',
			
			500 => 'Internal Server Error',
			501 => 'Not Implemented',
			502 => 'Bad Gateway',
			503 => 'Service Unavailable',
			504 => 'Gateway Timeout',
			505 => 'HTTP Version Not Supported'
		);
	 
		return (isset($codes[$code])) ? $codes[$code] : '';
	}
	
	public static function sendResponse($status = 200, $body = '', $content_type = 'text/html') {
		$status_header = 'HTTP/1.1 ' . $status . ' ' . Response::getStatusCodeMessage($status);
		header($status_header);
		header('Content-type: ' . $content_type);
		echo $body;
	}
	
	
}

/** Json数据格式化（仅用于展示）
* @param  Mixed  $data   数据 
* @param  String $indent 缩进字符，默认4个空格 
* @return JSON 
*/  
function jsonFormat($data, $indent=null){  
  
	// 对数组中每个元素递归进行urlencode操作，保护中文字符  
	array_walk_recursive($data, 'jsonFormatProtect');
  
	// json encode  
	$data = json_encode($data);
  
	// 将urlencode的内容进行urldecode  
	$data = urldecode($data);
  
	// 缩进处理  
	$ret = '';
	$pos = 0;
	$length = strlen($data);
	$indent = isset($indent)? $indent : '    ';
	$newline = "\n";
	$prevchar = '';
	$outofquotes = true;
  
	for($i=0; $i<=$length; $i++){
  
		$char = substr($data, $i, 1);
  
		if($char=='"' && $prevchar!='\\'){
			$outofquotes = !$outofquotes;
		}elseif(($char=='}' || $char==']') && $outofquotes){
			$ret .= $newline;
			$pos --;
			for($j=0; $j<$pos; $j++){
				$ret .= $indent;
			}
		}
  
		$ret .= $char;
		  
		if(($char==',' || $char=='{' || $char=='[') && $outofquotes){
			$ret .= $newline;
			if($char=='{' || $char=='['){
				$pos ++;
			}
  
			for($j=0; $j<$pos; $j++){
				$ret .= $indent;
			}
		}
  
		$prevchar = $char;
	}
  
	return $ret;
}  
  
/** 将数组元素进行urlencode 
* @param String $val 
*/  
function jsonFormatProtect(&$val){
	if($val!==true && $val!==false && $val!==null){
		$val = urlencode($val);
	}
}

// 用于读取APP接口数据（原始请求数据流）
function get_input() {
	
	$c = file_get_contents('php://input');
	$data = json_decode($c, true);
	
	
	/* 如果与第三方有通信验证，则开启此段代码
	$app_id = in($data['app_id']);			// App_ID iemkdow2ndfldh77ld520
	if($app_id != $this->config['app_id']) {
		Response::json(403, null);
	}
	*/
	
	return $data;
}

// 用于读取APP接口数据，接收以form-data形式post过来的数据（图片上传）
function get_post_formdata() {
	
	/* 如果与第三方有通信验证，则开启此段代码
	$app_id = in($_POST['app_id']);			// App_ID iemkdow2ndfldh77ld520
	if($app_id != $this->config['app_id']) {
		Response::json(403, null);
	}
	*/
	
	return $_POST;
}