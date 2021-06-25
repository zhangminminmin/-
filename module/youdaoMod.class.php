<?php
/**
 *  有道词典翻译
 */
class youdaoMod extends commonMod 
{
    protected $userinfo;
    protected $curl_timeout = 2000;
    protected $url = "https://openapi.youdao.com/api";
    protected $appKey = "61d7185b6eb47c6d";
    protected $secKey = "QzHtx4NZxRtK63ElFEklVx0GfXiT6hIi";
    public function __construct() 
    {
        parent::__construct();
    }

    /**
     * 检测登录
     */
    public function checkLogin()
    {
        if (empty($_SESSION['user_id'])) {
            $this->ajaxReturn(203, "未登录");
        }

        $userinfo = $this->data_getinfo("user", "id='" . $_SESSION['user_id'] . "'");
        if (empty($userinfo['nickname']) || empty($userinfo['avatar'])) {
            $this->ajaxReturn(401, "请先去个人中心完善资料");
        }

        $this->userinfo = $userinfo;
    }

    /**
     * 检测是否可以进行听写朗读 等操作
     * 是会员的话  看是否到期 到期的话  不能操作
     * 不是会员的 不能操作
     * 
     */
    public function checkUser()
    {
        $userinfo = $this->userinfo;
        if ($userinfo['type'] != 3) {
            if ($userinfo['type'] == 2) {
                if ($userinfo['endtime'] < time()) {
                    $this->ajaxReturn(202, "会员已经到期 无法操作");
                }
            } else {
                $this->ajaxReturn(202, "不是会员，没有操作权限");
            } 
        }
        
    }


    public function do_request($q)
    {
        $salt = $this->create_guid();
        $args = array(
            'q' => $q,
            'appKey' => $this->appKey,
            'salt' => $salt,
        );
        $args['from'] = 'auto';
        $args['to'] = 'zh-CHS';
        $args['signType'] = 'v3';
        $curtime = strtotime("now");
        $args['curtime'] = $curtime;
        $signStr = $this->appKey . $this->truncate($q) . $salt . $curtime . $this->secKey;
        $args['sign'] = hash("sha256", $signStr);
        $ret = $this->call($this->url, $args);
        return $ret;
    }

    // 发起网络请求
    public function call($url, $args=null, $method="post", $testflag = 0, $timeout = 2000, $headers=array())
    {
        $ret = false;
        $i = 0;
        while($ret === false)
        {
            if($i > 1)
                break;
            if($i > 0)
            {
                sleep(1);
            }
            $ret = $this->callOnce($url, $args, $method, false, $timeout, $headers);
            $i++;
        }
        return $ret;
    }

    public function callOnce($url, $args=null, $method="post", $withCookie = false, $timeout = 2000, $headers=array())
    {
        $ch = curl_init();
        if($method == "post")
        {
            $data = $this->convert($args);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_POST, 1);
        }
        else
        {
            $data = $this->convert($args);
            if($data)
            {
                if(stripos($url, "?") > 0)
                {
                    $url .= "&$data";
                }
                else
                {
                    $url .= "?$data";
                }
            }
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if(!empty($headers))
        {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        if($withCookie)
        {
            curl_setopt($ch, CURLOPT_COOKIEJAR, $_COOKIE);
        }
        $r = curl_exec($ch);
        curl_close($ch);
        return $r;
    }

    public function convert(&$args)
    {
        $data = '';
        if (is_array($args))
        {
            foreach ($args as $key=>$val)
            {
                if (is_array($val))
                {
                    foreach ($val as $k=>$v)
                    {
                        $data .= $key.'['.$k.']='.rawurlencode($v).'&';
                    }
                }
                else
                {
                    $data .="$key=".rawurlencode($val)."&";
                }
            }
            return trim($data, "&");
        }
        return $args;
    }

    // uuid generator
    public function create_guid()
    {
        $microTime = microtime();
        list($a_dec, $a_sec) = explode(" ", $microTime);
        $dec_hex = dechex($a_dec* 1000000);
        $sec_hex = dechex($a_sec);
        $this->ensure_length($dec_hex, 5);
        $this->ensure_length($sec_hex, 6);
        $guid = "";
        $guid .= $dec_hex;
        $guid .= $this->create_guid_section(3);
        $guid .= '-';
        $guid .= $this->create_guid_section(4);
        $guid .= '-';
        $guid .= $this->create_guid_section(4);
        $guid .= '-';
        $guid .= $this->create_guid_section(4);
        $guid .= '-';
        $guid .= $sec_hex;
        $guid .= $this->create_guid_section(6);
        return $guid;
    }

    public function create_guid_section($characters)
    {
        $return = "";
        for($i = 0; $i < $characters; $i++)
        {
            $return .= dechex(mt_rand(0,15));
        }
        return $return;
    }

    public function truncate($q)
    {
        $len = $this->abslength($q);
        return $len <= 20 ? $q : (mb_substr($q, 0, 10) . $len . mb_substr($q, $len - 10, $len));
    }

    public function abslength($str)
    {
        if(empty($str)){
            return 0;
        }
        if(function_exists('mb_strlen')){
            return mb_strlen($str,'utf-8');
        }
        else {
            preg_match_all("/./u", $str, $ar);
            return count($ar[0]);
        }
    }

    public function ensure_length(&$string, $length)
    {
        $strlen = strlen($string);
        if($strlen < $length)
        {
            $string = str_pad($string, $length, "0");
        }
        else if($strlen > $length)
        {
            $string = substr($string, 0, $length);
        }
    }

    public function searchWords()
    {
        // 输入
        $input = in($_POST);
        $content = $input['content'];

        $ret = $this->do_request($content);
        // print_r($ret);
        $ret = json_decode($ret, true);
        $len = count($ret['translation']);
        $translation = "";
        for ($i=0; $i < $len; $i++) { 
            $translation .= " " . $ret['translation'][$i];
        }
        $param = array(
            "translation" => $translation,
        );

        $this->ajaxReturn(200, "获取翻译成功", $param);
    }
    


}
?>