<?php
/**
 *  百度翻译
 */
class baiduMod extends commonMod 
{
    protected $userinfo;
    protected $curl_timeout = 2000;
    protected $url = "https://fanyi-api.baidu.com/api/trans/vip/translate";
    protected $appKey = "20191115000357368";
    protected $secKey = "2FpDEY9jhkKm8WNqkLWL";
    public function __construct() 
    {
        parent::__construct();
    }

        //翻译入口
    public function translate($query, $from = "auto", $to = 'auto')
    {
        $args = array(
            'q' => $query,
            'appid' => $this->appKey,
            'salt' => rand(10000,99999),
            'from' => $from,
            'to' => $to,

        );
        $args['sign'] = $this->buildSign($query, $this->appKey, $args['salt'], $this->secKey);
        $ret = $this->call($this->url, $args);
        $ret = json_decode($ret, true);
        return $ret; 
    }

    //加密
    public function buildSign($query, $appID, $salt, $secKey)
    {/*{{{*/
        $str = $appID . $query . $salt . $secKey;
        $ret = md5($str);
        return $ret;
    }/*}}}*/

    //发起网络请求
    public function call($url, $args=null, $method="post", $testflag = 0, $timeout = CURL_TIMEOUT, $headers=array())
    {/*{{{*/
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
    }/*}}}*/

    public function callOnce($url, $args=null, $method="post", $withCookie = false, $timeout = CURL_TIMEOUT, $headers=array())
    {/*{{{*/
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
    }/*}}}*/

    public function convert(&$args)
    {/*{{{*/
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
    }/*}}}*/

    public function test()
    {
        // 输入
        $input = in($_POST);
        $content = $input['content'];

        $ret = $this->translate($content);
        // print_r($ret);
        // $ret = json_decode($ret, true);
        print_r($ret);
    }
    


}
?>