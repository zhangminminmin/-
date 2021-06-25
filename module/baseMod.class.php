<?php
/**
 * 基础接口
 * 
 */
use Firebase\JWT\JWT;
use Firebase\JWT\JWK;
class baseMod extends commonMod
{

    public function __construct() 
    {
        parent::__construct();
    }

    /**
     * 首页接口
     */
    public function homePage()
    {
        // logo图片
        $siteurl = $this->siteurl;
        $logoInfo = $this->data_list("form_data_logo", " where id>0 ", " limit 1");
        $logo = formatAppImageUrl($logoInfo[0]['image'], $siteurl);
        // banner图
        $banner = $this->data_list("form_data_banner", "where id > 0 ", " order by id desc");
        $bannerList = array();
        if (!empty($banner)) {
            foreach ($banner as $k => $val) {
                $bannerList[] = array(
                    "id" => $val['id'],
                    "image" => formatAppImageUrl($val['image'], $siteurl),
                    'image_pc' => formatAppImageUrl($val['image_pc'], $siteurl),
                    'image_pad' => formatAppImageUrl($val['image_pad'],$siteurl),
                );
            }
        }
        // 关于我们
        $aboutUs = $this->data_list("form_data_aboutUs", "where id>0", " order by id desc", " limit 1");
        $aboutUs[0]['content'] = getImgThumbUrl($aboutUs[0]['content'], $siteurl);
        $aboutUs[0]['description'] = getImgThumbUrl($aboutUs[0]['description'], $siteurl);
        // 系统介绍
        $introduce = $this->data_list("form_data_introduce", "where id>0", "order by id desc");
        $introduceList = array();
        if (!empty($introduce)) {
            foreach ($introduce as $k => $val) {
                $introduceList[] = array(
                    "image" => formatAppImageUrl($val['image'], $siteurl),
                    "description" => $val['description'],
                    "name" => $val['name'],
                );
            }
        }
        // 新闻列表
        $news = $this ->data_list("news", "where id>0", "order by sort desc,id desc", "limit 4");
        $newsList = array();
        if (!empty($news)) {
            foreach ($news as $k => $val) {
                $image = json_decode($val['imgs'],true);
                $newsList[] = array(
                    "id" => $val['id'],
                    "title" => $val['title'],
                    "description" => $val['description'],
                    "image" => formatAppImageUrl($image[0], $siteurl),
                    "created_at" => date("Y-m-d", $val['created_at']),
                );
            }
        }

        // 推荐的平台素材 首页推荐是 1
        $where = "where id > 0 and position like '%". ',1,' ."%'";
        $source = $this->data_list("source", $where, "order by id desc", " limit 4");
        $sourceList = array();
        if (!empty($source)) {
            foreach ($source as $k => $val) {
                $sourceList[] = array(
                    "title" => $val['title'],
                    "id" => $val['id'],
                    "image" => formatAppImageUrl($val['image'], $siteurl),
                    "created_at" => date("Y-m-d", $val['created_at']),
                    "type" => $val['type'],
                );
            }
        }
        // 首页系统弹窗公告
        $notice = $this->data_list('form_data_notice','where id >0', 'order by id desc', 'limit 1');
        $info = array(
            'id' => $notice[0]['id'] ? :0,
            'content' => $notice[0]['content'] ? :'',
        );
        $param = array(
            "logo" => $logo,
            "banner" => $bannerList,
            "aboutUs" => $aboutUs,
            "introduce" => $introduceList,
            "news" => $newsList,
            "source" => $sourceList,
            'notice' => $info,
        );
        $this->ajaxReturn(200, "获取信息成功", $param);
    }
    /**
     * 新闻分类
     */
    public function newsSort()
    {
        $list = $this->data_list('news_sort', 'where id > 0', ' order by id desc');
        $items = array();
        foreach($list as $k=> $val) {
            $items[] = array(
                'id' => $val['id'],
                'name' => $val['name'],
            );
        }
        $param = [
            'sort' => $items,
        ];
        $this->ajaxReturn(200, '分类获取成功！', $param);
    }
    /**
     * 首页新闻列表
     * page 分页分数
     */
    public function newsList()
    {
        $input = $this->post;
        $siteurl = $this->siteurl;


        $page = intval($input['page']) <= 0 ? 1 : intval($input['page']);
        $pageSize = intval($input['pageSize']) <= 0 ? 8 : intval($input['pageSize']);
        $where = "where id>0 ";
        if (!empty($input['sort_id'])) {
            $where .= ' and sort_id = ' . $input['sort_id'];
        }
        $count = $this->data_count("news", $where);

        $param = array(
            "newsList" => array(),
        );
        if (empty($count)) {
            $this->ajaxReturn(200, "暂时没有数据", $param);
        }
        $pagenum = ceil($count / $pageSize);
        if ($page > $pagenum) {
            $this->ajaxReturn(200, "已经没有数据了", $param);
        }

        $limit = " LIMIT " . ($page-1)*$pageSize . "," . $pageSize;
        $list = $this->data_list("news", $where, "order by id desc", $limit);
        $newsList = array();
        if(!empty($list)){  
            foreach ($list as $k => $val) {
                $image = json_decode($val['imgs'], true);
                $newsList[] = array(
                    "id" => $val['id'],
                    "title" => $val['title'],
                    "description" => $val['description'],
                    "image" => formatAppImageUrl($image[0], $siteurl),
                    "created_at" => date("Y-m-d", $val['created_at']),
                );
            }
        }
        $param = array(
            "newsList" => $newsList,
            "pageNum" => $pagenum,
        );
        $this->ajaxReturn(200, "数据列表获取成功", $param);


    }

    /**
     * 新闻热门接口
     * 
     */
    public function hotNewList()
    {
        $list = $this->data_list("news", " where id >0 and hot = 2");
        $hotNewsList = array();
        if (!empty($list)) {
            foreach ($list as $k => $val) {
                $hotNewsList[] = array(
                    "id" => $val['id'],
                    "title" => $val['title'],
                    "created_at" => date("Y-m-d", $val['created_at']),
                    "description" => $val['description'],
                );
            }
        }

        $param = array(
            "hotNewsList" => $hotNewsList,
        );
        $this->ajaxReturn(200, "热门获取成功", $param);
    }

    /**
     * 新闻详情
     * id 新闻列表页的id
     */
    public function newsInfo()
    {
        $input = $this->post;
        $siteurl = $this->siteurl;
        $id = intval($input['id']);
        if ($id < 0) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        $news = $this->data_getinfo("news", "id='" . $id . "'");
        if (empty($news)) {
            $this->ajaxReturn(202, "文章已经删除或者下架");
        }

        $newsinfo = array(
            "title" => $news['title'],
            "created_at" => date("Y-m-d H:i:s", $news['created_at']),
            "content" => getImgThumbUrl($news['content'], ''),
            'source_id' => (int)$news['source_id'] ? :0,
            'type' => (int)$news['type'] ? :0,
            'path' => $news['path'] ? :'',
            'path_title' => $news['path_title'] ? :'',
        );
        $this->ajaxReturn(200, "获取详情成功", $newsinfo);
    }
    /**
     * 首页的素材素材推荐的分类
     * 资源的类型
     */
    public function sourceType()
    {
        $sourceCategory = $this->config['source'];
        $this->ajaxReturn(200, "ok", $sourceCategory);
    }

    /**
     * 素材的列表
     * type 资源的类型的id
     * page 分页页数 1
     */
    public function  sourceList()
    {
        $siteurl = $this->siteurl;
        $input = $this->post;
        if (empty($input['type'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        $page = intval($input['page']) > 0 ? intval($input['page']) : 1;
        $pageSize = 8;

        $param = array("sourceList" => array());
        $where = "where id>0 and type='" . $input['type'] . "'  and position like '%" . ",1," . "%'";
        $count = $this->data_count("source", $where, "order by id desc");
        if (empty($count)) {
            $this->ajaxReturn(200, "暂时没有数据", $param);
        }
        $pagenum = ceil($count / $pageSize);

        if ($pagenum < $page) {
            $this->ajaxReturn(200, "数据加载完了", $param);
        }

        $limit = " LIMIT " . ($page-1) * $pageSize . "," . $pageSize;
        $list = $this->data_list("source", $where, "order by id desc", $limit);
        $sourceList = array();
        if (!empty($list)) {
            foreach ($list as $k => $val) {
                $sourceList[] = array(
                    "id" => $val['id'],
                    "image" => formatAppImageUrl($val['image'], $siteurl),
                    "title" => $val['title'],
                    "created_at" => date("Y-m-d H:i", $val['created_at']),
                );
            }
        }

        $param = array("sourceList" => $sourceList);
        $this->ajaxReturn(200, "获取数据成功", $param);
    }

    /**
     * 用户协议
     * 
     */
    public function agreement()
    {
        $siteurl = $this->siteurl;
        $list = $this->data_list("form_data_agreement", "where id > 0", " order by id desc", " limit 1");
        $info = $list[0];
        $info['content'] = getImgThumbUrl($info['content'], $siteurl);
        $param = array(
                "info" => $info,
            );
        $this->ajaxReturn(200, "获取信息成功", $param);
    }



    // PC端三方登录 微信登录
    public function otherWxLogin()
    {
        $appid = "wx3e4ef522a4332920";
        $redirect_uri = "https://" . $this->config['siteurl'] . "/index.php/base/notifyWxlogin";
        $redirect_uri = URLEncode($redirect_uri);
        $url = "https://open.weixin.qq.com/connect/qrconnect";
        $url .= "?appid=" . $appid;
        $url .= "&redirect_uri=" . $redirect_uri . "&response_type=code&scope=snsapi_login&state=index#wechat_redirect";

        $param = array("url" => $url);
        $this->ajaxReturn(200, "获取连接成功", $url);
    }

    // 微信登录的返回
    public function notifyWxlogin()
    {
        $input = in($_GET);
        $appid = "wx3e4ef522a4332920";
        $appsecret = "f79095d9a09cfa0a5d674fcc37942c59";
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token";
        if (!empty($input['code']) && !empty($input['state'])) {
            $url .= "?appid=" . $appid . "&secret=" . $appsecret . "&code=" . $input['code'];
            $url .= "&grant_type=authorization_code";

            $json = json_decode($this->curlGet($url));
            $openid = $json->openid;
            $unionid = $json->unionid;

            if (empty($unionid)) {
                Header("Location: https://" . $this->config['siteurl']);exit;
                exit;
            }
            $this->checkWechat($unionid, $openid);
        } else {
            echo "授权失败";
            exit;
        }
    }

    // 判断是否存在
    public function checkWechat($unionid, $openid)
    {
        $sql = " platform = 'wechat' and unionid = '" . $unionid . "'";
        $platformInfo = $this->data_getinfo("platform", $sql);
        if (empty($platformInfo)) {
            $data = array(
                "platform" => "wechat",
                "openid" => $openid,
                "user_id" => 0,
                "created_at" => time(),
                "unionid" => $unionid,
            );
            $addPlatform = $this->data_add("platform", $data);
            Header("Location: https://" . $this->config['siteurl'] . "/bind?platformId=" . $addPlatform);exit;
        }

        if (! empty($platformInfo['user_id'])) {
            $_SESSION['user_id'] = $platformInfo['user_id'];
            Header("Location: https://" . $this->config['siteurl']. "?isthird=1");exit;
        } else {
            Header("Location: https://" . $this->config['siteurl'] . "/bind?platformId=" . $platformInfo['id']);exit;
        }


    }
    // pc端三方登录 qq登录
    public function otherQQ()
    {
        $appid = "101837296";
        $redirect_uri = "https://" . $this->config['siteurl'] . "/index.php/base/otherQqlogin";
        $redirect_uri = URLEncode($redirect_uri);
        $url = "https://graph.qq.com/oauth2.0/authorize";
        $url .= "?response_type=code&client_id=" . $appid;
        $url .= "&redirect_uri=" . $redirect_uri;
        $url .= "&state=index";

        $param = array("url" => $url);
        $this->ajaxReturn(200, "获取连接成功", $param);
    }

    // 三方登录 QQ登录回调
    public function otherQqlogin()
    {
        $appid = "101837296";
        $appsecret = "1a81b5fea5d90324120d8725a0473a3c";

        $input = in($_GET);
        $redirect_uri = "https://" . $this->config['siteurl'] . "/index.php/base/otherQqlogin";
        $redirect_uri = URLEncode($redirect_uri);

        $url = "https://graph.qq.com/oauth2.0/token";
        if (!empty($input['code']) && !empty($input['state'])) {

            $url .= "?client_id=" . $appid . "&client_secret=" . $appsecret;
            $url .= "&code=" . $input['code'] . "&redirect_uri=" . $redirect_uri;
            $url .= "&grant_type=authorization_code";
            $json = $this->curlGet($url);

            $access_token = explode("&", $json);
            // 获取unionid
            if (!empty($access_token[0])) {
                $getUnionidUrl = "https://graph.qq.com/oauth2.0/me?" . $access_token[0] . "&unionid=1";
                $response = $this->curlGet($getUnionidUrl);
                
                // 获取unionid和openid
                if(strpos($response, "callback") !== false){

                    $lpos = strpos($response, "(");
                    $rpos = strrpos($response, ")");
                    $response  = substr($response, $lpos + 1, $rpos - $lpos -1);
                    $msg = json_decode($response);
                    if(isset($msg->error)){
                        echo "获取unionid和opendi失败";
                        exit;
                    }

                    $unionid = $msg->unionid;
                    $openid = $msg->openid;
                    if (!empty($unionid) && !empty($openid)){
                        $this->checkqq($unionid, $openid); 
                    }
                }
            }
        } else {
            echo "授权失败";
            exit;
        }
    }

    public function checkqq($unionid, $openid)
    {
        $sql = " platform='qq' and unionid='" . $unionid . "'";
        $platformInfo = $this->data_getinfo("platform", $sql);
        if (empty($platformInfo)) {
            $data = array(
                "platform" => "qq", 
                "openid" => $openid,
                "user_id" => 0,
                "created_at" => time(),
                "unionid" => $unionid,
            );
            $addPlatform = $this->data_add("platform", $data);
            Header("Location: https://" . $this->config['siteurl'] . "/bind?platformId=" . $addPlatform);exit;
        }

        if (! empty($platformInfo['user_id'])) {
            $_SESSION['user_id'] = $platformInfo['user_id'];
            Header("Location: https://" . $this->config['siteurl']. "?isthird=1");exit;
        } else {
            Header("Location: https://" . $this->config['siteurl'] . "/bind?platformId=" . $platformInfo['id']);exit;
        }
    }





        /*
     * @param $client_id
     * @param $redirect_uris
     */ 
    public function otherGoogleLogin()
    {
//        $redirect_uris = "https://" . $this->config['siteurl'] . "/index.php/base/notifyGoogleLogin";
        $redirect_uris = 'http://google.xingtingyi.com/notifyGoogle.php';
        $client_id = "683938591915-ose0jmtf7an2mo7ln2ivc2q93t5iih30.apps.googleusercontent.com";
        $redirect_uris=urlencode($redirect_uris);
        $scope=urlencode('https://www.googleapis.com/auth/userinfo.profile');
        $url = "https://accounts.google.com/o/oauth2/auth?response_type=code&access_type=offline&client_id={$client_id}&redirect_uri={$redirect_uris}&state&scope={$scope}&approval_prompt=auto";
        
        $param = array("url" => $url);
        $this->ajaxReturn(200, "获取Google连接成功", $param);
    }



//    public function notifyGoogleLogin()
//    {
//        $client_id = "683938591915-ose0jmtf7an2mo7ln2ivc2q93t5iih30.apps.googleusercontent.com";
//        $client_secret = 'ACIFOkm_XzukZGEE4FBoFQmJ';
//        $redirect_uri = "https://" . $this->config['siteurl'] . "/index.php/base/otherGoogleLogin";
//
//        if (empty($_GET['code'])) {
//            echo "信息获取失败";
//            die;
//        } else {
//            //用户允许授权后，将会重定向到redirect_uri的网址上，并且带上code参数
//            $code=$_GET['code'];
//            $postData=array(
//                'code'=>$code,
//                'client_id'=>$client_id,
//                'client_secret'=>$client_secret,
//                'redirect_uri'=>$redirect_uri,
//                'grant_type'=>'authorization_code'
//            );
//
//            //第二步：通过code获取access_token
//            $access_token=$this->getToken($postData);
//            if(empty($access_token)){
//                echo '获取TOKEN失败！';
//                die;
//            }
//
//            //第三步：通过access_token调用接口,获取用户信息
//            $user=$this->getUserInfo($access_token);
//            if (! $user['id']) {
//                echo "信息获取失败";
//                die;
//            }
//
//            $this->checkGoogle($user['id']);
//            //处理获取到的的用户数据。。。。。。
//
//        }
//    }

    public function notifyGoogleLogin()
    {
        $user_id = $_GET['user_id'];
        if (!empty($user_id)) {
            $this->checkGoogle($user_id);
        }else{
            die('授权失败！');
        }
    }

    public function checkGoogle($id)
    {
        $sql = " platform='google' and unionid='" . $id . "'";
        $platformInfo = $this->data_getinfo("platform", $sql);
        if (empty($platformInfo)) {
            $data = array(
                "platform" => "google", 
                "openid" => $id,
                "user_id" => 0,
                "created_at" => time(),
                "unionid" => $id,
            );
            $addPlatform = $this->data_add("platform", $data);
            Header("Location: https://" . $this->config['siteurl'] . "/bind?platformId=" . $addPlatform);exit;
        }

        if (! empty($platformInfo['user_id'])) {
            $_SESSION['user_id'] = $platformInfo['user_id'];
//            setcookie('PHPSESSID', session_id(), time()+36002430 ,'/');
//            setcookie('abc', session_id(), time()+36002430 ,'/');

            Header("Location: https://" . $this->config['siteurl']. "?isthird=1");exit;
        } else {
            Header("Location: https://" . $this->config['siteurl'] . "/bind?platformId=" . $platformInfo['id']);exit;
        }
    }
    
     /**
     * 抓取TOKEN
     * @param $postData
     * @param string $purl
     * @return bool
     */
    protected function getToken($postData,$purl='https://accounts.google.com/o/oauth2/token')
    {

        $fields = (is_array($postData)) ? http_build_query($postData) : $postData;
        $curlHeaders = [
            'content-type: application/x-www-form-urlencoded;CHARSET=utf-8',
            'Content-Length: ' . strlen($fields),
        ];

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $purl);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $curlHeaders);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($curl);
        $responseCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if($response && $responseCode == 200){

            $json = json_decode($response, true);
            $token=$json['access_token'];
            return $token;
        }else {
            return false;
        }

    }


    /**
     * 获取用户信息
     * @param $access_token
     * @return array
     */
    protected function getUserInfo($access_token){
        $url = "https://www.googleapis.com/oauth2/v1/userinfo?access_token=".$access_token;
        $userInfo = json_decode(file_get_contents($url),true);

        $data['id']=$userInfo['id'];                     //ID
        $data['name']=$userInfo['name'];                //用户名
        $data['locale']=$userInfo['locale'];           //语言
        $data['picture']=$userInfo['picture'];         //头像
        $data['given_name']=$userInfo['given_name'];   //名字
        $data['family_name']=$userInfo['family_name']; //姓

        return $data;
    }


    /*
    * 三方登录 ios
    * */
    public function otherIosLogin_test()
    {
        try {
            $userID = in($_POST['userID']);
            $identityToken = in($_POST['identityToken']);

            if(!$userID || !$identityToken){
                throw new \Exception("参数错误 请稍后重试！",202);
            }

            $verifyRes = $this->appleJwtVerify($identityToken);

            // var_dump($verifyRes);
            if(isset($verifyRes->jwtStatus) && $verifyRes->jwtStatus == 'failed'){
                throw new \Exception( $verifyRes['jwtMsg'],202);

            }else {
                $code = 301;
                $msg='请去绑定手机号码';
                $sql = "platform='ios' and unionid='" . $userID . "'";
                $oauth_info = $this->data_getinfo("platform", $sql);
                if (empty($oauth_info)) {
                    $data = array(
                        "platform" => 'ios',
                        "openid" => $userID,
                        "user_id" => 0,
                        "created_at" => time(),
                        "unionid" => $userID,
                    );
                    $platformId = $this->data_add("platform", $data);
                    $platformIdArr = ['platformid'=>$platformId];
                    //$this->ajaxReturn(301, "请去绑定手机号码", array("platformid"=> $platformId));    
                    // throw new \Exception('请去绑定手机号码',301,  $platformId);
                }elseif($oauth_info['user_id'] == 0) {
                    //$this->ajaxReturn(301, "请去绑定手机号码", array("platformid"=> $oauth_info['id']));
                    // throw new \Exception('请去绑定手机号码',301, array("platformid"=> $oauth_info['id']));
                    $platformIdArr = ['platformid'=>$oauth_info['id']];
                }else{
                    $code = 200;
                    $msg='登陆成功';
                    $platformIdArr=[];
                    $_SESSION['user_id'] = $oauth_info['user_id'];
                }
            }
        } catch (\Exception $e) {
            $this->ajaxReturn(202, $e->getMessage(), []);
        }

        $this->ajaxReturn($code, $msg, $platformIdArr);
    }


    /*
    * 三方登录 ios
    * */
    public function otherIosLogin()
    {
        try {
            $userID = in($_POST['userID']);
            $identityToken = in($_POST['identityToken']);

            if(!$userID || !$identityToken){
                throw new \Exception("参数错误 请稍后重试！",202);
            }

            $verifyRes = $this->appleJwtVerify($identityToken);

            // var_dump($verifyRes);
            if(isset($verifyRes->jwtStatus) && $verifyRes->jwtStatus == 'failed'){
                throw new \Exception( $verifyRes['jwtMsg'],202);

            }else {
                $code = 301;
                $msg='请去绑定手机号码';
                $sql = "platform='ios' and unionid='" . $userID . "'";
                $oauth_info = $this->data_getinfo("platform", $sql);
                if (empty($oauth_info)) {
                    $data = array(
                        "platform" => 'ios',
                        "openid" => $userID,
                        "user_id" => 0,
                        "created_at" => time(),
                        "unionid" => $userID,
                    );
                    $platformId = $this->data_add("platform", $data);
                    $platformIdArr = ['platformid'=>$platformId];
                    //$this->ajaxReturn(301, "请去绑定手机号码", array("platformid"=> $platformId));
                    // throw new \Exception('请去绑定手机号码',301,  $platformId);
                    //随机生成 手机号 和 区号
                    $nation_code = '123123';
                    do{
                        $mobile = rand(1000000000, 9999999999);
                        $info = $this->data_getinfo('user', ' mobile= ' . $mobile);
                    }while($info);
                    $data = array(
                        'nation_code' => $nation_code,
                        'mobile' => $mobile,
                        'nickname' => '用户' . substr($userID, -4),
                    );

                    $insertId = $this->data_add('user', $data);
                    $edit = $this->data_edit('platform', array('user_id' => $insertId) , ' id = ' . $platformId);
                    $code = 200;
                    $msg='登陆成功';
                    $platformIdArr=[];
                    $_SESSION['user_id'] = $insertId;
                }elseif($oauth_info['user_id'] == 0) {
                    //$this->ajaxReturn(301, "请去绑定手机号码", array("platformid"=> $oauth_info['id']));
                    // throw new \Exception('请去绑定手机号码',301, array("platformid"=> $oauth_info['id']));
                    //随机生成 手机号 和 区号
                    $nation_code = '123123';
                    do{
                        $mobile = rand(1000000000, 9999999999);
                        $info = $this->data_getinfo('user', ' mobile= ' . $mobile);
                    }while($info);
                    $data = array(
                        'nation_code' => $nation_code,
                        'mobile' => $mobile,
                        'nickname' => '用户' . substr($userID, -4),
                    );

                    $insertId = $this->data_add('user', $data);
                    $edit = $this->data_edit('platform', array('user_id' => $insertId) , ' id = ' . $oauth_info['id']);
                    $code = 200;
                    $msg='登陆成功';
                    $platformIdArr=[];
                    $_SESSION['user_id'] = $insertId;
                }else{
                    $code = 200;
                    $msg='登陆成功';
                    $platformIdArr=[];
                    $_SESSION['user_id'] = $oauth_info['user_id'];
                }
            }
        } catch (\Exception $e) {
            $this->ajaxReturn(202, $e->getMessage(), []);
        }

        $this->ajaxReturn($code, $msg, $platformIdArr);
    }

    private function appleJwtVerify($identityToken){
        //获取Apple公钥访问地址：https://appleid.apple.com/auth/keys
        //得到Apple公钥：

        $publicKeyKid = JWT::getPublicKeyKid($identityToken);
        $publicKeyData = self::fetchPublicKey($publicKeyKid);
        $publickKey = $publicKeyData['publicKey'];
        $alg = $publicKeyData['alg'];

//        $publickKey = "-----BEGIN PUBLIC KEY-----
//MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAiGaLqP6y+SJCCBq5Hv6p
//GDbG/SQ11MNjH7rWHcCFYz4hGwHC4lcSurTlV8u3avoVNM8jXevG1Iu1SY11qInq
//UvjJur++hghr1b56OPJu6H1iKulSxGjEIyDP6c5BdE1uwprYyr4IO9th8fOwCPyg
//jLFrh44XEGbDIFeImwvBAGOhmMB2AD1n1KviyNsH0bEB7phQtiLk+ILjv1bORSRl
//8AK677+1T8isGfHKXGZ/ZGtStDe7Lu0Ihp8zoUt59kx2o9uWpROkzF56ypresiIl
//4WprClRCjz8x6cPZXU2qNWhu71TQvUFwvIvbkE1oYaJMb0jcOTmBRZA2QuYw+zHL
//wQIDAQAB
//-----END PUBLIC KEY-----";//pem公钥 【也可以通过将RSA公钥modulus（N）和exponent（E）转换为PEM文件】


        $decoded = JWT::decode($identityToken, $publickKey, array($alg));
        return $decoded;
    }

    private static function encodeLength($length) {
        if ($length <= 0x7F) {
            return chr($length);
        }
        $temp = ltrim(pack('N', $length) , chr(0));
        return pack('Ca*', 0x80 | strlen($temp) , $temp);
    }
    /**
     * Fetch Apple's public key from the auth/keys REST API to use to decode
     * the Sign In JWT.
     *
     * @param string $publicKeyKid
     * @return array
     */
    public static function fetchPublicKey($publicKeyKid) {
        $publicKeys = file_get_contents('https://appleid.apple.com/auth/keys');
        $decodedPublicKeys = json_decode($publicKeys, true);

        if(!isset($decodedPublicKeys['keys']) || count($decodedPublicKeys['keys']) < 1) {
            throw new Exception('Invalid key format.');
        }

        $kids = array_column($decodedPublicKeys['keys'], 'kid');
        $parsedKeyData = $decodedPublicKeys['keys'][array_search($publicKeyKid, $kids)];
        $parsedPublicKey= JWK::parseKey($parsedKeyData);
        $publicKeyDetails = openssl_pkey_get_details($parsedPublicKey);

        if(!isset($publicKeyDetails['key'])) {
            throw new Exception('Invalid public key details.');
        }

        return [
            'publicKey' => $publicKeyDetails['key'],
            'alg' => $parsedKeyData['alg']
        ];
    }



    public function curlGet($url, $method = 'get', $data = '') {
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


    public function down()
    {
        $info = $this->data_list("form_data_share_pc", "where id > 0 ", "order by id desc", "limit 1");
        $this->assign('info', $info[0]);
        $this->display('down.html');
    }


    public function upload()
    {

    }

    /**
     * 小程序单独上传图片接口
     * $image
     */
    public function uploadImage()
    {
        $siteUrl = $this->siteurl;
        if (empty($_FILES['image'])) {
           $this->ajaxReturn(202, '没有上传图片');
        }

        $images = imageUpload($_FILES['image'], "image" . time() . '_' . rand(100000,999999));
        $image = $images[0];
        $param = [
            'image' => $image,
            'image_url' => $siteUrl . $image,
        ];
        return ajaxReturn(200, '图片上传成功！',$param);
    }


    /**
     * 小程序 下载链接
     */
    public function download_path()
    {
        $pathList = $this->data_list("form_data_download_path", 'where id> 0', " order by id desc", 'limit 1');
        $info = array(
            'ios_path' => $pathList[0]['ios_path'],
            'an_path' => $pathList[0]['an_path'],
        );
        $this->ajaxReturn(200,'链接获取成功！',$info);
    }


    /**
     * 启动页链接
     */
    public function start_path()
    {
        $siteurl = $this->siteurl;
        $pathList = $this->data_list("form_data_qd_path", 'where id> 0', " order by id desc", 'limit 1');
        $info = array(
            'path' => $pathList[0]['path'],
            'image' => formatAppImageUrl($pathList[0]['image'],$siteurl),
        );
        $this->ajaxReturn(200,'链接获取成功！',$info);
    }


    /**
     * 分享页面
     * userName 小程序的原始id
     * path 小程序的路径
     * source_id 素材的id
     * type 1平台素材  2打卡活动
     */
    public function shareData()
    {
        $input = $this->post;
        $siteurl = $this->siteurl;
        if (!$input['type']) {
            $this->ajaxReturn(202, '参数错误 刷新重试！');
        }

        if (!$input['source_id']) {
            $this->ajaxReturn(202, '参数错误 刷新重试！');
        }
        $url_path = $input['type'] == 1 ? '/pages/source/detail?id=' . $input['source_id'] : '/pages/punch/detail?id=' . $input['source_id'];
        $mini_appid = 'wxba14fc743ef3ce2a';
        $mini_appscret = '86834feb183d277ec5c57aa9e13c1144';

        //获取access_token
        $access_token ="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . $mini_appid . "&secret=" . $mini_appscret;

        $_SESSION['access_token_code'] ="";
        $_SESSION['expires_in'] = 0;
        $ACCESS_TOKEN ="";

        if(!isset($_SESSION['access_token_code']) || (isset($_SESSION['expires_in']) && time() >$_SESSION['expires_in']))
        {

            $json = $this->httpRequest($access_token );
            $json = json_decode($json,true);
            // var_dump($json);
            $_SESSION['access_token_code'] =$json['access_token'];
            $_SESSION['expires_in'] = time()+7200;
            $ACCESS_TOKEN =$json["access_token"];
        }
        else{

            $ACCESS_TOKEN = $_SESSION["access_token_code"];
        }

        //构建请求二维码参数
        //path是扫描二维码跳转的小程序路径，可以带参数?id=xxx
        //width是二维码宽度
        $qcode ="https://api.weixin.qq.com/cgi-bin/wxaapp/createwxaqrcode?access_token=$ACCESS_TOKEN";
        $param = json_encode(array("path" => $url_path,"width"=> 150));

        //POST参数
        $result = $this->httpRequest($qcode,$param,"POST");
        //生成二维码
        $url = '/upload/source_' . time() . "/";
        $path = __ROOTDIR__ . $url;
        if(!is_dir($path)){
            @mkdir($path, 0777, true);
        }
        $filename = time() . '.png';
        file_put_contents($path.$filename,$result);
//        $base64_image ="data:image/jpeg;base64,".base64_encode($result );
        $param = [
            'code_path' => $siteurl . $url . $filename,
            'userName' => 'gh_562db6722f5e',
            'path' => $url_path,
        ];
        $this->ajaxReturn(200, '图片获取成功！', $param);
    }

    //把请求发送到微信服务器换取二维码
    function httpRequest($url,$data='',$method='GET'){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL,$url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_USERAGENT,$_SERVER['HTTP_USER_AGENT']);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
        if($method=='POST')
        {
            curl_setopt($curl, CURLOPT_POST, 1);
            if ($data !='')
            {
                curl_setopt($curl, CURLOPT_POSTFIELDS,$data);
            }
        }

        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($curl);
        curl_close($curl);
        return $result;
    }

    public function friendPath()
    {
        $list = $this->data_list("form_data_friend_path", " where id>0 ");
        $items = [];
        if (!empty($list)) {
            foreach ($list as $k => $val) {
                $items[] = [
                    'id' => $val['id'],
                    'title' => $val['title'],
                    'path' => $val['path'],
                ];
            }
        }
        $params = [
            'list' => $items,
        ];
        $this->ajaxReturn(200, '数据获取成功！', $params);
    }
}