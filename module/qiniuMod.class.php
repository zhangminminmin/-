<?php

use Qiniu\Auth;
use Qiniu\Processing\PersistentFop;

class qiniuMod extends commonMod 
{
    protected $accessKey = "Yz7r1mtiwEJAuIAdebqCEMEDI1BRUqtsOI4eWa7O";
    protected $secretKey = "3TsFJNLMcCHBDSbOj6vm8UxtboDRGcHvC8a6YbRr";
    // protected $bucketName = "video-audio-test"; // 空间名称
    protected $bucketName = "yuanqi"; // 空间名称
    protected $userinfo = "";
    // protected $domain = "http://q1xbajwsv.bkt.clouddn.com/";
    protected $domain = "http://qiuniu.xingtingyi.com/";
    protected $notifyUrl = "";
    public function __construct() 
    {
        parent::__construct();
        $this->notifyUrl = "http://" . $this->config['siteurl'] . "/index.php/qiniu/notify";
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
        $this->userinfo = $userinfo;
    }


    // 七牛云生成token 和 key
    public function createToken()
    {
        $this->checkLogin();
        $input = in($_GET);
        $accessKey = $this->accessKey;
        $secretKey = $this->secretKey;
        $bucket = $this->bucketName;

        $auth = new Auth($accessKey, $secretKey);
        $token = $auth->uploadToken($bucket);

//        file_put_contents('11111112.txt',$input['type']);
//        file_get_contents('11111112.txt',$input['type']);
//        echo $input['type'];die;
        if (isset($input['type']) && $input['type'] == 1) {
            $key = time() . "_" . $_SESSION['user_id'] . "_" . rand(10000, 99999) . ".mp4";
        }else{
            $key = time() . "_" . $_SESSION['user_id'] . "_" . rand(10000, 99999) . ".mp3";
        }

        $param = array(
            "token" => $token,
            "key" => $key,
        );
        $this->ajaxReturn(200, "获取token成功", $param);
    }

    
    /**
     *  合成音频
     *  source_id 资源主表的id
     *  type 1平台  2个人  3精品
     */
    public function mergeAudioInfo()
    {
        $this->checkLogin();
        $input = in($_POST);
        if (empty($input['source_id'])) {
            $this->ajaxReturn(202, "参数错误，请刷新重试");
        }

        if (empty($input['type'])) {
            $this->ajaxReturn(202, "参数错误，请刷新重试");
        }

        $type = $input['type'];
        if ($input['type'] == 3) {
            $type = 4;
        }
        $accessKey = $this->accessKey;
        $secretKey = $this->secretKey;
        $bucket = $this->bucketName;
        $domain = $this->domain;

        $auth = new Auth($accessKey, $secretKey);
        // 所有的音频文件
        $sql = "where source_id = '" . $input['source_id'] . "' and user_id = '" . $_SESSION['user_id'] . "' and type = '" . $type . "'";
        $list = $this->data_list("source_read", $sql);
        if (empty($list)) {
            $this->ajaxReturn(202, "暂时没有朗读信息 无法合成#1");
        }

        $pp = "";
        $len = count($list);
        if ($len == 1) {
            $this->ajaxReturn(202, "一段音频无法合成");
        }

        for($i=1; $i < $len; $i++) {
            $pp .= \Qiniu\base64_urlSafeEncode($domain . $list[$i]['path']);
            if ($i != $len - 1) {
                $pp .= "/"; 
            }
        }
        //要转码的文件所在的空间和文件名。
        $key = $list[0]['path'];
        $mergeAudio = time() . "_" . $_SESSION['user_id'] . "_" . $input['source_id'] . ".mp3";

        //转码是使用的队列名称。 https://portal.qiniu.com/mps/pipeline
        $pipeline = 'hefengxun_pipeline';
        $force = false;

        //转码完成后通知到你的业务服务器。
        $notifyUrl = $this->notifyUrl;
        $notifyUrl .= "?source_id='" . $input['source_id'] . "'&user_id='" . $_SESSION['user_id'] . "'&type='" . $input['type'] ."'";
        $config = new \Qiniu\Config();
        //$config->useHTTPS=true;

        $pfop = new PersistentFop($auth, $config);

        //要进行转码的转码操作。 http://developer.qiniu.com/docs/v6/api/reference/fop/av/avthumb.html
        $fops = "avconcat/2/format/mp3/" . $pp . "|saveas/" . \Qiniu\base64_urlSafeEncode($bucket . ":" . $mergeAudio);

        list($id, $err) = $pfop->execute($bucket, $key, $fops, $pipeline, $notifyUrl, $force);

        if ($err != null) {
            $this->ajaxReturn(202, "音频合成失败！");
        } else {
            $param = array("id" => $id);
            $data = array(
                "user_id" => $_SESSION['user_id'],
                "source_id" => $input['source_id'],
                "path" => $mergeAudio,
                "created_at" => time(),
                "updated_at" => time(),
                "type" => $input['type'],
                "status" => 1,
            );
            $where = "user_id = " . $_SESSION['user_id'] . " and source_id = " . $input['source_id'] . " and type = " . $input['type'];
            $info = $this->data_getinfo("merge_audio", $where);
            if (empty($info)) {
                $this->data_add("merge_audio", $data);
            } else {
                $this->data_edit("merge_audio", $data, " id = '" . $info['id'] . "'");
            }
            $this->ajaxReturn(200, "音频合成中...！", $param);
        }
    }

    public function notify()
    {
        $input = in($_GET);

        $user_id  = str_replace("\\", "", $input['user_id']);
        $source_id  = str_replace("\\", "", $input['source_id']);
        $type  = str_replace("\\", "", $input['type']);
        $sql = "user_id = " . $user_id . " and source_id = " . $source_id . " and type = " . $type;

        $info = $this->data_getinfo("merge_audio", $sql);
        $edit = $this->data_edit("merge_audio", array("status" => 2), $sql);

        $this->ajaxReturn(200, "视频合成成功");
    }


    /**
     * 我的听写朗读(平台  个人  精品课程的)
     * audioList 上传之后key列表
     */
    public function  dictationRead()
    {
        $this->checkLogin();
        $input = $this->post;
        // print_r($input);
        $accessKey = $this->accessKey;
        $secretKey = $this->secretKey;
        $bucket = $this->bucketName;
        $domain = $this->domain;

        $auth = new Auth($accessKey, $secretKey);
        
        $audioList = $input['audioList'];
        if (empty($audioList)) {
            $this->ajaxReturn(202, "音频文件为空 ，无法合成！");
        }
        if (! is_array($audioList)) {
            $this->ajaxReturn(202, "数据参数出错，刷新重试");
        }

        $pp = "";
        $len = count($audioList);
        if ($len == 1) {
            $this->ajaxReturn(202, "单条信息无法合成#2");
        }

        for($i=1; $i < $len; $i++) {
            $pp .= \Qiniu\base64_urlSafeEncode($domain . $audioList[$i]);
            if ($i != $len - 1) {
                $pp .= "/"; 
            }
        }
        //要转码的文件所在的空间和文件名。
        $key = $audioList[0];
        $mergeAudio = "dictationRead_" . time() . "_" . $_SESSION['user_id'] . "_" . rand(10000, 99999) . ".mp3";

        //转码是使用的队列名称。 https://portal.qiniu.com/mps/pipeline
        $pipeline = 'hefengxun_pipeline';
        $force = false;

        //转码完成后通知到你的业务服务器。
        $notifyUrl = null;
        $config = new \Qiniu\Config();
        //$config->useHTTPS=true;

        $pfop = new PersistentFop($auth, $config);

        //要进行转码的转码操作。 http://developer.qiniu.com/docs/v6/api/reference/fop/av/avthumb.html
        $fops = "avconcat/2/format/mp3/" . $pp . "|saveas/" . \Qiniu\base64_urlSafeEncode($bucket . ":" . $mergeAudio);

        list($id, $err) = $pfop->execute($bucket, $key, $fops, $pipeline, $notifyUrl, $force);

        if ($err != null) {
            $this->ajaxReturn(202, "音频合成失败！");
        } else {
            $param = array("id" => $id);
            $this->ajaxReturn(200, "音频合成中...！", $param);
        }
    }


    /**
     *  查询进度
     *  id  获取进度查询id
     */
    public function findPlan()
    {
        $input = in($_POST);
        $accessKey = $this->accessKey;
        $secretKey = $this->secretKey;
        $bucket = $this->bucketName;
        if (empty($input['id'])) {
            $this->ajaxReturn(202, "网络原因请刷新重试");
        }
        $auth = new Auth($accessKey, $secretKey);

        $config = new \Qiniu\Config();
        $pfop = new PersistentFop($auth, $config);

        //查询转码的进度和状态
        list($ret, $err) = $pfop->status($input['id']);
        if ($err != null) {
            $this->ajaxReturn(202, "合成音频失败");
        } else {
            if ($ret['code'] != 0) {
                $msg = $ret['items'][0]['error'] == null ? $ret['items'][0]['desc'] : $ret['items'][0]['error'];
                $this->ajaxReturn(202, $msg, $ret);
            }

            $param = array(
                "key" => $ret['items'][0]['key'],
            );

            $this->ajaxReturn(200, "视频合成成功", $param);
        }
    }


    /**
     * 我的朗读合成音频
     */
    public function dictationRead1()
    {
        
    }

}
?>