<?php
	header("Content-type: text/html; charset=utf-8;");
	error_reporting(0);
	include_once("./log_.php");
	include_once("./WxPayPubHelper/WxPayPubHelper.php");
	require_once '../../inc/config.php';
	echo $config['POINT_GET'];
    //使用通用通知接口
	$notify = new Notify_pub();

	//存储微信的回调
	//验证签名，并回应微信。
    //对后台通知交互时，如果微信收到商户的应答不是成功或超时，微信认为通知失败，
    //微信会通过一定的策略（如30分钟共8次）定期重新发起通知，
    //尽可能提高通知的成功率，但微信不保证通知最终能成功。
	$xml = $GLOBALS['HTTP_RAW_POST_DATA'];	
	$notify->saveData($xml);
	
	if($notify->checkSign() == FALSE){
		$notify->setReturnParameter("return_code","FAIL");//返回状态码
		$notify->setReturnParameter("return_msg","签名失败");//返回信息
	}else{
		$notify->setReturnParameter("return_code","SUCCESS");//设置返回码
	}
	$returnXml = $notify->returnXml();
	//echo $returnXml;
		
	//以log文件形式记录回调信息
	$log_ = new Log_();
	$log_name="./notify_url.log";//log文件路径
	//$log_->log_result($log_name,"【接收到的notify通知】:\n".$xml."\n");

	if($notify->checkSign() == TRUE)
	{
		if ($notify->data["return_code"] == "FAIL") {
			
			//此处应该更新一下订单状态，商户自行增删操作
			//$log_->log_result($log_name,"【通信出错】:\n".$xml."\n");
			//file_put_contents("2.txt", "fail");
			exit('FAIL');
		}
		elseif($notify->data["result_code"] == "FAIL"){
			
			//此处应该更新一下订单状态，商户自行增删操作
			//$log_->log_result($log_name,"【业务出错】:\n".$xml."\n");
			//file_put_contents("2.txt", "fail");
			exit('FAIL');
		}
		else{
			
			//此处应该更新一下订单状态，商户自行增删操作
            //$log_->log_result($log_name,"【支付成功】:\n".$xml."\n");
			
			require('../../inc/data.php');
			$link = mysql_connect($config['DB_HOST'], $config['DB_USER'], $config['DB_PWD']);
			mysql_select_db($config['DB_NAME']);
			
			// 统一编码为utf8
			mysql_query("set character set 'utf8'");	//读库
			mysql_query("set names 'utf8'");	//写库
			
			preg_match('/<out_trade_no><\!\[CDATA\[(.+?)\]\]><\/out_trade_no>/', $xml, $arr);
			preg_match('/<cash_fee><\!\[CDATA\[(.+?)\]\]><\/cash_fee>/', $xml, $arr1);
			
			$res = set_order($arr[1], $arr1[1] / 100);
			
			if($res){
				exit('SUCCESS');
			}else{
				exit('FAIL');
			}
			
		}
	}

function set_order($order_sn, $total) {
	
	//$rs = explode('-', $order_sn);
	//file_put_contents("0.txt", $order_sn.",".$rs[0].",".$rs[1].",".$total);
	
	if(substr($order_sn, 0, 2) == 'LZ'){
		$rs = explode('-', $order_sn);
		$order_id = $rs[0];
		$user_id = $rs[1];
		$date = time();
		$intro = "";
		
		switch($rs[2]) {
			case 1:
				$intro = "律师预约";
				return update_order($user_id, $order_id, $date, $intro, $total);
				break;
				
			case 2:
				$intro = "文书定制";
				return update_order($user_id, $order_id, $date, $intro, $total);
				break;
				
			case 3:
				$intro = "公司套餐";
				return update_order($user_id, $order_id, $date, $intro, $total);
				break;
				
			case 4:
				$intro = "账户充值";
				return recharge($user_id, $order_id, $date, $intro, $total);
				break;
		}
		
		
	}
	
	return false;
}

// 充值成功后的处理
function recharge($user_id, $order_id, $date, $intro, $total) {
	
	$res = mysql_query("select * from dc_member where `id` = '$user_id'");
	$user_info = mysql_fetch_array($res);
	$point_get = floor($total) * $config['POINT_GET'];
	
	// 账户金额流水
	mysql_query("insert into dc_user_cash (user_id, order_id, intro, `date`, result) values ($user_id, '$order_id', '账户充值', $date, $total)");
	
	// 账户积分流水
	mysql_query("insert into dc_user_point (user_id, order_id, intro, `action_id`, `date`, result) values ($user_id, '$order_id', '账户充值', 5, $date, $point_get)");
	
	// 更新用户表信息
	mysql_query("update dc_member set `fund`=`fund`+$total, points=points+$point_get where `id`=$user_id");
	
	$r = mysql_query("insert into dc_order (`id`, `user_id`, `is_company`, `goods_name`, `goods_id`, `lawyer_id`, `goods_type_id`, `status`, `date`, `price`, `user_name`, `user_sex`, `user_tel`, `com_name`, `com_contact`, `com_tel`) values ('$order_id', $user_id, ".$user_info['is_company'].", '账户充值', 0, 0, 4, 4, $date, $total, '".$user_info['name']."', '".$user_info['sex']."', '".$user_info['tel']."', '".$user_info['com_name']."', '".$user_info['com_contact']."', '".$user_info['com_tel']."')");
	
	if($r && mysql_affected_rows() > 0){
		return true;
	} else {
		return false;
	}
}

// 订单成功后的处理
function update_order($user_id, $order_id, $date, $intro, $total) {
	
	if($user_id && $order_id){
			
		// 1. 更新coupon_details表优惠券使用时间
		// 2. 更新user_point积分流水时间
		// 3. 更新user_cash金额流水时间
		// 4. 插入user_cash充值、支付流水
		
		$res = mysql_query("select coupon_id from dc_order where order_sn = '$order_sn'");
		$coupon_details = mysql_fetch_array($res);
		$coupon_id = $coupon_details['coupon_id'];
		
		if(!empty($coupon_id)) {
			mysql_query("update dc_coupon_details set is_use = 1, use_date = '".date('Y-m-d H:i:s', $date)."' where `id` = $coupon_id and user_id = $user_id");
		}
		
		mysql_query("update dc_user_point set date = $date where `order_id` = '$order_id' and `user_id` = $user_id");
		mysql_query("update dc_user_cash set date = $date where `order_id` = '$order_id' and `user_id` = $user_id");
		
		mysql_query("insert into dc_user_cash (user_id, order_id, intro, `date`, result) values ($user_id, '$order_id', '账户充值', $date, $total)");
		mysql_query("insert into dc_user_cash (user_id, order_id, intro, `date`, result) values ($user_id, '$order_id', '".$intro."订单支付', $date, -$total)");
		
		$r = mysql_query("update dc_order set status = 1, date = $date where `id` = '$order_id' and status = '0' and user_id=$user_id");
		
		if($r && mysql_affected_rows() > 0){
			return true;
		} else {
			return false;
		}
	} else {
		return false;
	}
}
?>