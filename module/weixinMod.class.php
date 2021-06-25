<?php

/*
 * @微信控制器
 * @Copyright  ahaiba.com
 * @Author  Jeff<jeffchou@qq.com>    2016-7-19
 * @Version 1.0
 */

class weixinMod extends commonMod {

    private $token;
    private $data = array();
    public $fans;
    public $mykey;
    public $chatkey;
    public $wxuser;
    public $apiServer;
    public $siteUrl;
    public $user;

    public function __construct() {
        parent::__construct();
    }
    
    // Jeff PHP服务器接受微信的推送事件消息
    public function index() {

        $this->siteUrl = $this->config['siteurl'];
        if (!class_exists('SimpleXMLElement')) {
            exit('SimpleXMLElement class not exist');
        }
        if (!function_exists('dom_import_simplexml')) {
            exit('dom_import_simplexml function not exist');
        }
        $this->token = htmlspecialchars($_GET['token']);
        
        // 匹配微信借口的token与本地定义的是否一致
        if (!preg_match("/^[0-9a-zA-Z]{3,42}$/", $this->token)) {
            exit('error token');
        }
        if ($this->token <> $this->config['WX_token']) {
            exit('error token');
        }
        
        // 公众号用户信息
        $wxuser = array(
            'aeskey' => $this->config['WX_aeskey'],
            'appid' => $this->config['WX_appid'],
            'encode' => $this->config['WX_encode']
        );
        
        
        $weixin = $this->cpweixin($this->token, $wxuser);   // 获取微信对象，返回一个cpWechat对象

        $data = $weixin->request();
        $this->data = $data;

        list($content, $type) = $this->reply($data);
        $weixin->response($content, $type);
    }
	
	
	// Jeff 授权登陆（本站授权）
	public function oauth() {
        
        $code = isset($_GET['code']) ? htmlspecialchars($_GET['code']) : '';
        $state = isset($_GET['state']) ? htmlspecialchars($_GET['state']) : '';
		
        if (!empty($code) && !empty($state)) {
            $url_get = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . $this->config['WX_appid'] . '&secret=' . $this->config['WX_appsecret'] . '&code=' . $code . '&grant_type=authorization_code';
            $json = json_decode($this->curlGet($url_get));
			$openid = $json->openid;
			
			if( empty($openid) ) {
				header("location: /index.php/" . $state);
				exit;
			}
			
			// 通过scope=snsapi_base方式，只能获取openid流程到此结束
			// 通过scope=snsapi_userinfo方式，继续走下面的流程，可用临时的token换取微信用户信息
			
            $user_info = model('weixin')->get_member($openid);
			if(!$user_info) {
				
				$wx_url = 'https://api.weixin.qq.com/sns/userinfo?access_token='.$json->access_token.'&openid='.$openid.'&lang=zh_CN';
				$classData = json_decode(httpGet($wx_url));
				$new_user['name'] = str_replace(array("'", "\\"), array(''), $classData->nickname);
				$new_user['sex'] = $classData->sex == 1 ? "男" : "女";
				$new_user['city'] = $classData->city;
				$new_user['province'] = $classData->province;
				$new_user['avatar'] = $classData->headimgurl;
				
				$new_user['openid'] = $openid;
				$new_user['oauth_date'] = time();
				model('weixin')->add_member($new_user);
				
				$user_info = model('weixin')->get_member($openid);
			}
			
            $_SESSION["userid"] = $user_info['id'];
			
			header("location: /index.php/" . $state);
			exit;
			
        } else {
            echo '发现错误！';
            exit;
        }
    }
    
    // Jeff PHP服务器处理后响应给微信前端
    private function reply($data) {
        
        switch($data['Event']) {
            
            case 'CLICK':
                // 用户点击自定义菜单
                // 事件KEY值，与自定义菜单接口中KEY值对应
                $data['Content'] = $data['EventKey'];
                $this->data['Content'] = $data['EventKey'];
                return $this->keyword($data['Content']);
                break;
                
            case 'subscribe':
                // 关注/取消关注事件，事件类型，subscribe(订阅)、unsubscribe(取消订阅)
                // 获取关注后的欢迎信息
                $follow_data = model('weixin')->areply();	// 只有一条数据
				
				$openid = $data['FromUserName'];	// 发送方帐号（OpenID）
				if( empty($openid) ) { exit; }
                
				// 根据openid获取关注人的用户信息
                $user_info = array('openid' => $openid, 'att_date' => time());
				// 全局缓存access_token和jsapi_ticket到站点data目录下
                $access_token = getAccessToken($this->config['WX_appid'], $this->config['WX_appsecret']);
				
                if ($access_token) {
                
                    // 获取用户信息
                    $wx_url = 'https://api.weixin.qq.com/cgi-bin/user/info?openid=' . $openid . '&access_token=' . $access_token;
                    $classData = json_decode(httpGet($wx_url));
                    if ($classData->subscribe == 1) {
                        $user_info['name'] = str_replace(array("'", "\\"), array(''), $classData->nickname);
                        $user_info['sex'] = $classData->sex == 1 ? "男" : "女";
                        $user_info['city'] = $classData->city;
                        $user_info['province'] = $classData->province;
                        $user_info['avatar'] = $classData->headimgurl;
                        $user_info['att_date'] = $classData->subscribe_time;
                    }
                    // 保存用户信息
                    model('weixin')->add_member($user_info);
                }
                
                // 首页功能
                if ($follow_data['home'] == 1) {
                    return $this->keyword($follow_data['keyword']);	// Jeff 默认返回欢迎信息
                } else {
                    if ($follow_data['keyword'] != "") {
                        return $this->keyword($follow_data['keyword']);	// Jeff 默认返回欢迎信息 + 更多
                    } else {
						// 为0时，直接返回默认欢迎信息
                        return array(html_entity_decode($follow_data['content']), 'text');
                    }
                }
                break;
                
            case 'unsubscribe':
			
				$openid = $data['FromUserName'];	// 发送方帐号（OpenID）
				if( empty($openid) ) { exit; }
				
                // 取消关注，更新用户数据
                model('weixin')->update_member(array('status' => 0, 'un_date' => time()), $openid);
                break;
                
            case 'LOCATION':
                return $this->nokeywordApi();
                break;
        }
        
    }
    
    // 更新用户当前地理位置
    private function nokeywordApi() {
        // 国标转换为百度坐标
		$a_d = $this->Convert_GCJ02_To_BD092($this->data['Latitude'], $this->data['Longitude']);
		$url = 'http://api.map.baidu.com/geocoder/v2/?ak=ixLALgQVp5XmBPUtGSM4fcYE&location='.$a_d['lat'].','.$a_d['lng'].'&output=json&pois=1';
		$json2 = json_decode($this->curlGet($url));
		
		return model('weixin')->update_member(
		  array(
		      'city' => str_replace('市', '', $json2->result->addressComponent->city),
		      'province' => str_replace('省', '', $json2->result->addressComponent->province)
		  ),
		  $this->data['FromUserName']
		);
		
		//return array($json2->result->addressComponent->city, 'text');   // 合肥市
    }
	
	// Jeff 根据关键字匹配回复信息
    private function keyword($key) {
		
        $like['keyword'] = $key;
        // $like['token'] = $this->token;

        $data = model('weixin')->keyword($like);
        //print_r($like);exit;
        if ($data != false) {
            switch ($data['module']) {
                case 'Text':
                    $like['type'] = 2;
                    $back = model('weixin')->text($like);
                    if ($back) {
                        return array(text_out($back['text']), 'text');
                    } else {
                        return array(('‘' . $data['keyword']) . '’无此图文信息或图片,请提醒商家，重新设定关键词', 'text');
                    }

                case 'Img':
                    $like['type'] = 1;
                    $back = model('weixin')->img($like);

                    if ($back == false) {
                        return array(('‘' . $data['keyword']) . '’无此图文信息或图片,请提醒商家，重新设定关键词', 'text');
                    }
                    // $idsWhere = 'id in (';
                    $comma = '';
                    foreach ($back as $keya => $infot) {
                        //$idsWhere.=$comma . $infot['id'];
                        $comma = ',';
                        if ($infot['url'] != false) {
                            //处理外链
                            if (!(strpos($infot['url'], 'http') === FALSE)) {
                                $url = html_entity_decode($infot['url']);
                            } else {//内部模块的外链
                                //$url = $this->getFuncLink($infot['url']);
                            }
                        } else {
                            $url = trim($this->siteUrl, '/') . "/index.php/showinfo/?aid=" . $infot['id']; //显示内容详细地址;
                        }
                        if ($infot['id'] == 276) {
                            $url = trim($this->siteUrl, '/') . "/index.php/yingxiao/?openid=" . $this->data['FromUserName']; //活动跳转
                        }
						
						$pic_url = trim($this->siteUrl, '/') . $infot['pic'];
						if(!strpos($pic_url, 'http://')) {
							$pic_url = 'http://'.$pic_url;
						}
                        $return[] = array($infot['title'], $this->handleIntro($infot['text']), $pic_url, $url);
                    }
                    // $idsWhere.=')';

                    return array($return, 'news');
                    break;
            }
        }
    }

    private function getFuncLink($u) {
        $urlInfos = explode(' ', $u);
        switch ($urlInfos[0]) {
            default:
                $url = str_replace(array('{wechat_id}', '{siteUrl}', '&amp;'), array($this->data['FromUserName'], $this->siteUrl, '&'), $urlInfos[0]);
                break;
            case '刮刮卡':
                $Lottery = M('Lottery')->where(array('token' => $this->token, 'type' => 2, 'status' => 1))->order('id DESC')->find();
                $url = $this->siteUrl . U('Wap/Guajiang/index', array('token' => $this->token, 'wecha_id' => $this->data['FromUserName'], 'id' => $Lottery['id']));
                break;
        }
        return $url;
    }
    
    
    private function curlGet($url, $method = 'get', $data = '') {
        $ch = curl_init();
        $header = array("Accept-Charset: utf-8");
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
		if (ini_get('open_basedir') == '' && ini_get('safe_mode' == 'Off')) {
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		}
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $temp = curl_exec($ch);
        return $temp;
    }
    
    // 过滤函数
    public function handleIntro($str) {
        $str = html_entity_decode(htmlspecialchars_decode($str));
        $search = array('&amp;', '&quot;', '&nbsp;', '&gt;', '&lt;');
        $replace = array('&', '"', ' ', '>', '<');
        return strip_tags(str_replace($search, $replace, $str));
    }
	
	/**
	* 中国正常GCJ02坐标---->百度地图BD09坐标
	* 腾讯地图用的也是GCJ02坐标
	* @param double $lat 纬度
	* @param double $lng 经度
	*/
	function Convert_GCJ02_To_BD092($lat, $lng) {
        $x_pi = 3.14159265358979324 * 3000.0 / 180.0;
        $x = $lng;
        $y = $lat;
        $z = sqrt($x * $x + $y * $y) + 0.00002 * sin($y * $x_pi);
        $theta = atan2($y, $x) + 0.000003 * cos($x * $x_pi);
        $lng = $z * cos($theta) + 0.0065;
        $lat = $z * sin($theta) + 0.006;
        return array('lng' => $lng, 'lat' => $lat);
    }
	
	
	// 主站授权子站跳转接口，共用主站（认证服务号）授权登陆
	public function oauth_center() {
		
		$code = isset($_GET['code']) ? htmlspecialchars($_GET['code']) : '';
		$state = isset($_GET['state']) ? htmlspecialchars($_GET['state']) : '';
		
		if (!empty($code) && !empty($state)) {
			$url_get = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . $this->config['WX_appid'] . '&secret=' . $this->config['WX_appsecret'] . '&code=' . $code . '&grant_type=authorization_code';
			$json = json_decode($this->curlGet($url_get));
			
			
			//微信用户信息
			$url2 = 'https://api.weixin.qq.com/sns/userinfo?openid=' . $json->openid . '&access_token=' . $json->access_token . '&lang=zh_CN';
			$classData = json_decode(httpGet($url2));
			$datab['nickname'] = str_replace(array("'", "\\"), array(''), $classData->nickname);
			$datab['sex'] = $classData->sex;
			$datab['city'] = $classData->city;
			$datab['province'] = $classData->province;
			$datab['avatar'] = $classData->headimgurl;
			$datab['atttime'] = $classData->subscribe_time;
			
			// 跳转地址
			$query_params = 
				"oppenid=".$json->openid
				."&nickname=".$datab['nickname']."&sex=".$datab['sex']."&city=".$datab['city']
				."&province=".$datab['province']."&avatar=".$datab['avatar']
				."&atttime=".time()."&state=".$state;
			
			$url = $_GET['url'] . ( strpos($_GET['url'], '?') ? '&' : '?' );
			$url .= $query_params;
			
			Header("Location: ".$url);exit;
		} else {

			echo '失败#2';
			exit;
		}
	}
	
	// 接收主站返回的用户信息数据，执行主站授权登陆
	public function verify(){
		
		//http://zhoutian.weishuoju.com/index.php/weixin/verify/?oppenid=o0zcKs9H-KSrZe49rhMowmyQLM6o&nickname=Jeff&sex=1&city=浦东新区&province=上海&avatar=http://wx.qlogo.cn/mmopen/ia7Fg3uj1pOIUvmyg4wXsh8I0sJcrPbicfxFLCEhWsV8XsephSumsiahEpIyxnPgib5gloTBiaWqdAObKWhqRKZm3cQ/0&atttime=&state=index
		
		$user_info['openid'] = isset($_GET['oppenid']) ? in($_GET['oppenid']) : "";
		$state = isset($_GET['state']) ? in($_GET['state']) : "";	// 跳转参数
		
		if(empty($user_info['openid'])) {
			header("location: /index.php/index/");	// 获取不到再次跳转到index内，重新跳转微信的授权接口
			exit;
		}
		
		$user_info['name'] = isset($_GET['nickname']) ? in($_GET['nickname']) : "";
		$user_info['sex'] = isset($_GET['sex']) ? in($_GET['sex']) : "";
		$user_info['city'] = isset($_GET['city']) ? in($_GET['city']) : "";
		$user_info['province'] = isset($_GET['province']) ? in($_GET['province']) : "";
		$user_info['avatar'] = isset($_GET['avatar']) ? in($_GET['avatar']) : "";
		$user_info['att_date'] = isset($_GET['atttime']) ? in($_GET['atttime']) : "";
		$user_info['oauth_date'] = time();
		
		$user_id = model('weixin')->add_member($user_info);
		$_SESSION["userid"] = $user_id;
		
		//授权登录跳转
		header("location: /index.php/$state/");
		exit;
	}
}

?>