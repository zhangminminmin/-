<?php

use Firebase\JWT\JWT;
/**
 * 登录注册模块
 * 三方登录模块
 * 忘记密码模块
 */

class loginMod extends commonMod 
{
    public function __construct()
    {
        parent::__construct();

    }

    /***
     * ios的修改
     * ios_id
     */
    public function iosLogin()
    {
        $input = $this->post;
        if (!$input['ios_id']) {
            $this->ajaxReturn(202, '网络出错  参数获取失败！');
        }

        $info = $this->data_getinfo('user', ' mobile = "' . $input['ios_id'] . '"');
        if (!$info) {
            $data = array(
                "mobile" => $input['ios_id'],
                "password" => '',
                'nation_code' => $input['nation_code'],
                "nickname" => '游客' . rand('1000','9999'),
                'avatar' => '/upload/user.png',
                "created_at" =>  time(),
            );
            $addUser = $this->data_add('user', $data);
            if (!$addUser) {
                $this->ajaxReturn(202, '网络出错 请刷新重试！');
            }
            $id = $addUser;
        }else{
            $id = $info['id'];
        }
        $_SESSION['user_id'] = $id;
        $this->ajaxReturn(200,'登陆成功');
    }

    /*
     * 手机号码登录接口
     * nation_code 国别号
     * mobile 手机号
     * password 密码
     * 
     */
    public function login()
    {
        $input = $this->post;

        if (empty($input['nation_code'])) {
            $this->ajaxReturn(202, '请选择国家区号！');
        }

        if (empty($input['mobile'])) {
            $this->ajaxReturn(202, "手机号码不能为空");
        }

        if (empty($input['password'])) {
            $this->ajaxReturn(202, "密码不能为空");
        }

        $sql = " mobile='" . $input['mobile'] . "' and password ='" . md5($input['password']) . "' and nation_code='" . $input['nation_code'] . "'";
        $info = $this->data_getinfo("user", $sql);
        if (empty($info)) {
            $this->ajaxReturn(202, "账号或者密码错误");
        } else {
            $_SESSION['user_id'] = $info['id'];
            $this->ajaxReturn(200, "登录成功");
        }
    }

    /*
     * 手机号注册第一步 验证手机号
     * nation_code 区号
     * mobile 手机号
     * code  手机短信码
     */
    public function registerOne()
    {
        $input = $this->post;
        if (empty($input['nation_code'])) {
            $this->ajaxReturn(202, '请选择国家区号！');
        }
        if (empty($input['mobile'])) {
            $this->ajaxReturn(202, "手机号码不能为空");
        }

        $info = $this->data_getinfo("user", " mobile='" . $input['mobile'] . "' and nation_code='" . $input['nation_code'] . "'");
        if (!empty($info)) {
            $this->ajaxReturn(202, "手机号码已经注册，请直接登录即可");
        }

        if (empty($input['code'])) {
            $this->ajaxReturn(202, "短信验证码不能为空");
        }

        $res = $this->checkSmsCode($input['nation_code'] . $input['mobile'], $input['code'], "reg");
        if ($res[0] == 202) {
            $this->ajaxReturn(202, $res[1]);
        }

        $this->ajaxReturn(200, " 手机号通过验证，即将跳转下一步...");
    }

    /**
     * 注册第二步 设置密码
     * nation_code 区号
     * mobile
     * code
     * password 密码
     * repassword 重复密码
     */
    public function registerTwo()
    {
        $input = $this->post;

        if (empty($input['nation_code'])) {
            $this->ajaxReturn(202, '请选择国家区号！');
        }

        if (empty($input['mobile'])) {
            $this->ajaxReturn(202, "手机号已经失效");
        }

        $info = $this->data_getinfo("user", " mobile='" . $input['mobile'] . "' and nation_code = '" . $input['nation_code'] . "'");
        if (!empty($info)) {
            $this->ajaxReturn(202, "手机号码已经注册，请直接登录即可");
        }

        if (empty($input['code'])) {
            $this->ajaxReturn(202, "验证码不能为空");
        }

        $res = $this->checkSmsCode($input['nation_code'] . $input['mobile'], $input['code'], "reg");
        
        if (empty($input['password']) && strlen($input['password']) < 6) {
            $this->ajaxReturn(202, "密码不能为空且最少为六位数");
        }

        if ($input['password'] != $input['repassword']) {
            $this->ajaxReturn(202, "两次密码输入不一致");
        }

        $data = array(
                "mobile" => $input['mobile'],
                'nation_code' => $input['nation_code'],
                "password" => md5($input['password']),
                "nickname" => '用戶' . substr($input['mobile'],-4),
                'avatar' => '/upload/user.png',
                "created_at" => time(),
            );
        $insertId = $this->data_add("user", $data);

        // 删除验证码
        if ($res[0] == 202) {
            $this->ajaxReturn(202, $res[1]);
        } else {
            $this->delSmsCode($res[2]);
        }

        if(empty($insertId)) {
            $this->ajaxReturn(202, "网络原因请刷新重试");
        }else {
            $_SESSION['user_id'] = $insertId;
            $this->ajaxReturn(200, "注册成功，正在跳转...");
        }
    }

    /**
     * PC端注册接口
     * nation_code 区号
     * mobile 手机号
     * code 验证码
     * password 密码
     * repassword 重复密码
     */
    public function registerPc()
    {
        $input = $this->post;

        if (empty($input['nation_code'])) {
            $this->ajaxReturn(202, '请选择国家区号！');
        }

        if (empty($input['mobile'])) {
            $this->ajaxReturn(202, "手机号码不能为空");
        }

        $info = $this->data_getinfo("user", " mobile='" . $input['mobile'] . "' and nation_code='" . $input['nation_code'] . "'");
        if (!empty($info)) {
            $this->ajaxReturn(202, "手机号码已经注册，请直接登录即可");
        }
        
        $res = $this->checkSmsCode($input['nation_code'] . $input['mobile'], $input['code'], "reg");
        if ($res[0] == 202) {
            $this->ajaxReturn(202, $res[1]);
        }

        if (empty($input['password']) && strlen($input['password']) < 6) {
            $this->ajaxReturn(202, "密码不能为空且最少为六位数");
        }

        if ($input['password'] != $input['repassword']) {
            $this->ajaxReturn(202, "两次密码输入不一致");
        }

        $data = array(
                "mobile" => $input['mobile'],
                "password" => md5($input['password']),
                'nation_code' => $input['nation_code'],
                "nickname" => '用戶' . substr($input['mobile'],-4),
                'avatar' => '/upload/user.png',
                "created_at" =>  time(),
            );
        $addUser = $this->data_add("user", $data);
        if (empty($addUser)) {
            $this->ajaxReturn(202, "网络原因请刷新重试");
        } else {
            if ($res[0] == 200) {
                $this->delSmsCode($res[2]);
            }
            $this->ajaxReturn(200, "会员注册成功");
        }
    }


    /**
     * 小程序登录
     * code 小程序code
     * platform 值 miniapp
     */
    public function miniappLogin()
    {
        $mini_appid = 'wxba14fc743ef3ce2a';
        $mini_appscret = '86834feb183d277ec5c57aa9e13c1144';

        $code = in($_POST['code']);
        $platform = in($_POST['platform']);

        if ($code) {
            $result = json_decode(file_get_contents('https://api.weixin.qq.com/sns/jscode2session?appid='. $mini_appid .'&secret='. $mini_appscret .'&js_code='.$code.'&grant_type=authorization_code'));

            if (!isset($result->openid)) {
                $this->ajaxReturn(202, '授权信息获取失败');
            }

            if($result->openid){
                $oauth_info = $this->data_getinfo('platform', ' unionid = "' . $result->openid . '"');
                if (!$oauth_info) {
                    $data = array(
                        "platform" => $platform,
                        "openid" => $result->openid,
                        "user_id" => 0,
                        "created_at" => time(),
                        "unionid" => $result->openid,
                    );
                    $platformId = $this->data_add('platform',$data);
                    $this->ajaxReturn(301, "请去绑定手机号码", array("platformid"=> $platformId));
                }

                if($oauth_info['user_id'] == 0) {
                    $this->ajaxReturn(301, "请去绑定手机号码", array("platformid"=> $oauth_info['id']));
                }

                session_start();
                $session_id = session_id();
                $_SESSION['user_id'] = $oauth_info['user_id'];
                $param = [
                    'session_id' => $session_id,
                ];
                $this->ajaxReturn(200, "登录成功",$param);
            }
        }else{
            $this->ajaxReturn(202, '授权失败！');
        }
    }

    /**
     * 第三方登录  QQ 微信 谷歌 ios登录
     * platform : QQ  手机微信 电脑扫码登录 谷歌
     * openid : 唯一身份标识
     * unionid :联合id
     */
    public function oauthLogin_test_test_test() 
    {
        $input = $this->post;
        if (empty($input['platform']) || empty($input['openid'])) {
            $this->ajaxReturn(202, "三方登录信息获取失败！");
        }

        $sql = "platform='" . $input['platform'] . "' and openid='" . $input['openid'] . "'";
        $oauth_info = $this->data_getinfo("platform", $sql);
        if (empty($oauth_info)) {
            $data = array(
                    "platform" => $input['platform'],
                    "openid" => $input['openid'],
                    "user_id" => 0,
                    "created_at" => time(),
                );
            $platformId = $this->data_add("platform", $data);
            $this->ajaxReturn(301, "请去绑定手机号码", array("platformid"=> $platformId));
        }

        if($oauth_info['user_id'] == 0) {
            $this->ajaxReturn(301, "请去绑定手机号码", array("platformid"=> $oauth_info['id']));
        }

        $_SESSION['user_id'] = $oauth_info['user_id'];
        $this->ajaxReturn(200, "登录成功");
    }


    public function oauthLogin() 
    {
        $input = $this->post;
        if (empty($input['platform']) || empty($input['openid']) || empty($input['unionid'])) {
            $this->ajaxReturn(202, "三方登录信息获取失败！");
        }

        $sql = "platform='" . $input['platform'] . "' and unionid='" . $input['unionid'] . "'";
        $oauth_info = $this->data_getinfo("platform", $sql);
        if (empty($oauth_info)) {
            $data = array(
                    "platform" => $input['platform'],
                    "openid" => $input['openid'],
                    "user_id" => 0,
                    "created_at" => time(),
                    "unionid" => $input['unionid'],
                );
            $platformId = $this->data_add("platform", $data);
            $this->ajaxReturn(301, "请去绑定手机号码", array("platformid"=> $platformId));
        }

        if($oauth_info['user_id'] == 0) {
            $this->ajaxReturn(301, "请去绑定手机号码", array("platformid"=> $oauth_info['id']));
        }

        $_SESSION['user_id'] = $oauth_info['user_id'];
        $this->ajaxReturn(200, "登录成功");
    }


    /*
    * 三方登录 ios
     * userID 用户的唯一id
     * identityToken 验证凭证code
    * */
    public function otherIosLogin()
    {
        try {
            $userID = in($_POST['userID']);
            $identityToken = in($_POST['identityToken']);

            if(!$userID || !$identityToken){
                throw new \Exception("参数错误 请稍后重试！");
            }

            $verifyRes = $this->appleJwtVerify($identityToken);
            if(isset($verifyRes['jwtStatus']) && $verifyRes['jwtStatus'] == 'failed'){
                throw new \Exception( $verifyRes['jwtMsg']);

            }else {
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
                    //$this->ajaxReturn(301, "请去绑定手机号码", array("platformid"=> $platformId));
                    throw new \Exception('请去绑定手机号码',301);
                }

                if($oauth_info['user_id'] == 0) {
                    //$this->ajaxReturn(301, "请去绑定手机号码", array("platformid"=> $oauth_info['id']));
                    throw new \Exception('请去绑定手机号码',301);

                }

                $_SESSION['user_id'] = $oauth_info['user_id'];
            }
        } catch (\Exception $e) {
            $this->ajaxReturn($e->getCode() ?: 202, $e->getMessage());
        }
        $this->ajaxReturn('200', '登陆成功！');
    }

    private function appleJwtVerify($identityToken = ''){
        //获取Apple公钥访问地址：https://appleid.apple.com/auth/keys
        //得到Apple公钥：
//        {
//            "kty": "RSA",
//            "kid": "eXaunmL",
//            "use": "sig",
//            "alg": "RS256",
//            "n": "4dGQ7bQK8LgILOdLsYzfZjkEAoQeVC_aqyc8GC6RX7dq_KvRAQAWPvkam8VQv4GK5T4ogklEKEvj5ISBamdDNq1n52TpxQwI2EqxSk7I9fKPKhRt4F8-2yETlYvye-2s6NeWJim0KBtOVrk0gWvEDgd6WOqJl_yt5WBISvILNyVg1qAAM8JeX6dRPosahRVDjA52G2X-Tip84wqwyRpUlq2ybzcLh3zyhCitBOebiRWDQfG26EH9lTlJhll-p_Dg8vAXxJLIJ4SNLcqgFeZe4OfHLgdzMvxXZJnPp_VgmkcpUdRotazKZumj6dBPcXI_XID4Z4Z3OM1KrZPJNdUhxw",
//            "e": "AQAB"
//        }

        //通过Apple公钥在线(https://8gwifi.org/jwkconvertfunctions.jsp)得到用于解密的pem公钥字符串
        $publickKey = "-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA4dGQ7bQK8LgILOdLsYzf
ZjkEAoQeVC/aqyc8GC6RX7dq/KvRAQAWPvkam8VQv4GK5T4ogklEKEvj5ISBamdD
Nq1n52TpxQwI2EqxSk7I9fKPKhRt4F8+2yETlYvye+2s6NeWJim0KBtOVrk0gWvE
Dgd6WOqJl/yt5WBISvILNyVg1qAAM8JeX6dRPosahRVDjA52G2X+Tip84wqwyRpU
lq2ybzcLh3zyhCitBOebiRWDQfG26EH9lTlJhll+p/Dg8vAXxJLIJ4SNLcqgFeZe
4OfHLgdzMvxXZJnPp/VgmkcpUdRotazKZumj6dBPcXI/XID4Z4Z3OM1KrZPJNdUh
xwIDAQAB
-----END PUBLIC KEY-----";//pem公钥 【也可以通过将RSA公钥modulus（N）和exponent（E）转换为PEM文件】

        $decoded = JWT::decode($identityToken, $publickKey, array('RS256'));
        return $decoded;
    }


    /**
     * 绑定手机号 第一步
     * nation_code 区号
     * mobile 手机号
     * code 验证码
     * platformid 三方登录表的id
     */
    public function bindMobile()
    {
        $input = $this->post;

        if (empty($input['nation_code'])) {
            $this->ajaxReturn(202, '请选择国家区号');
        }
        if (empty($input['platformid'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        if (empty($input['mobile'])) {
            $this->ajaxReturn(202, "手机号码不能为空");
        }

        $res = $this->checkSmsCode($input['nation_code'] . $input['mobile'], $input['code'], "bind");
        if ($res[0] == 202) {
            $this->ajaxReturn(202, $res[1]);
        }

        $oauth_info = $this->data_getinfo("platform", " id='" . $input['platformid'] . "'");
        $userinfo = $this->data_getinfo("user", "mobile='" . $input['mobile'] . "' and nation_code = '" . $input['nation_code'] . "'");
        if (empty($oauth_info)) {
            $this->ajaxReturn(202, "网络出错，请刷新重试");
        }

        if (! empty($oauth_info['user_id'])) {
            $this->ajaxReturn(202, "此三方账号已经绑定手机号,请直接登录");
        }

        if (empty($userinfo)) {
            $data = array('mobile'=> $input['mobile'], "platformid"=> $input['platformid']);
            $this->ajaxReturn(302, "请完善密码", $data);
        }

        $sql = "platform='" . $oauth_info['platform'] . "' and user_id='" . $userinfo['id'] . "'";
        $oauth_user = $this->data_getinfo("platform", $sql);
        if (!empty($oauth_user)) {
            $this->ajaxReturn(202, "该手机号已绑定该三方登录其他账号，暂时无法绑定");
        }

        $sql = "id='" . $input['platformid'] . "'";
        $platformEdit = $this->data_edit("platform", array("user_id"=> $userinfo['id']), $sql);
        if (empty($platformEdit)) {
            $this->ajaxReturn(202, "网络出错，请刷新重试");
        } else {
            $_SESSION['user_id'] = $userinfo['id'];
            $this->ajaxReturn(200, "登录成功");
        }
    }

    /**
     * 完善密码
     * platformid 第三方登录表的id
     * nation_code 国家区号
     * mobile 手机号
     * code 验证码
     * password 密码
     * repassword 重复密码
     */
    public function completePass()
    {
        $input = $this->post;

        if (empty($input['nation_code'])) {
            $this->ajaxReturn(202, '请选择国家区号！');
        }
        if (empty($input['platformid']) || empty($input['mobile'])) {
            $this->ajaxReturn(202, "网络原因请刷新重试");
        }

        $oauth_info = $this->data_getinfo("platform", " id='" . $input['platformid'] . "'");
        if (empty($oauth_info)) {
            $this->ajaxReturn(202, "网络原因请刷新重试");
        }

        if (! empty($oauth_info['user_id'])) {
            $this->ajaxReturn(202, "此三方账号已经绑定手机号,请直接登录");
        }

        // 检查验证码
        $res = $this->checkSmsCode($input['nation_code'] . $input['mobile'], $input['code'], "bind");

        if (empty($input['password']) && strlen($input['password']) < 6) {
            $this->ajaxReturn(202, "密码不能为空且最少为六位数");
        }

        if ($input['password'] != $input['repassword']) {
            $this->ajaxReturn(202, "两次密码输入不一致");
        }

        $data = array(
                'nation_code' => $input['nation_code'],
                "mobile" => $input['mobile'],
                "password" => md5($input['password']),
                "nickname" => '用戶' . substr($input['mobile'],-4),
                'avatar' => '/upload/user.png',
                "created_at" => time(),
            );

        // 检查是否存在
        $userinfo = $this->data_getinfo("user", "mobile='" . $input['mobile'] . "' and nation_code='" . $input['nation_code'] . "'");
        if (empty($userinfo)) {
            $insertId = $this->data_add("user", $data);
        } else {

            // 检查手机号是否绑定同一平台的其他账号
            $sql = "platform='" . $oauth_info['platform'] . "' and user_id='" . $userinfo['id'] . "'";
            $oauth_user = $this->data_getinfo("platform", $sql);
            if (!empty($oauth_user)) {
                $this->ajaxReturn(202, "该手机号已绑定该三方登录其他账号，暂时无法绑定");
            }
            $insertId = $userinfo['id'];
        }
        
        $sql = "id='" . $input['platformid'] . "'";
        $platformEdit = $this->data_edit("platform", array("user_id"=> $insertId), $sql);
        

        if ($res[0] == 202) {
            $this->ajaxReturn(202, $res[1]);
        } else {
            $this->delSmsCode($res[2]);
        }

        $_SESSION['user_id'] = $insertId;
        $this->ajaxReturn(200, "登录成功");
    }

    /**
     * 绑定手机号
     * nation_code 区号
     * mobile 手机号
     * platformid : 第三方登录表的id
     * code 验证码
     * password 新密码
     * repassword 新重复密码
     */
    public function bindMobilePc()
    {
        $input = $this->post;
        if (empty($input['nation_code'])) {
            $this->ajaxReturn(202, '请选择国家区号！');
        }
        if (empty($input['platformid'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        $sql = " id = '" . $input['platformid'] . "'";
        $oauth_info = $this->data_getinfo("platform", $sql);

        if (empty($oauth_info)) {
            $this->ajaxReturn(202, "网络原因请刷新重试");
        }

        if (! empty($oauth_info['user_id'])) {
            $this->ajaxReturn(202, "此三方账号已经绑定手机号,请直接登录");
        }

        if (empty($input['mobile'])) {
            $this->ajaxReturn(202, "手机号码不能为空");
        }

        $res = $this->checkSmsCode($input['nation_code'] . $input['mobile'], $input['code'], "bind");
        
        if ($res[0] == 202) {
            $this->ajaxReturn(202, $res[1]);
        }

        if (empty($input['password']) && strlen($input['password']) < 6) {
            $this->ajaxReturn(202, "密码不能为空且最少为六位数");
        }

        if ($input['password'] != $input['repassword']) {
            $this->ajaxReturn(202, "两次密码输入不一致");
        }

        $userinfo = $this->data_getinfo("user", "mobile = '" . $input['mobile'] . "' and nation_code='" . $input['nation_code'] . "'");
        if (empty($userinfo)) {
            $data = array(
                'nation_code' => $input['nation_code'],
                "mobile" => $input['mobile'],
                "password" => md5($input['password']),
                "nickname" => '用戶' . substr($input['mobile'],-4),
                'avatar' => '/upload/user.png',
                "created_at" => time(),
            );
            $addUser = $this->data_add("user", $data);
            $sql = "id='" . $input['platformid'] . "'";
            $platformEdit = $this->data_edit("platform", array("user_id"=> $addUser), $sql);
            $_SESSION['user_id'] = $addUser;
        } else {
            $sql = "platform='" . $oauth_info['platform'] . "' and user_id='" . $userinfo['id'] . "'";
            $oauth_user = $this->data_getinfo("platform", $sql);
            if (!empty($oauth_user)) {
                $this->ajaxReturn(202, "该手机号已绑定该三方登录其他账号，暂时无法绑定");
            }

            $_SESSION['user_id'] = $userinfo['id'];
            $sql = "id='" . $input['platformid'] . "'";
            $platformEdit = $this->data_edit("platform", array("user_id"=> $userinfo['id']), $sql);
        }

        $this->ajaxReturn(200, "手机号绑定成功");
    }

    /**
     * 忘记密码第一步
     * nation_code 国家区号
     * mobile 手机号
     * code 验证码
     */
    public function resetPassOne()
    {
        $input = $this->post;
        if (empty($input['nation_code'])) {
            $this->ajaxReturn(202, '请选择国家区号！');
        }
        if (empty($input['mobile'])) {
            $this->ajaxReturn(202, "手机号码不能为空");
        }

        $userinfo = $this->data_getinfo("user", "mobile='" . $input['mobile'] . "' and nation_code='" . $input['nation_code'] . "'");
        if (empty($userinfo)) {
            $this->ajaxReturn(202, "账号信息不存在");
        }

        $res = $this->checkSmsCode($input['nation_code'] . $input['mobile'], $input['code'], "reset");
        if ($res[0] == 202) {
            $this->ajaxReturn(202, $res[1]);
        }

        $this->ajaxReturn(200, "手机验证通过,即将跳转下一步...");
    }


    /**
     * 忘记密码第二步
     * nation_code 国家区号
     * mobile 手机号
     * code 验证码
     * password 密码
     * repassword 重复密码
     */
    public function resetPassTwo()
    {
        $input = $this->post;
        if (empty($input['nation_code'])) {
            $this->ajaxReturn(202, '请选择国家区号！');
        }
        if (empty($input['mobile'])) {
            $this->ajaxReturn(202, "手机号已经失效");
        }
        
        $info = $this->data_getinfo("user", "mobile='" . $input['mobile'] . "' and nation_code='" . $input['nation_code'] . "'");
        if (empty($info)) {
            $this->ajaxReturn(202, "手机号码不存在");
        }

        $res = $this->checkSmsCode($input['nation_code'] . $input['mobile'], $input['code'], "reset");
        

        if (empty($input['password']) || strlen($input['password']) < 6) {
            $this->ajaxReturn(202, "密码不能为空且最少为六位数");
        }

        if ($input['password'] != $input['repassword']) {
            $this->ajaxReturn(202, "两次密码输入不一致");
        }

        $data = array("password" => md5($input['password']));
        $passEdit = $this->data_edit("user", $data, " id='" . $info['id'] . "'");

        // 判断验证码
        if ($res[0] == 202) {
            $this->ajaxReturn(202, $res[1]);
        } else {
            $this->delSmsCode($res[2]);
        }

        if (empty($passEdit)) {
            $this->ajaxReturn(202, "网络原因请刷新重试");
        } else {
            
            $_SESSION['user_id'] = $info['id'];
            $this->ajaxReturn(200, "密码修改成功");
        }
    }

    /**
     * pc端的忘记密码
     * nation_code 国家区号
     * mobile 手机号
     * code 验证码
     * password 新密码
     * repassword 验证新密码
     */
    public function resetPassPc()
    {
        $input = $this->post;
        if (empty($input['nation_code'])) {
            $this->ajaxReturn(202, '请选择国家区号！');
        }

        if (empty($input['mobile'])) {
            $this->ajaxReturn(202, "手机号码不能为空");
        }

        $userinfo = $this->data_getinfo("user", "mobile='" . $input['mobile'] . "' and nation_code='" . $input['nation_code'] . "'");
        if (empty($userinfo)) {
            $this->ajaxReturn(202, "账号信息不存在");
        }

        $res = $this->checkSmsCode($input['nation_code'] . $input['mobile'], $input['code'], "reset");
        if ($res[0] == 202) {
            $this->ajaxReturn(202, $res[1]);
        }

        if (empty($input['password']) || strlen($input['password']) < 6) {
            $this->ajaxReturn(202, "密码不能为空且最少为六位数");
        }

        if ($input['password'] != $input['repassword']) {
            $this->ajaxReturn(202, "两次密码输入不一致");
        }

        $data = array("password" => md5($input['password']));
        $passEdit = $this->data_edit("user", $data, " id='" . $userinfo['id'] . "'");

        if (empty($passEdit)) {
            $this->ajaxReturn(202, "网络原因请刷新重试");
        } else {
            $_SESSION['user_id'] = $userinfo['id'];
            if ($res[0] == 200) {
                $this->delSmsCode($res[2]);
            }
            $this->ajaxReturn(200, "密码修改成功");
        }
    }

    /**
     * 发送短信的接口
     * nation_code 国家区号
     * mobile 
     * act
     */
    public function sendCode() 
    {
        $input = $this->post;

        if (empty($input['nation_code'])) {
            $this->ajaxReturn(202, '请选择国家区号！');
        }
        if (empty($input['mobile'])) {
            $this->ajaxReturn(202, "手机号码不能为空");
        }

        if (empty($input['act'])) {
            $this->ajaxReturn(202, '请传入短信的类型');
        }
        $code = rand(100000, 999999);
        $res = sendSmscode($input['nation_code'] . $input['mobile'], $code);
        // 测试阶段打开以下代码 
        // $code = "123456";
        // $res = true;
        if ($res) {
            // 删除所有过期的验证码
            $s = $this->config['expire_time'] * 60 + time();
            $this->data_del("sendcode", " expiretime <'" . $s . "' and style='" . $input['act'] . "'");
            $this->data_del("sendcode"," mobile='" . $input['mobile'] . "' and style='" . $input['act'] . "'");
            // 将手机号码保存到表中
            $data = array(
                "expiretime" => $s,
                "mobile" => $input['nation_code'] . $input['mobile'],
                "code" => $code,
                "style" => $input['act'],
                "created_at" => time(),
            );
            $add = $this->data_add("sendcode", $data);
            $this->ajaxReturn(200, "短信发送成功");
        }else{
            $this->ajaxReturn(202, "发送失败");
        }
    }


    
    /**
     * 国别号列表
     */
    public function nationCode()
    {

        $list = model("u")->data_list('form_data_mobile_code', 'where id >0', ' order by id desc');
        $items = [];
        foreach ($list as $k => $val) {
            $items[] = [
                'id' => $val['id'],
                'name' => $val['name'],
                'code' => $val['code'],
            ];
        }

        $param = [
            'list' => $items,
        ];
        return ajaxReturn(200, '国别号获取成功！', $param);
    }

}