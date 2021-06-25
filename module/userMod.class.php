<?php
/**
 * 个人中心的接口
 * 
 */
class userMod extends commonMod 
{
    protected $userinfo;
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
//        if (empty($userinfo['nickname']) || empty($userinfo['avatar'])) {
//            $this->ajaxReturn(401, "请先去完善资料");
//        }

        $this->userinfo = $userinfo;
    }

    /**
     * 用户信息 type 为1的时候 普通会员  为2的时候猩听译
     */
    public function userCenter()
    {
        $this->checkLogin();
        if (empty($_SESSION['user_id'])) {
            $this->ajaxReturn(203, "未登录");
        }

        $siteurl = $this->siteurl;
        $userinfo = $this->data_getinfo("user", "id = '" . $_SESSION['user_id'] . "'");

        // 会员剩余的天数
        $days = $this->checkDays($userinfo);
        $userinfo['avatar'] = empty($userinfo['avatar']) ? $this->config['avatar'] : $userinfo['avatar'];
        $avatar = formatAppImageUrl($userinfo['avatar'], $siteurl);
        // 今天是否签到
        $starttime = strtotime(date("Y-m-d 00:00:00"));
        $endtime = strtotime(date("Y-m-d 23:59:59"));

        $sql = "created_at >= '" . $starttime . "' and created_at <= '" . $endtime . "' and user_id = '" . $_SESSION['user_id'] . "'";
        $signLogInfo = $this->data_getinfo("sign_log", $sql);
        $status = 1;
        if (! empty($signLogInfo)) {
            $status = 2;
        }

        $show_data = $this->isShow();
        $is_show = $show_data['report_error_count'] + $show_data['suggest_count'] + $show_data['message_count'] + $show_data['notice_count'] + $show_data['comment_count'];
        $info = array(
            "id" => (int)$_SESSION['user_id'],
            "avatar" => $avatar,
            "nickname" => empty($userinfo['nickname']) ? $userinfo['mobile'] : $userinfo['nickname'],
            "sign" => empty($userinfo['sign']) ? "" : $userinfo['sign'],
            "type" => (int)$userinfo['type'],
            "days" => $days,
            "status" => $status,
            'is_show' => $is_show > 0 ? 1:2,
//            'show_data' => $show_data
        );


        $param = array(
            "userinfo" => $info,
        );
        $this->ajaxReturn(200, "获取用户信息成功", $param);
    }

    /**
     * 我的听写
     * page 页数
     * type 素材的类型 1平台素材  2个人素材  3精品课程
     * dotype 1我的听写 2朗读 3翻译 4字幕
     */
    public function myDictationList()
    {
        $this->checkLogin();
        $siteurl = $this->siteurl;
        $input = $this->post;
        if ($input['type'] != 1 && $input['type'] != 2 && $input['type'] != 3) {
            $this->ajaxReturn(202, "请选择素材分类", $input);
        }

        $page = intval($input['page']) > 0 ? intval($input['page']) : 1;
        $pageSize = 15;

        $table = "";
        switch($input['type']) {
            case 1:
                $table = "source";
                break;
            case 2:
                $table = "user_source";
                break;
            case 3:
                $table = "good_course";
                break;
            default:
                $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        $where = "where user_id = '" . $_SESSION['user_id'] . "' and source_type = '" . $input['type'] . "' and do_type = '" . $input['dotype'] . "'";
        
        $param = array("type" => $input['type'], "sourceList" => array());

        $count = $this->data_count("source_log", $where);
        if (empty($count)) {
            $this->ajaxReturn(200, "", $param);// 暂时没有数据
        }

        $pagenum = ceil($count / $pageSize);
        if ($pagenum < $page) {
            $this->ajaxReturn(200, "", $param); //数据加载完成
        }

        $limit = " LIMIT " . ($page-1) * $pageSize . "," .$pageSize;
        $list = $this->data_list("source_log", $where, " order by id desc", $limit);
        $sourceList = array();
        if (!empty($list)) {
            foreach ($list as $k => $val) {
                $sourceInfo = $this->data_getinfo($table, " id = '" . $val['source_id'] . "'");
                $sourceList[] = array(
                    "source_id" => $val['source_id'],
                    "title" => $sourceInfo['title'],
                    "image" => formatAppImageUrl($sourceInfo['image'], $siteurl),
                    "created_at" => date("Y-m-d H:i", $sourceInfo['created_at']),
                    "type" => $sourceInfo['type'],
                );
            }
        }
        $param = array(
            "type" => $input['type'], 
            "sourceList" => $sourceList,
            "pageNum" => $pagenum,
        );
        $this->ajaxReturn(200, "获取信息成功", $param);
    }

    /**
     * 我的生词本 source_words
     * page 页数  
     * pid 一级分类id
     * sort_id 分类id (选择分类时候可传)
     */
    public function myWordsList()
    {
        $this->checkLogin();
        $input = $this->post;
        $where = " where id > 0 and user_id = '" . $_SESSION['user_id'] . "' ";

        $page = intval($input['page']) > 0 ? intval($input['page']) : 1;
        $pageSize = intval($input['pageSize']) > 0 ? intval($input['pageSize']) : 15 ;

        if (!empty ($input['pid'])) {
            $where .= " and pid = '" . $input['pid'] . "'";
        }

        if (!empty($input['sort_id'])) {
            $where .= " and sort_id = '" . $input['sort_id'] . "'";
        }

        $param = array(
            "wordsList" => array(),
        );

        $count = $this->data_count("source_words", $where);
        if (empty($count)) {
            $this->ajaxReturn(200, "暂时没有数据", $param);
        }

        $pagenum = ceil($count / $pageSize);
        if ($pagenum  < $page) {
            $this->ajaxReturn(200, "数据加载完成", $param);
        }

        $limit = " LIMIT " . ($page-1) * $pageSize . "," .$pageSize;
        $list = $this->data_list("source_words", $where, " order by id desc", $limit);
        $wordsList = array();
        if (!empty($list)) {
            foreach ($list as $k => $val) {
                $wordsList[] = array(
                    "id" => $val['id'],
                    "name" => $val['name'],
                    "pronunciation_words" => $val['pronunciation_words'],
                    "paraphrase" => $val['paraphrase'],
                    "type" => (int)$val['type'],
                );
            }
        }

        $param = array(
            "wordsList" => $wordsList,
            "pageNum" => $pagenum,
        );
        $this->ajaxReturn(200, "获取单词成功", $param);
    }

    /**
     * 添加生词分类
     * pid 上级分类的id
     * name 分类名称
     */
    public function addCategory()
    {
        $this->checkLogin();
        $input = $this->post;

        if (intval($input['pid']) == 0) {
            $this->ajaxReturn(202, "请选择上级分类");
        }
        $pid = intval($input['pid']) > 0 ? intval($input['pid']) : 0;
        if (empty($input['name'])) {
            $this->ajaxReturn(202, "分类名称不能为空");
        }
        $data = array(
            "pid" => $pid,
            "name" => $input['name'],
            "created_at" => time(),
            "user_id" => $_SESSION['user_id'],
        );
        // 检查此等级下面是否存在此分类名称
        $where = " name = '" . $input['name'] . "' and pid = '" . $pid . "' and user_id = '" . $_SESSION['user_id'] . "'";
        $info = $this->data_getinfo("source_word_sort", $where);
        if (!empty($info)) {
            $this->ajaxReturn(202, "此等级下面的分类已经存在 请勿重复添加");
        }
        $addCategory = $this->data_add("source_word_sort", $data);
        if (empty($addCategory)) {
            $this->ajaxReturn(202, "网络原因请刷新重试");
        } else {
            $param = array("id" => $addCategory);
            $this->ajaxReturn(200, "分类添加成功", $param);
        }
    }

    /**
     * 编辑生词分类
     * id 分类的id
     * name 分类名称
     */
    public function editCategory()
    {
        $this->checkLogin();
        $input = $this->post;

        if (intval($input['id']) <= 0) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        $info = $this->data_getinfo("source_word_sort", " id = '" . $input['id'] . "'");
        if (intval($info['pid']) == 0) {
            $this->ajaxReturn(202, "一级分类为平台指定 无法修改");
        }

        if (empty($input['name'])) {
            $this->ajaxReturn(202, "分类名称不能为空");
        }

        $data = array(
            "name" => $input['name'],
        );

        $editCategory = $this->data_edit("source_word_sort", $data, " id = '" . $input['id'] . "'");

        if (empty($editCategory)) {
            $this->ajaxReturn(202, "网络原因请刷新重试");
        } else {
            $this->ajaxReturn(200, "分类编辑成功");
        }
    }

    /**
     * 删除生词分类接口
     * id 分类的id
     */
    public function delCategory()
    {
        $this->checkLogin();
        $input = $this->post;
        if (empty($input['id'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        $info = $this->data_getinfo("source_word_sort", " id = '" . $input['id'] . "'");
        if ($info['pid'] == 0) {
            $this->ajaxReturn(202, "没有权限删除一级分类");
        }

        $wordsInfo = $this->data_getinfo("source_words", " sort_id = '" . $input['id'] . "'");
        if (! empty($wordsInfo)){
            $this->ajaxReturn(202, "此分类下面已经添加生词 无法删除");
        }

        $del = $this->data_del("source_word_sort", " id = '" . $input['id'] . "'");
        if (empty($del)) {
            $this->ajaxReturn(202, "网络原因请刷新重试");
        } else {
            $this->ajaxReturn(200, "删除生词分类成功");
        }
    }

    /**
     * PC端的编辑生词分类
     * ids 分类的id
     * names  分类的名称
     */
    public function editCategoryPc()
    {
        $this->checkLogin();
        $input = $this->post;

        $idNum = count($input['ids']);
        $nameNum = count($input['names']);

        if (empty($idNum)) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        if ($idNum != $nameNum) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        $this->model->query("START TRANSACTION");
        // 循环编辑
        for($i = 0; $i < $idNum; $i++) {
            // 一级不可以修改
            $info = $this->data_getinfo("source_word_sort", " id = '" . $input['ids'][$i] . "'");
            if (intval($info['pid']) == 0) {
                $this->model->query("ROLLBACK");
                $this->ajaxReturn(202, "一级分类为平台指定 无法修改");
            }
            
            $data = array("name" => $input['names'][$i]);
            $editSort = $this->data_edit("source_word_sort", $data, " id = '" . $input['ids'][$i] . "'");
            if (empty($editSort)) {
                $this->model->query("ROLLBACK");
                $this->ajaxReturn(202, "网络出错请刷新重试");
            }
        }
        $this->model->query("COMMIT");
        $this->ajaxReturn(200, "分类编辑成功");
    }

    /**
     * 我的动态圈
     * page 页数
     */
    public function myCircle()
    {
        $input = $this->post;
        $this->checkLogin();
        $siteurl = $this->siteurl;
        $userinfo = $this->userinfo;
        $where = " WHERE user_id = '" . $_SESSION['user_id'] . "' AND id > 0";
        $order = " ORDER BY id DESC";

        $page = intval($input['page']) > 0 ? intval($input['page']) : 1;
        $pageSize = intval($input['pageSize']) > 0 ? intval($input['pageSize']) : 10;

        $param = array(
            "friendCircle" => array(),
        );

        $count = $this->data_count("friend_circle", $where);
        if (empty($count)) {
            $this->ajaxReturn(200, "暂时没有数据", $param);
        }

        $pagenum = ceil($count / $pageSize);
        if ($pagenum < $page) {
            $this->ajaxReturn(200, "数据加载完成", $param);
        }

        $limit = " LIMIT " . ($page - 1) * $pageSize . "," . $pageSize;
        $list = $this->data_list("friend_circle", $where, $order, $limit);

        $friendCircle = array();
        foreach ($list as $k => $val) {
            $num = $this->data_count("comment", "where id>0 and circle_id='" . $val['id'] . "'");
            $friendCircle[] = array(
                    "id" => $val['id'],
                    "nickname" => $userinfo['nickname'],
                    "avatar" => formatAppImageUrl($userinfo['avatar'], $siteurl),
                    "images" => $this->jsonImage($val['images'], $siteurl),
                    "title" => $val['title'],
                    "created_at" => $this->formatTime($val['created_at']),
                    "num" => $num,
                );
        }

        $param = array(
            "friendCircle" => $friendCircle,
            "pagenum" => $pagenum,
        );
        $this->ajaxReturn(200, "我的动态获取成功", $param);
    }

    /**
     * 删除动态圈
     * id 朋友圈的id
     */
    public function delCircle()
    {
        $input = $this->post;
        $this->checkLogin();
        $id = intval($input['id']);
        if ($id <= 0) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        $delCircle = $this->data_del("friend_circle", " id = '" . $id . "'");
        $delComment = $this->data_del("comment", "circle_id = '" . $id . "'");

        $this->ajaxReturn(200, "朋友圈信息删除成功");
    }


    /**
     * 退出登录
     */
    public function exitLogin()
    {
        unset($_SESSION['user_id']);
        $this->ajaxReturn(200, "退出登录成功");
    }


    /**
     * 进入个人资料页面
     */
    public function userData()
    {
        $siteurl = $this->siteurl;
        if (empty($_SESSION['user_id'])) {
            $this->ajaxReturn(203, "未登录");
        }

        $userinfo = $this->data_getinfo("user", "id = '" . $_SESSION['user_id'] . "'");
        $avatar = formatAppImageUrl($userinfo['avatar'], $siteurl);

        $ename = "";
        if (!empty($userinfo['english_level'])) {   
            $english_level_info = $this->data_getinfo("form_data_english", " id = '" . $userinfo['english_level'] . "'");
            $ename = $english_level_info['name'];
        }

        $jname = "";
        if (!empty($userinfo['japanese_level'])) {   
            $japanese_level_info = $this->data_getinfo("form_data_japanese", " id = '" . $userinfo['japanese_level'] . "'");
            $jname = $japanese_level_info['name'];
        }
        $info = array(
            "formatAvatar" => (string)$avatar,
            "avatar" => $userinfo['avatar'],
            "nickname" => $userinfo['nickname'],
            "birthday" => $userinfo['birthday'],
            "sex" => (int)$userinfo['sex'],
            "sex_name" => empty($userinfo['sex']) ? "" : $this->config['sex'][(int)$userinfo['sex']-1]['name'],
            "sign" => $userinfo['sign'],
            "english_level" => empty($userinfo['english_level']) ? "" : (int)$userinfo['english_level'],
            "english_level_name" => $ename,
            "japanese_level" => empty($userinfo['japanese_level']) ? "" : (int)$userinfo['japanese_level'],
            "japanese_level_name" => $jname,
        );
        $param = array("info" => $info);
        $this->ajaxReturn(200, "信息获取成功", $param);
    }

    /**
     * 个人信息页面参数
     */
    public function userDataParam()
    {
        $sex = $this->config['sex'];
        // 日语等级
        $japanese = $this->data_list("form_data_japanese", "where id>0");
        $japan = array();
        if (!empty($japanese)) {
            foreach ($japanese as $k => $val) {
                $japan[] = array(
                    "id" => (int)$val['id'],
                    "name" => $val['name'],
                );
            }
        }

        $englishList = $this->data_list("form_data_english", "where id>0");
        $english = array();
        if (!empty($englishList)) {
            foreach ($englishList as $k => $val) {
                $english[] = array(
                    "id" => (int)$val['id'],
                    "name" => $val['name'],
                );
            }
        }
        $param = array(
            "sex" => $sex,
            "japan" => $japan,
            "english" => $english,
        );
        $this->ajaxReturn(200, "参数获取成功", $param);
    }

    /**
     * 更新个人信息
     * name  字段名称 昵称  生日 性别 签名 日语等级  英语等级
     * value 字段的值
     */
    public function updateUserData()
    {
        $input = $this->post;
        if (! $_SESSION['user_id']) {
            $this->ajaxReturn(203, "未登录");
        }

        $userinfo = $this->data_getinfo("user", " id = '" . $_SESSION['user_id'] . "'");

        $fieldName = array("nickname", "birthday", "sex", "sign", "japanese_level", "english_level");
        if (! in_array($input['name'], $fieldName)) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }
        if (empty($input['value'])) {
            $this->ajaxReturn(202, $this->config['field'][$input['name']] . "不能为空");
        }

        $data = array($input['name'] => $input['value']);
        $editUserData = $this->data_edit("user", $data, "id = '" . $_SESSION['user_id'] . "'");
        if (empty($editUserData)) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        } else {
            $this->ajaxReturn(200, $this->config['field'][$input['name']] . "完善成功");
        }
    }

    /**
     * 个人中心上传头像接口
     * avatar 上传图像
     */
    public function uploadAvatar()
    {   
        $siteurl = "http://" . $this->config['siteurl'];
        if (!$_SESSION['user_id']) {
            $this->ajaxReturn(203, "未登录");
        }

        if (empty($_FILES['avatar'])) {
            $this->ajaxReturn(202, "上传头像失败");
        }

        $image = imageUpload($_FILES['avatar'], $_SESSION['user_id']);
        $data = array("avatar" => $image[0]);
        $editAvatar = $this->data_edit("user", $data, "id = '" . $_SESSION['user_id'] . "'");
        if (empty($editAvatar)) {
            $this->ajaxReturn(202, "网络原因请刷新重试");
        } else {
            $image = formatAppImageUrl($image[0], $siteurl);
            $param = array('avatar' => $image);
            $this->ajaxReturn(200, "头像上传成功", $param);
        }
    }

    /**
     * 修改个人信息
     * avatar 头像
     * nickname 昵称
     * birthday 生日
     * sex 性别
     * sign 签名
     * japanese_level 日语等级
     * english_level 英语等级
     */
    public function updateUserDataPc()
    {
        $input = $this->post;
        if (!$_SESSION['user_id']) {
            $this->ajaxReturn(203, "未登录");
        }

        $data = array();
        $userinfo = $this->data_getinfo("user", "id = '" . $_SESSION['user_id'] . "'");
        // print_r($userinfo);die;
        if (empty($userinfo['avatar'])) {
            if (empty($_FILES['avatar'])) {
                $this->ajaxReturn(202, "头像上传失败");
            } 
        }

        if (! empty($_FILES['avatar'])) {
            $image = imageUpload($_FILES['avatar'], $_SESSION['user_id']);
            $data['avatar'] = $image[0];
        }

        if (empty($input['nickname'])) {
            $this->ajaxReturn(202, "昵称不能为空");
        }

        $data['nickname'] = $input['nickname'];
        if (empty($input['birthday'])) {
            $this->ajaxReturn(202, "生日不能为空");
        }

        $data['birthday'] = $input['birthday'];
        if (empty($input['sex'])) {
            $this->ajaxReturn(202, "请选择性别");
        }

        $data['sex'] = $input['sex'];
        if (empty($input['sign'])) {
            $this->ajaxReturn(202, "签名不能为空");
        }

        $data['sign'] = $input['sign'];
        if (empty($input['japanese_level'])) {
            $this->ajaxReturn(202, "请选择日语等级");
        }

        $data['japanese_level'] = $input['japanese_level'];
        if (empty($input['english_level'])) {
            $this->ajaxReturn(202, "请选择英语等级");
        }
        $data['english_level'] = $input['english_level'];

        $userEdit = $this->data_edit("user", $data, "id = '" . $_SESSION['user_id'] . "'");
        if (empty($userEdit)) {
            $this->ajaxReturn(202, "网络原因请刷新重试");
        } else {
            $this->ajaxReturn(200, "个人资料完善成功");
        }
    }
    /**
     * 修改密码
     * password 原密码
     * newPwd 新密码
     * reNewPwd 重复新密码
     */
    public function modifyPwd()
    {
        $input = $this->post;
        if (empty($_SESSION['user_id'])) {
            $this->ajaxReturn(203, "未登录");
        }

        if (empty($input['password'])) {
            $this->ajaxReturn(202, "原来密码不能为空");
        }
        $userinfo = $this->data_getinfo("user", "id = '" . $_SESSION['user_id'] . "'");
        $password = md5($input['password']);
        if ($password != $userinfo['password']) {
            $this->ajaxReturn(202, "原密码输入错误");
        }

        if (strlen($input['newPwd']) < 6) {
            $this->ajaxReturn(202, "密码最小长度为六位");
        }

        if ($userinfo['password'] == md5($input['newPwd'])) {
            $this->ajaxReturn(202, "原密码与新密码相同");
        }

        if ($input['newPwd'] != $input['reNewPwd']) {
            $this->ajaxReturn(202, "两次密码输入不一致");
        }

        $data = array("password" => md5($input['newPwd']));
        $editPwd = $this->data_edit("user", $data, "id = '" . $_SESSION['user_id'] . "'");
        if (empty($editPwd)) {
            $this->ajaxReturn(202, "密码修改失败");
        } else {
            $this->ajaxReturn(200, "密码修改成功");
        }
    }


    /**
     * 个人中心内容
     * content 反馈内容
     */
    public function suggest()
    {
        $this->checkLogin();
        $input = $this->post;
        if (empty($input['content'])) {
            $this->ajaxReturn(202, "反馈内容不能为空");
        }

        $data = array(
            "content" => $input['content'],
            "created_at" => time(),
            "user_id" => $_SESSION['user_id'],
        );

        $addSuggest = $this->data_add("suggest", $data);
        if (empty($addSuggest)) {
            $this->ajaxReturn(202, "网络原因请刷新重试");
        } else {
            $this->ajaxReturn(200, "意见反馈成功");
        }
    }


    /**
     * 用户协议
     * form_data_user_agreement
     */
    public function userAgreement()
    {
        $this->checkLogin();
        $siteurl = $this->siteurl;
        $info = $this->data_list("form_data_user_agreement", "where id >0 ", " order by id desc", "limit 1");
        $content = getImgThumbUrl($info[0]['content'], $siteurl);

        $param = array(
            "content" => $content,
        );
        $this->ajaxReturn(200, "用户协议信息获取成功", $param);
    }



    /**
     * 关于我们
     * form_data_aboutus 
     */
    public function aboutUs()
    {   
        $this->checkLogin();
        $siteurl = $this->siteurl;
        $info = $this->data_list("form_data_aboutUs", "where id >0 ", " order by id desc", "limit 1");
        $content = getImgThumbUrl($info[0]['content'], $siteurl);

        $param = array(
            "content" => $content,
        );
        $this->ajaxReturn(200, "关于我们信息获取成功", $param); 
    }

    /**
     * 
     */

    /**
     * 
     */
    

    /**
     *  针对文本已经朗读的合成的录音进行操作
     *  翻译  朗读 添加生词 制作弹幕   听写 
     */
    
    /**
     * 进入文本合成录音 ====> 听写
     * source_id 素材的id
     * type 类型 1平台素材  2 个人素材 3 精品课程
     */
    public function textDictationInfo()
    {
        $this->checkLogin();
        $input = $this->post;
        if (empty($input['source_id'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        if (empty($input['type'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        // 听写内容信息
        $where = " type = 3 and pid = '" . $input['type'] . "' and user_id = '" . $_SESSION['user_id'] . "' and source_id = '" . $input['source_id'] . "'";
        $info = $this->data_getinfo("source_dictation", $where);
        $infoLog = $this->data_getinfo("dictation_log", $where);

        $dictationLog = array();
        if (!empty($infoLog)) {
            $str = empty($infoLog['dictation_tag']) ? "" : substr($infoLog['dictation_tag'], 1, strlen($infoLog['dictation_tag']) - 2);
            $dictationLog = array(
                "source_id" => $input['source_id'],
                "content" => empty($infoLog['content']) ? "" : htmlspecialchars_decode($infoLog['content']),
                "time" => $infoLog['time'],
                "dictation_tag" => $str,
                "created_at" => date("Y-m-d H:i", $infoLog['created_at']),
            );
        }
        $dictationInfo = array();
        if (!empty($info)) {
            $str = empty($info['dictation_tag']) ? "" : substr($info['dictation_tag'], 1, strlen($info['dictation_tag']) - 2);
            $dictationInfo = array(
                "source_id" => $input['source_id'],
                "content" => empty($info['content']) ? "" : htmlspecialchars_decode($info['content']),
                "time" => $info['time'],
                "dictation_tag" => $str,
                "created_at" => date("Y-m-d H:i", $info['created_at']),
            );
        }

        $dictation = empty($dictationLog) ? $dictationInfo : $dictationLog;
        $param = array(
            "dictationInfo" => empty($dictation) ? (object)array() : $dictation,
        );
        $this->ajaxReturn(200, "听写信息获取成功", $param);
    }


    /**
     * 文本合成录音  =====》 自动保存听写  10s自动保存功能
     * 每个十秒自动保存一次听写内容 （自动保存功能）
     * source_id 音视频的id (主表的id)
     * content 听写的内容
     * time 听写时间
     * dictation_tag  1,2,3
     * type 1平台素材  2个人素材  3 精品课程
     */
    public function dictationLog()
    {
        $input = $this->post;
        $this->checkLogin();

        $input['type'] = intval($input['type']) > 0 ? intval($input['type']) : 0 ;
        // 查看路径是否存在
        $sql = "user_id = '" . $_SESSION['user_id'] . "' and source_id = '" . $input['source_id'] . "' and type = '" . $input['type'] . "'";
        $sourceInfo = $this->data_getinfo("merge_audio", $sql);
        if (empty($sourceInfo)) {
            $this->ajaxReturn(202, "合成的音频不存在或者已失效");
        }
        
        // 听写的标签
        $dictation_tag = "";
        if (!empty($input['dictation_tag'])) {
            $dictation_tag = "," . $input['dictation_tag'] . ",";
        }

        $where = " source_id='" . $input['source_id'] . "' and type= 3 and user_id='" . $_SESSION['user_id'] . "' and pid = '" . $input['type'] . "'";
        $dictationInfo = $this->data_getinfo("dictation_log", $where);

        $data = array(
            "source_id" => $input['source_id'],
            "source_period_id" => 0,
            "content" => $input['content'],
            "created_at" => time(),
            "time" => $input['time'],
            "user_id" => $_SESSION['user_id'],
            "type" => 3,
            "dictation_tag" => $dictation_tag,
            "pid" => $input['type'],
        );

        if (empty($dictationInfo)) {
            $dictationInfo = $this->data_add("dictation_log", $data);
        } else {
            $dictationInfo = $this->data_edit("dictation_log", $data, "id='" . $dictationInfo['id'] . "'");
        }
        $this->ajaxReturn(200, "听写信息保存成功");
        
    }

    /**
     * 文本合成录音====> 听写
     * source_id 资源的id
     * content 听写内容
     * time 听写使用的时间
     * type 类型  1平台素材  2个人素材 3 精品课程
     * dictation_tag  选择标签
     */
    public function textDictation()
    {
        $this->checkLogin();
        $input = $this->post;

        if (empty($input['type'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        if (empty($input['source_id'])) {
            $this->ajaxReturn(202, "网络原因请刷新重试");
        }

        if (empty($input['content'])) {
            $this->ajaxReturn(202, "听写内容不能为空");
        }

        if (empty($input['time'])) {
            $this->ajaxReturn(202, "听写时长录入失败");
        }

        // 查看标签
        $dictation_tag = "";
        if (!empty($input['dictation_tag'])) {
            $dictation_tag = "," . $input['dictation_tag'] . ",";
        }

        $data = array(
            "source_id" => $input['source_id'],
            "source_period_id" => 0,
            "content" => $input['content'],
            "created_at" => time(),
            "time" => $input['time'],
            "user_id" => $_SESSION['user_id'],
            "type" => 3,
            "pid" => $input['type'],
            "dictation_tag" => $dictation_tag,
        );

        // 检查是否存在
        $where = " type = 3 and source_id = '" . $input['source_id'] . "' and user_id = '" . $_SESSION['user_id'] . "' and pid = '" . $input['type'] . "'";
        $info = $this->data_getinfo("source_dictation", $where);
        // 删除log表中的数据
        $this->data_del("dictation_log", $where);
        // 更新user表中的学习天数和最近学习的时间
        $this->doDays($_SESSION['user_id']);

        if (empty($info)) {
            $addDictation = $this->data_add("source_dictation", $data);
        } else {
            $editDictation = $this->data_edit("source_dictation", $data, " id = '" . $info['id'] . "'");
        }

        $this->ajaxReturn(200, "听写记录保存成功");
    }


    /**
     * 进入制作弹幕的页面
     * source_id 素材的id
     * type 1平台素材 2 个人素材 3 精品课程
     */
    public function textSubtitlesInfo()
    {
        $this->checkLogin();
        $input = $this->post;

        if (empty($input['source_id'])) {
            $this->ajaxReturn(202, "网络原因请刷新重试");
        }

        if (empty($input['type'])) {
            $this->ajaxReturn(202, "网络原因请刷新重试");
        }

        $where = " type = 3 and source_id ='" . $input['source_id'] . "' and pid = '" . $input['type'] . "' and user_id = '" . $_SESSION['user_id'] . "'";
        $info = $this->data_getinfo("source_subtitles", $where);

        $content = json_decode($info['content'], true);
        $subtitlesList = array();
        if (!empty($content)) {
            foreach ($content as $k => $val) {
                $subtitlesList[] = $val;
            }
        }

        $param = array(
            "subtitlesList" => $subtitlesList,
        );

        $this->ajaxReturn(200, "字幕信息获取成功", $param);
    }

    /**
     * 制作弹幕信息
     * source_id 主表的id
     * content 弹幕的内容
     * type 1平台素材  2 个人素材 3 精品课程
     */
    public function textSubtitles() 
    {
        $this->checkLogin();
        $input = $this->post;
        if (empty($input['source_id'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        if (empty($input['content'])) {
            $this->ajaxReturn(202, "弹幕信息不能为空");
        }

        if (empty($input['type'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        $list = explode("\n", $input['content']);
        if (empty($list)) {
            $this->ajaxReturn(202, "弹幕的上传格式不正确");
        }

        $subtitles = array();
        foreach ($list as $k => $val) {
            preg_match("/\[(\d{1,}:\d{2})]/", $val, $result);
            if (!empty($result)) {
                $time = $result[1];
                $explodeTime = explode(":", $time);
                $timeStamp = $explodeTime[0] * 60 + $explodeTime[1];
                $content = str_replace($result[0], '', $val);
                $subtitles[$timeStamp] = array(
                    "time" => $time,
                    "content" => $content,
                );
            }
        }

        ksort($subtitles);
        if (empty($subtitles)) {
            $this->ajaxReturn(202, "弹幕的上传格式不正确");
        }

        $sql = " type = 3 and source_id = '" . $input['source_id'] . "' and user_id = '" . $_SESSION['user_id'] . "' and pid = '" . $input['type'] . "'"; 
        $info = $this->data_getinfo("source_subtitles", $sql);

        $data = array(
            "source_id" => $input['source_id'], 
            "source_period_id" => 0, 
            "content" => addslashes(json_encode($subtitles)),
            "created_at" => time(),
            "user_id" => $_SESSION['user_id'],
            "type" => 3, 
            "pid" => $input['type'],
        );

        // 更新user表中的学习天数和最近学习的时间
        $this->doDays($_SESSION['user_id']);

        if (empty($info)) {
            $addSubtitles = $this->data_add("source_subtitles", $data);
        } else {
            $editSubtitles = $this->data_edit("source_subtitles", $data, " id = '" . $info['id'] . "'");
        }

        $this->ajaxReturn(200, "弹幕制作成功");
    }
    
    /**
     * 针对音视频的听写内容  进行的操作 添加生词 朗读  翻译 
     * 
     */
    
    /**
     * 添加/编辑生词
     * 提交（保存/编辑）生词
     * source_id 素材主表的id
     * words_id 生词表的id
     * name 生词名称
     * sort_id 分类
     * paraphrase 释义
     * pronunciation 读音
     * pronunciation_word 读音拼写
     * sentences 例句
     * associate 联想 
     * type 类型 1平台 2个人 3精品课程
     */
    public function reSubWords()
    {
        $input = $this->post;
        $this->checkLogin();
        // $this->checkUser();
        $res = $this->checkField($input);
        if ($res[0] == 202) {
            $this->ajaxReturn(202, $res[1]);
        }

        if (empty($input['type'])) {
            $input['type'] = 0;
        }
        $where = "where pid='" . $input['sort_id'] . "' and user_id='" . $_SESSION['user_id'] . "'";
        $wordsCategory = $this->data_list("source_word_sort", $where);
        if (!empty($wordsCategory)) {
            $this->ajaxReturn(202, "此分类还有下级，无法添加生词");
        }

        // 查看是否是一级  一级不允许添加
        $info = $this->data_getinfo("source_word_sort", " id = '" . $input['sort_id'] . "'");
        if ($info['pid'] == 0) {
            $this->ajaxReturn(202, "请先添加二级分类");
        }
        
        $data = array(
            "name" => $input['name'],
            "pid" => $info['pid'],
            "sort_id" => $input['sort_id'],
            "paraphrase" => $input['paraphrase'],
            "pronunciation" => str_replace($this->config['qiniu'], "", $input['pronunciation']),
            "pronunciation_words" => $input['pronunciation_words'],
            "sentences" => $input['sentences'],
            "associate" => $input['associate'],
            "user_id" => $_SESSION['user_id'],
            "type" => 3,
            "source_id" => $input['source_id'],
            "pid" => $input['type'],
        );

        // 更新user表中的学习天数和最近学习的时间
        $this->doDays($_SESSION['user_id']);

        if (empty($input['words_id'])) {
            $addWords = $this->data_add("source_words", $data);
        } else {
            $editWords = $this->data_edit("source_words", $data, "id='" . $input['words_id'] . "'");
        }

        $this->ajaxReturn(200, "生词添加成功");
    }

    // /**
    //  * 进入翻译页面
    //  * source_id 主表的id
    //  * source_period_id 附表的id
    //  * type 类型 1平台素材  2 个人素材 3 精品课程 
    //  */
    // public function reReadInfo()
    // {
    //     $input = $this->post;
    //     $this->checkLogin();

    //     if (empty($input['source_id'])) {
    //         $this->ajaxReturn(202, "参数错误请刷新重试");
    //     }

    //     if (empty($input['type'])) {
    //         $this->ajaxReturn(202, "参数错误请刷新重试");
    //     }

    //     if ($input['type'] != 2) {
    //         if (empty($input['source_period_id'])) {
    //             $this->ajaxReturn(202, "参数错误请刷新重试");
    //         } 
    //     }

    //     $source_period_id = intval($input['source_period_id']) > 0 ? intval($input['source_period_id']) : 0;
    //     $sql = "source_id = '" . $input['source_id'] . "' and source_period_id = '" . $source_period_id . "' and user_id = '" . $_SESSION['user_id'] . "' and type = 3 and pid = '" . $input['type'] . "'";
    // }
    /**
     * 朗读操作 
     * 多段音视频合成
     * source_id 资源的id
     * source_period_id 附表的id
     * path 合成的路径
     * audioList 每个合成的音频
     * type 1平台 2 个人素材 3精品课程
     */
    public function reRead()
    {
        $input = $this->post;
        $this->checkLogin();
        if (empty($input['source_id'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        if (intval($input['type']) <= 0) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        if ($input['type'] != 2) {
            if (empty($input['source_period_id'])) {
                $this->ajaxReturn(202, "参数错误请刷新重试");
            } 
        }

        if (empty($input['path'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        if (empty($input['audioList'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        $source_period_id = intval($input['source_period_id']) > 0 ? intval($input['source_period_id']) : 0;
        $data = array(
            "source_id" => $input['source_id'],
            "source_period_id" => $source_period_id,
            "path" => $input['path'],
            "created_at" => time(),
            "user_id" => $_SESSION['user_id'],
            "type" => 3,
            "pid" => $input['type'],
            "read_info" => addslashes(json_encode($input['audioList'])),
        );

        $sql = "source_id = '" . $input['source_id'] . "' and source_period_id = '" . $source_period_id . "' and user_id = '" . $_SESSION['user_id'] . "' and type = 3 and pid = '" . $input['type'] . "'";
        $info = $this->data_getinfo("source_read", $sql);
        if (empty($info)) {
            $sourceRead = $this->data_add("source_read", $data);
        } else {
            $sourceRead = $this->data_edit("source_read", $data, " id = '" . $info['id'] . "'");
        }

        if (empty($sourceRead)) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        } else {
            $this->ajaxReturn(200, "朗读操作成功");
        }

    }

    /**
     * 进入翻译页面
     * source_id 主表的id
     * source_period_id 附表的id
     * type 类型 1平台素材  2 个人素材 3 精品课程
     */
    public function reTranslationInfo()
    {
        $input = $this->post;
        $this->checkLogin();

        if (empty($input['source_id'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        if (empty($input['type'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        if ($input['type'] != 2) {
            if (empty($input['source_period_id'])) {
                $this->ajaxReturn(202, "参数错误请刷新重试");
            } 
        }

        $type = ($input['type'] == 3) ? 4 : $input['type'];
        // 获取听写的内容
        $sql = " source_id='" . $input['source_id'] . "' and source_period_id='" . $input['source_period_id'] . "' and user_id='" . $_SESSION['user_id'] . "' and type = " . $type;

        $dictation = $this->data_getinfo("source_dictation", $sql);
        if (! empty($dictation)) {
            $content = empty($dictation['content']) ? "" : htmlspecialchars_decode($dictation['content']);
        } 

        $source_period_id = intval($input['source_period_id']) > 0 ? intval($input['source_period_id']) : 0;

        $where = " source_id = '" . $input['source_id'] . "' and source_period_id = '" . $source_period_id . "' and user_id= '" . $_SESSION['user_id'] . "' and type = 3 and pid = '" . $input['type'] . "'";
        $info = $this->data_getinfo("source_translation", $where);
        $param = array(
            "dictationInfo" => $content,
            "info" => empty($info) ? (object)array() : $info,
        );

        $this->ajaxReturn(200, "翻译信息获取成功", $param);
    }

    /**
     * 添加翻译操作 文本翻译
     * source_id 主表的id
     * source_period_id 
     * content 翻译内容
     * grammar 语法
     * words 单词
     * type 类型 1平台素材 2个人素材 3精品课程
     */
    public function reTranslation()
    {
        $input = $this->post;
        $this->checkLogin();
        if (empty($input['source_id'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        if (empty($input['type'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        if ($input['type'] != 2) {
            if (empty($input['source_period_id'])) {
                $this->ajaxReturn(202, "参数错误请刷新重试");
            } 
        }

        $source_period_id = intval($input['source_period_id']) > 0 ? intval($input['source_period_id']) : 0;

        if (empty($input['content'])) {
            $this->ajaxReturn(202, "翻译内容不能为空");
        }

        if (empty($input['grammar'])) {
            $this->ajaxReturn(202, "语法不能为空");
        }

        if (empty($input['words'])) {
            $this->ajaxReturn(202, "单词不能为空");
        }

        $sql = " type = 3 and source_id = '" . $input['source_id'] . "' and source_period_id = '" . $source_period_id ."' and user_id = '" . $_SESSION['user_id'] . "' and  type = 3 and pid = '" . $input['type'] . "'";
        $info = $this->data_getinfo("source_translation", $sql);

        $data = array(
            "source_id" => $input['source_id'],
            "source_period_id" => $input['source_period_id'],
            "content" => $input['content'],
            "grammar" => $input['grammar'],
            "words" => $input['words'],
            "created_at" => time(),
            "user_id" => $_SESSION['user_id'],
            "type" => 3,
            "pid" => $input['type'],
        );

        // 更新user表中的学习天数和最近学习的时间
        $this->doDays($_SESSION['user_id']);

        if (empty($info)) {
            $addTranslation = $this->data_add("source_translation", $data);
        } else {
            $editTranslation = $this->data_edit("source_translation", $data, " id = '" . $info['id'] . "'");
        }

        $this->ajaxReturn(200, "翻译信息保存成功");
    }    

    /**
     * 我的课程
     * page 页数 1
     * status 支付状态   1未支付  2已支付  3已取消
     */
    public function myCourseList()
    {
        $siteurl = $this->siteurl;
        $this->checkLogin();
        // $this->checkUser();
        $input = $this->post;
        $page = intval($input['page']) > 0 ? intval($input['page']) : 1;
        $pageSize = intval($input['pageSize']) > 0 ? intval($input['pageSize']) : 10;
        
        $where = " where id > 0 and user_id = '" . $_SESSION['user_id'] . "'";
        if (empty($input['status'])) {
            $this->ajaxReturn(202, "请选择支付状态");
        } else {
            $where .= " and status = '" . $input['status'] . "'";
        }

        $count = $this->data_count("order", $where);

        $param = array(
            "myGoodCourse" => array(),
        );

        if (empty($count)) {
            $this->ajaxReturn(200, "暂时没有数据".$_SESSION['user_id'], $param);
        }

        $pagenum = ceil($count / $pageSize);
        if ($page > $pagenum) {
            $this->ajaxReturn(200, "数据已经加载完成", $param);
        }

        $limit = " limit ". ($page - 1) * $pageSize . "," . $pageSize;
        $list = $this->data_list("order", $where, " order by id desc ", $limit);

        $myGoodCourse = array();
        foreach ($list as $k => $val) {
            $courseInfo = $this->data_getinfo("good_course", " id = '" . $val['good_course_id'] . "'");
            $myGoodCourse[] = array(
                "id" => $val['id'],
                "good_course_id" => $val['good_course_id'],
                "title" => $val['title'],
                "image" => formatAppImageUrl($courseInfo['image'], $siteurl),
                "created_at" => $val['created_at'],
                "type" => (int)$courseInfo['type'],
                "status" => (int)$val['status'],
                "price" => sprintf("%0.2f", ($val['price'] / 100)),
                "pay_type" => (int)$val['type'],
            );
        }

        $param = array(
            "myGoodCourse" => $myGoodCourse,
            "pageNum" => $pagenum, 
        );

        $this->ajaxReturn(200, "信息获取成功", $param);
    }

    /**
     * 取消支付的接口 购买课程
     * order_id 订单的id
     */
    public function cancelOrder()
    {
        $this->checkLogin();
        $input = $this->post;
        $order_id = intval($input['order_id']);
        if (empty($order_id)) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        $orderInfo = $this->data_getinfo("order", " id = '" . $order_id . "'");
        if (empty($orderInfo)) {
            $this->ajaxReturn(202, "订单不存在");
        }

        if ($orderInfo['status'] != 1) {
            $this->ajaxReturn(202, "订单状态已经修改，无法取消");
        }

        $editOrder = $this->data_edit("order", array("status" => 3), " id = '" . $order_id . "'");
        if (!empty($editOrder)) {
            $this->ajaxReturn(200, "订单取消成功");
        } else {
            $this->ajaxReturn(202, "网络出错 订单取消失败");
        }
    }
    //后期新增的接口
    /**
     * 搜索生词列表
     * name 生词名称
     * page 页数
     */
    public function searchWords()
    {
        $this->checkLogin();
        // $this->checkUser();
        $input = $this->post;

        
        $page = intval($input['page']) > 0 ? intval($input['page']) : 1;
        $pageSize = intval($input['pageSize']) > 0 ? intval($input['pageSize']) : 15;
        
        if (empty($input['name'])) {
            $this->ajaxReturn(202, "搜索的生词不能为空");
        }

        $where = " where id > 0 and name like '%". $input['name'] ."%' ";
        $count = $this->data_count("source_words", $where);

        $param = array(
            "wordsList" => array(),
        );

        if (empty($count)) {
            $this->ajaxReturn(200, "暂时没有数据", $param);
        }

        $pagenum = ceil($count / $pageSize);
        if ($page > $pageSize) {
            $this->ajaxReturn(200, "数据加载完成", $param);
        }

        $limit = " limit " . ($page - 1) * $pageSize . "," . $pageSize;
        $list = $this->data_list("source_words", $where, "order by id desc", $limit);

        $wordsList = array();
        if (!empty($list)) {
            foreach($list as $k => $val) {
                $userinfo = $this->data_getinfo("user", "id = '" . $val['user_id'] . "'");
                $sql = " where id >0 and user_id = '" . $_SESSION['user_id'] . "' and name = '" . $val['name'] . "'";
                $myWords = $this->data_list("source_words", $sql);
                $exist = empty($myWords) ? 1 : 2; //1代表不存在生词  2 代表已经存在此生词

                $wordsList[] = array(
                    "id" => $val['id'],
                    "name" => $val['name'],
                    "pronunciation_words" => $val['pronunciation_words'],
                    "paraphrase" => $val['paraphrase'],
                    "user_id" => $val['user_id'],
                    "nickname" => $userinfo['nickname'],
                    "type" => $val['type'],
                    "is_exist" => $exist, //该词条自己词库是否存在
                );
            }
        }

        $param = array(
            "wordsList" => $wordsList,
            "pageNum" => $pagenum,
        );
        $this->ajaxReturn(200, "信息获取成功", $param);
    }

    /**
     * 添加到生词本
     * words_id 单词的id
     * sort_id 选择的生词的id
     */
    public function addOtherWords()
    {
        $this->checkLogin();
        $input = $this->post;
        if (empty($input['words_id'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        if (empty($input['sort_id'])) {
            $this->ajaxReturn(202, "请选择生词的分类");
        }

        $wordsInfo = $this->data_getinfo("source_words", " id = '" . $input['words_id'] . "'");
        if (empty($wordsInfo)) {
            $this->ajaxReturn(202, "单词信息已经删除");
        }

        $sql = "name = '" . $wordsInfo['name'] . "' and user_id = '" . $_SESSION['user_id'] . "'";
        $myselfWord = $this->data_getinfo("source_words", $sql);
        if (! empty($myselfWord)) {
            $this->ajaxReturn(202, "此生词已经存在 勿重复添加");
        }

        // 查看是否是一级  一级不允许添加
        $info = $this->data_getinfo("source_word_sort", " id = '" . $input['sort_id'] . "'");
        if ($info['pid'] == 0) {
            $this->ajaxReturn(202, "请先添加二级分类");
        }

        $data = array(
            "name" => $wordsInfo['name'],
            "pid" => $info['pid'],
            "sort_id" => $input['sort_id'],
            "paraphrase" => $wordsInfo['paraphrase'],
            "pronunciation" => $wordsInfo['pronunciation'],
            "pronunciation_words" => $wordsInfo['pronunciation_words'],
            "sentences" => $wordsInfo['sentences'],
            "associate" => $wordsInfo['associate'],
            "user_id" => $_SESSION['user_id'],
            "type" => $wordsInfo['type'],
            "source_id" => $wordsInfo['source_id'],
        );

        // 更新user表中的学习天数和最近学习的时间
        $this->doDays($_SESSION['user_id']);

        $addWords = $this->data_add("source_words", $data);
        if (empty($addWords)) {
            $this->ajaxReturn(202, "网络原因请刷新重试");
        } else {
            $this->ajaxReturn(200, "生词添加成功");
        }
    }


    // PC端的联系我们
    public function contactUs()
    {
        $siteurl = $this->siteurl;
        $list = $this->data_list("form_data_aboutUs", " where id > 0", " order by id desc", "limit 1");
        $contactInfo = array();
        if (!empty($list)) {
            $contactInfo['content'] = getImgThumbUrl($list[0]['content'], $siteurl);
            $contactInfo['map'] = getImgThumbUrl($list[0]['map'], $siteurl);
        }
        $contactInfo['addr'] = $this->config['addr'];
        $contactInfo['linkname'] = $this->config['lxr'];
        $contactInfo['tel'] = $this->config['telephone'];
        $images = $this->data_list("form_data_logo", "where id = 2 or id = 3");
        $imgs = array();
        if (!empty($images)) {
            foreach ($images as $k => $val) {
                $imgs[] = array(
                    "id" => (int)$val['id'],
                    "image" => formatAppImageUrl($val['image'], $siteurl),
                    "name" => $val['name'],
                ); 
            }
        }
        $contactInfo['imgs'] = $imgs;

        $param = array(
            "contactInfo" => $contactInfo,
        );
        $this->ajaxReturn(200, "获取联系我们信息成功", $param);
    }

    /**
     *  PC端联系我们页面留言
     * @return [type] [description]
     * mobile
     * username
     * content
     */
    public function leaveMsg()
    {
        $this->checkLogin();
        $input = $this->post;

        if(! preg_match("/^1[23456789]\d{9}$/", $input['mobile'])){
            $this->ajaxReturn(202, "手机号格式不正确");
        }

        if (empty($input['username'])) {
            $this->ajaxReturn(202, "姓名不能为空");
        }

        if (empty($input['content'])) {
            $this->ajaxReturn(202, "留言的内容不能为空");
        }

        $data = array(
            "mobile" => $input['mobile'],
            "username" => $input['username'],
            "content" => $input['content'],
            "user_id" => $_SESSION['user_id'],
            "created_at" => time(),
        );

        $addMsg = $this->data_add("message", $data);
        if (empty($addMsg)) {
            $this->ajaxReturn(202, "网络原因请刷新重试");
        } else {
            $this->ajaxReturn(200, "留言成功");
        }
    }

    /***
     * 新留言列表
     */
    public function newLeaveMsg()
    {
        $this->checkLogin();
        $input = $this->post;

//        if(! preg_match("/^1[23456789]\d{9}$/", $input['mobile'])){
//            $this->ajaxReturn(202, "手机号格式不正确");
//        }
        $res = $this->checkSmsCode($input['nation_code'] . $input['mobile'], $input['code'], "msg");
        if ($res[0] == 202) {
            $this->ajaxReturn(202, $res[1]);
        }

        if (empty($input['username'])) {
            $this->ajaxReturn(202, "姓名不能为空");
        }

        if (empty($input['content'])) {
            $this->ajaxReturn(202, "留言的内容不能为空");
        }

        $data = array(
            "mobile" => $input['nation_code'] . $input['mobile'],
            "username" => $input['username'],
            "content" => $input['content'],
            "user_id" => $_SESSION['user_id'],
            "created_at" => time(),
        );

        $addMsg = $this->data_add("message", $data);
        if (empty($addMsg)) {
            $this->ajaxReturn(202, "网络原因请刷新重试");
        } else {
            if ($res[0] == 200) {
                $this->delSmsCode($res[2]);
            }
            $this->ajaxReturn(200, "留言成功");
        }
    }

    /**
     * pc个人页面侧边栏
     * 热门平台素材
     * 
     */
    public function hotSource()
    {
        $where = " where id > 0 and position like '%" . ',2,' . "%' ";
        $list = $this->data_list("source", $where, " order by id desc", "limit 10");
        $sourceList = array();
        if (!empty($list)) {
            foreach ($list as $k => $val) {
                $sourceList[] = array(
                   "id" => $val['id'],
                   "title" => $val['title'],
                   "created_at" => date('Y-m-d H:i', $val['created_at']),
                );
            }
        }
        $param = array(
            "hotSource" => $sourceList,
        );
        $this->ajaxReturn(200, "热门平台素材获取成功", $param);
    }

    
    /**
     * 我的翻译详情页面
     * type 1 平台素材  2 个人素材  3 精品课程
     * source_id 资源的主表的id
     */
    public function sourceTranslationInfo()
    {
        $this->checkLogin();
        $siteurl = $this->siteurl;
        $input = $this->post;

        if ($input['type'] != 1 && $input['type'] != 2 && $input['type'] != 3) {
            $this->ajaxReturn(202, "不存在的素材格式");
        }

        if (empty($input['source_id'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        $maintable = "";
        $minortable = "";
        $fields = "source_id";
        switch ($input['type']) {
            case 1:
                $maintable = "source";
                $minortable = "source_text";
                break;
            case 2:
                $maintable = "user_source";
                $minortable = "user_source_text";
                $fields = "user_period_id";
                break;
            case 3:
                $maintable = "good_course";
                $minortable = "good_course_text";
                break;
            default:
                $this->ajaxReturn(202, "不存在的素材格式");
                break;
        }
        $sourceInfo = $this->data_getinfo($maintable, " id = '" . $input['source_id'] . "'");
        if (empty($sourceInfo)) {
            $this->ajaxReturn(202, "视频被删除或者下架");
        }
        // 主表的信息
        $info['id'] = (int)$sourceInfo['id'];
        $info['title'] = $sourceInfo['title'];
        $info['image'] = formatAppImageUrl($sourceInfo['image'], $siteurl);
        $info['created_at'] = date("Y-m-d H:i", $sourceInfo['created_at']);
        $info['type'] = (int)$sourceInfo['type'];

        $textList = $this->data_list($minortable, "where id >0 and `{$fields}` = '" . $input['source_id'] . "'");
        if (!empty($textList)) {
            foreach($textList as $k => $val) {
                $where = " id > 0 and source_id = '" . $input['source_id'] . "' and source_period_id = '" . $val['id'] . "' and user_id = '" . $_SESSION['user_id'] . "' and type = '" . $input['type'] . "' and pid = 0";
                $translation = $this->data_getinfo("source_translation", $where);
                $textList[$k]['translation'] = empty($translation) ? (object)array() : $translation;
                if ($input['type'] == 1) {
                    $count = 0;
                    if (!empty($translation)) {
                        $where = "where id>0 and translation_id = '" . $translation['id'] ."'";
                        $count = $this->data_count("praise", $where);
                    }
                    $textList[$k]['count'] = (int)$count; 
                }
                
            }
        }
        $param = array(
            "sourceInfo" => $info,
            "textList" => $textList
        );
        $this->ajaxReturn(200, "信息获取成功", $param);
    }


    // /**
    //  * 我的翻译详情页面 个人素材
    //  * source_id 
    //  */
    // public function userSourceTranslationInfo()
    // {
    //     $this->checkLogin();
    //     $siteurl = $this->siteurl;
    //     $input = $this->post;
    //     if (empty($input['source_id'])) {
    //         $this->ajaxReturn(202, "参数错误请刷新重试");
    //     }
    //     $sourceInfo = $this->data_getinfo("user_source", " id = '" . $input['source_id'] . "'");
    //     if (empty($sourceInfo)) {
    //         $this->ajaxReturn(202, "视频被删除或者下架");
    //     }
    //     // 主表的信息
    //     $info['id'] = (int)$sourceInfo['id'];
    //     $info['title'] = $sourceInfo['title'];
    //     $info['image'] = formatAppImageUrl($sourceInfo['image'], $siteurl);
    //     $info['created_at'] = date("Y-m-d H:i", $sourceInfo['created_at']);
    //     $info['type'] = (int)$sourceInfo['type'];

    //     $textList = $this->data_list("user_source_text", "where id >0 and user_period_id = '" . $input['source_id'] . "'");
    //     if (!empty($textList)) {
    //         foreach($textList as $k => $val) {
    //             $where = " id > 0 and source_id = '" . $input['source_id'] . "' and source_period_id = '" . $val['id'] . "' and user_id = '" . $_SESSION['user_id'] . "' and type = 2 and pid = 0";
    //             $translation = $this->data_getinfo("source_translation", $where);
    //             $textList[$k]['translation'] = empty($translation) ? (object)array() : $translation;
    //         }
    //     }
    //     $param = array(
    //         "sourceInfo" => $info,
    //         "textList" => $textList
    //     );
    //     $this->ajaxReturn(200, "信息获取成功", $param);
    // }

    /**
     * 删除操作
     * 听写 朗读 翻译 字幕 删除
     * source_id 资源的id
     * sourceType 素材类型  2个人  1平台
     * doType 操作类型 1听写  2朗读 3翻译  4字幕
     */
    public function delDosource()
    {
        $this->checkLogin();
        $input = $this->post;
        if (empty($input['source_id']) || empty($input['sourceType']) || empty($input['doType'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        $this->model->query("START TRANSACTION");
        // 删除source_log的数据
        $where = " user_id = '" . $_SESSION['user_id'] . "' and source_id = '" . $input['source_id'] . "' and source_type = '" . $input['sourceType'] . "' and do_type = '" . $input['doType'] . "'";
        $delSourceLog = $this->data_del("source_log", $where);
        if (empty($delSourceLog)) {
            $this->model->query("ROLLBACK");
            $this->ajaxReturn(202, "网络原因请刷新重试");
        }

        // 删除听写 朗迪 翻译 字幕
        $sql = " source_id = '" . $input['source_id'] . "' and user_id = '" . $_SESSION['user_id'] . "' and type = '" . $input['sourceType'] . "' and pid = 0";
        $table = "";
        switch ($input['doType']) {
            case 1:
                $table = "source_dictation";
                break;
            case 2:
                $table = 'source_read';
                break;
            case 3:
                $table = "source_translation";
                break;
            case 4:
                $table = "source_subtitles";
                break;
            default:
                $table = "";

        }
        if (empty($table)) {
            $this->model->query("ROLLBACK");
            $this->ajaxReturn(202, "网络原因请刷新重试");
        }
        $delDosource = $this->data_del($table, $sql);
        if (empty($delDosource)) {
            $this->model->query("ROLLBACK");
            $this->ajaxReturn(202, "网络原因请刷新重试");
        } else {
            $this->model->query("COMMIT");
            file_put_contents($_SESSION['user_id'] . '_' . '1.txt',$sql);
            $this->ajaxReturn(200, "删除成功");
        }
    }

    /**
     * 日签打卡
     */
    public function signDays()
    {
        $siteurl = "https://" . $this->config['siteurl'];
        $this->checkLogin();
        $userinfo = $this->data_getinfo("user", " id = '" . $_SESSION['user_id'] . "'");
        
        $signDays = $this->data_getinfo("form_data_sign_days", " days = '" . $userinfo['days'] . "'");
        if (empty ($signDays)) {
            $signDays = $this->data_list("form_data_sign_days", " where days = 0");
            $count = count($signDays) > 0 ? count($signDays) - 1 : 0;
            $i = rand(0, $count);
            $signDays = $signDays[$i];
        }

        $where = " where id > 0 and user_id = '" . $_SESSION['user_id'] . "'";
        $dictationNum = $this->data_count("source_dictation", $where);
        $readNum = $this->data_count("source_read", $where);
        $translationNum = $this->data_count("source_translation", $where);
        $wordsNum = $this->data_count("source_translation", $where);
        $subtitlesNum = $this->data_count("source_subtitles", $where);
        // 展示日签的信息
        $info = array(
            "days" => (int)$userinfo['days'],
            "dictationNum" => (int)$dictationNum,
            "readNum" => (int)$readNum,
            "translationNum" => (int)$translationNum,
            "wordsNum" =>(int)$wordsNum,
            "subtitlesNum" =>(int)$subtitlesNum,
            "avatar" => empty($userinfo['avatar']) ? "" : formatAppImageUrl($userinfo['avatar'], $siteurl),
            "nickname" => empty($userinfo['nickname']) ? "---" : $userinfo['nickname'],
            "bg_img" => formatAppImageUrl($signDays['bg_img'], $siteurl),
        );

        // 生成学习记录 （打卡记录）
        $info['created_at'] = time();
        $info['user_id'] = $_SESSION['user_id'];
        // print_r($info);die;
        $learnLog = $this->data_add("learn_log", $info);
        // 签到表的状态改成已经学习
        $starttime = strtotime(date("Y-m-d 00:00:00"));
        $endtime = strtotime(date("Y-m-d 23:59:59"));
        $sql = "created_at > '" . $starttime . "' and created_at <= '" . $endtime . "' and user_id = '" . $_SESSION['user_id'] . "'";
        $sign_log = $this->data_getinfo("sign_log",  $sql);
        if (! empty($sign_log)) {
            $editSignLog = $this->data_edit("sign_log", array("status" => 2), " id = '" . $sign_log['id'] . "'");
        }

        $param = array(
            "signInfo" => $info,
        );
        $this->ajaxReturn(200, "日签信息获取成功", $param);
    }


    /**
     * 进入签到页面
     */
    public function signLogList()
    {
        $this->checkLogin();
        $where = " where id > 0 and user_id = '" . $_SESSION['user_id'] . "'";
        $list = $this->data_list("sign_log", $where);

        $signLog = array();
        if (!empty($list)) {
            foreach ($list as $k => $val) {
                $signLog[] = array(
                    "formatTime" => date("Y-m-d", $val['created_at']),
                    "created_at" => $val['created_at'],
                    "status" => $val['status'],
                );        
            }
        }
        // 今天是否签到
        $starttime = strtotime(date("Y-m-d 00:00:00"));
        $endtime = strtotime(date("Y-m-d 23:59:59"));

        $sql = "created_at >= '" . $starttime . "' and created_at <= '" . $endtime . "' and user_id = '" . $_SESSION['user_id'] . "'";
        $signLogInfo = $this->data_getinfo("sign_log", $sql);
        $status = 1;
        if (! empty($signLogInfo)) {
            $status = 2;
        }

        $param = array(
            "signLog" => empty($signLog) ? (object)array() : $signLog,
            "status" => $status,
        );

        $this->ajaxReturn(200, "签到记录获取成功", $param);
    }
    /**
     * 签到
     * 提交保存签到数据
     * 
     */
    public function signLog()
    {
        $this->checkLogin();
        $starttime = strtotime(date("Y-m-d 00:00:00"));
        $endtime = strtotime(date("Y-m-d 23:59:59"));
        // 检查是否签到
        $sql = "created_at >= '" . $starttime . "' and created_at <= '" . $endtime . "' and user_id = '" . $_SESSION['user_id'] . "'";
        $signLogInfo = $this->data_getinfo("sign_log", $sql);
        if (! empty($signLogInfo)) {
            $this->ajaxReturn(202, "今日已签到");
        }
        // 检查是否已经学习
        $learnLog = $this->data_getinfo("learn_log", $sql);
        $status = empty($learnLog) ? 1 : 2;// 1未学习

        // 生成签到记录
        $data = array(
            "user_id" => $_SESSION['user_id'],
            "created_at" => time(),
            "status" => $status,
        );

        $addSignLog = $this->data_add("sign_log", $data);
        if (empty($addSignLog)) {
            $this->ajaxReturn(202, "网络原因请刷新重试");
        } else {
            $this->ajaxReturn(200, "签到成功");
        }
    }

    /**
     * 打卡记录
     * page 页数
     * learn_time 学习时间
     */
    public function learnLog()
    {
        $this->checkLogin();
        $input = $this->post;
        $learn_time = $input['learn_time'];
        if (empty($input['learn_time'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        $starttime = strtotime(date($learn_time . " 00:00:00"));
        $endtime = strtotime(date($learn_time . " 23:59:59"));

        $page = intval($input['page']) > 0 ? intval($input['page']) : 1;
        $pageSize = intval($input['pageSize']) > 0 ? intval($input['pageSize']) : 20;

        $where = " where created_at >= '" . $starttime . "' and created_at <= '" . $endtime . "' and user_id = '" . $_SESSION['user_id'] . "'";
        $count = $this->data_count("learn_log", $where);

        $param = array(
            "learnLogList" => array(),
        );

        if (empty($count)) {
            $this->ajaxReturn(200, "暂时没有数据", $param);
        }

        $pagenum = ceil($count / $pageSize);
        if ($page > $pagenum) {
            $this->ajaxReturn(200, "数据加载完成", $param);
        }

        $limit = " limit " . ($page - 1) * $pageSize . "," . $pageSize;
        $list = $this->data_list("learn_log", $where, " order by id desc", $limit);

        $learnLogList = array();
        if (!empty($list)) {
            foreach ($list as $k => $val) {
                $learnLogList[] = array(
                    "days" => (int)$val['days'],
                    "dictationNum" => (int)$val['dictationNum'],
                    "readNum" => (int)$val['readNum'],
                    "translationNum" => (int)$val['translationNum'],
                    "wordsNum" => (int)$val['wordsNum'],
                    "subtitles" => (int)$val['subtitlesNum'],
                    "created_at" => date("Y-m-d H:i", $val['created_at']),
                );
            }
        }

        $param = array(
            "learnLogList" => $learnLogList,
            "pageNum" => $pagenum,
        );

        $this->ajaxReturn(200, "打卡记录获取成功", $param);
    }

    /**
     *   删除生词接口
     *   words_id 生词表的id
     */
    public function delWords()
    {
        $words_id = intval($_POST['words_id']);
        if (empty($words_id)) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        $del = $this->data_del("source_words", " id = '" . $words_id . "'");
        if (empty($del)) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        } else {
            $this->ajaxReturn(200, "生词删除成功");
        }
    }

    /**
     * 点击个人标签的时候 文章列表
     * dictation_tag  标签的id
     * type  类型 
     * page 页数
     */
    public function sourceTagList()
    {
        $input = in($_POST);
        $this->checkLogin();
        $siteurl = "http://" . $this->config['siteurl'];
        $page = intval($input['page']) > 0 ? intval($input['page']) : 1;
        $pageSize = intval($input['pageSize']) ? intval($input['pageSize']) : 10;
        $dictation_tag = intval($input['dictation_tag']) > 0 ? intval($input['dictation_tag']) : 0;
        if (empty($dictation_tag)) {
            $this->ajaxReturn(202, "请选择标签");
        }

        // 资源表
        $type = intval($input['type']) > 0 ? intval($input['type']) : 0;
        $table = $this->doTable($type);
        if (empty($table)) {
            $this->ajaxReturn(202, "素材类型参数出错");
        }

        $where = "where dictation_tag like '%" . "," . $dictation_tag . "," . "%' and user_id = '" . $_SESSION['user_id'] . "' and type = '" . $input['type'] . "'";
        $count = $this->data_count("dictation_tag_log", $where);

        $param = array(
            "sourceList" => array(),
        );
        if (empty($count)) {
            $this->ajaxReturn(200, "暂时没有数据", $param);
        }

        $pagenum = ceil($count / $pageSize);
        if ($pagenum < $page) {
            $this->ajaxReturn(200, "暂时没有数据", $param);
        }

        $limit = "LIMIT " . ($page-1) * $pageSize . "," . $pageSize;
        $list = $this->data_list("dictation_tag_log", $where, " order by id desc", $limit);
        $sourceList = array();
        if (! empty($list)) {
            foreach ($list as $k => $val) {
                $info = $this->data_getinfo($table, " id = '" . $val['source_id'] . "'");
                $sourceList[] = array(
                    "id" => (int)$info['id'],
                    "image" => formatAppImageUrl($info['image'], $siteurl),
                    "type" => (int)$info['type'],
                    "title" => $info['title'],
                    "created_at" => date("Y-m-d", $info['created_at']),
                );
            }
        }

        $param = array(
            "sourceList" => $sourceList,
            "pageNum" => $pagenum,
        );

        $this->ajaxReturn(200, "ok", $param);
    }



    /**
     * 我的听写内容添加生词
     * type 1平台素材  2个人素材  3精品课程
     * source_id 资源的id
     * page 页数
     */
    public function dictationWordsList()
    {
        $this->checkLogin();
        $input = in($_POST);

        $page = intval($input['page']) > 0 ? intval($input['page']) : 1;
        $pageSize = intval($input['pageSize']) > 0 ? intval($input['pageSize']) : 40;
        $source_id = intval($input['source_id']);
        $type = intval($input['type']);

        if (empty($input['source_id'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        $table = $this->doTable($input['type']);
        if (empty($table)) {
            $this->ajaxReturn(202, "素材类型参数错误");
        }

        $where = "where user_id = '" . $_SESSION['user_id'] . "' and source_id = '" . $source_id . "' and type = 3 and pid = '" . $type . "'";
        $count = $this->data_count("source_words", $where);

        $param = array(
            "wordsList" => array(),
        );
        if (empty($count)) {
            $this->ajaxReturn(200, "暂时没有数据", $param);
        }

        $pagenum = ceil($count / $pageSize);
        if ($pagenum < $page) {
            $this->ajaxReturn(200, "暂时没有数据", $param);
        }

        $limit = "limit " . ($page-1) * $pageSize . "," . $pageSize;
        $list = $this->data_list("source_words", $where, "order by id desc", $limit);

        $wordsList = array();
        if (! empty($list)) {
            foreach ($list as $k => $val) {
                $wordsList[] = array(
                    "id" => (int)$val['id'],
                    "name" => $val['name'],
                    "pronunciation_words" => $val['pronunciation_words'],
                    "paraphrase" => $val['paraphrase'],
                );
            }
        }

        $param = array(
            "wordsList" => $wordsList,
        );

        $this->ajaxReturn(200, "单词列表获取成功", $param);
    }

    /**
     * 平台客服二维码
     */
    public function code()
    {
        $siteurl = "http://" . $this->config['siteurl'];
        $list = $this->data_list('form_data_kefu_code', 'where id > 0', 'order by id desc', 'limit 1');
        $info = array(
            'id' => $list[0]['id'] ? :0,
            'code' => $list[0]['image'] ? formatAppImageUrl($list[0]['image'],$siteurl) : "",
        );
        $this->ajaxReturn(200, '获取信息成功！', $info);
    }


    /**
     * 意见反馈信息查询
     * type 1意见反馈  2用户留言  3文章纠错
     * page
     */
    public function replyinfo()
    {
        $this->checkLogin();
        $input = in($_POST);
        $page = intval($input['page']) > 0 ? intval($input['page']) : 1;
        $pageSize = intval($input['pageSize']) > 0 ? intval($input['pageSize']) : 20;
        $table = '';
        $time_field = '';
        switch($input['type']){
            case 1:
                $table = 'suggest';
                $time_field = 'suggest_time';
                break;
            case 2:
                $table = 'message';
                $time_field = 'message_time';
                break;
            case 3:
                $table = 'report_error';
                $time_field = 'report_error_time';
                break;
            default:
                $this->ajaxReturn(202, '参数错误' , $input);
                break;
        }

        // 修改会员状态
        $data = [
            $time_field => time(),
        ];
        $edit = $this->data_edit('user', $data, ' id="' . $_SESSION['user_id'] . '"' );
        $where = "where user_id ='" . $_SESSION['user_id'] . "'";
        $count = $this->data_count($table, $where);

        $param = [
            'list' => [],
        ];
        if (empty($count)) {
            $this->ajaxReturn(200, "暂时没有数据",$param);
        }

        $pagenum = ceil($count / $pageSize);
        if ($pagenum < $page) {
            $this->ajaxReturn(200, "暂时没有数据",$param);
        }

        $limit = "limit " . ($page-1) * $pageSize . "," . $pageSize;
        $list = $this->data_list($table, $where, "order by id desc", $limit);

        $items = array();
        $source = array();
        foreach($list as $k => $val) {
            if ($input['type'] == 3) {
                $ta = $val['type'] == 1 ? 'source' : 'good_course';
                $info = $this->data_getinfo($ta,' id =' . $val['source_id']);
                $source = array(
                    'id' => $val['source_id'],
                    'type' => $val['type'],
                    'title' => $info['title'],
                );
            }
            $items[] = array(
                'id' => $val['id'],
                "content" => $val['content'],
                'created_at' => date('Y-m-d', $val['created_at']),
                'reply' => $val['reply'] ? :'',
                'reply_at' => date('Y-m-d', $val['reply_at']),
                'source' => $source ? :(object)array(),
            );
        }

        $param = array(
            'pagenum' => $pagenum,
            'list' => $items,
        );
        $this->ajaxReturn(200, '信息获取成功！', $param);
    }


    /**
     * 1素材纠错 2意见反馈 3留言信息 4系统公告 5评论回复
     * 显示是否有未读的信息
     */
    public function message()
    {
        $this->checkLogin();
        $userinfo = $this->userinfo;
        // 素材纠错信息
        $report_error = $this->data_list('report_error', ' where user_id ="' . $_SESSION['user_id'] . '"', ' order by reply_at desc', ' limit 1');
        $report_error_count = 0;
        $is_report_error = 1;
        if ($userinfo['report_error_time'] < $report_error[0]['reply_at']) {
            $where = ' where user_id="' . $_SESSION['user_id'] . '" and reply_at > "' . $userinfo['report_error_time'] . '"';
            $is_report_error = 2;
            $report_error_count = $this->data_count('report_error','report_error', $where);
        }
        // 意见反馈信息 是否未读
        $suggest = $this->data_list('suggest', ' where user_id="' . $_SESSION['user_id'] . '" ', " order by reply_at desc",'limit 1');
        $suggest_count = 0;
        $is_suggest = 1;
        if ($userinfo['suggest_time'] < $suggest[0]['reply_at']) {
            $where = ' where user_id="' . $_SESSION['user_id'] . '" and reply_at > "' . $userinfo['suggest_time'] . '"';
            $is_suggest = 2;
            $suggest_count = $this->data_count('suggest', $where);
        }
        // 留言信息的时候
        $message = $this->data_list('message', ' where  user_id="' . $_SESSION['user_id'] . '"', ' order by reply_at desc', ' limit 1');
        $message_count = 0;
        $is_message = 1;
        if ($userinfo['message_time'] < $message[0]['reply_at']) {
            $where = ' where user_id="' . $_SESSION['user_id'] . '" and reply_at > "' . $userinfo['message_time'] . '"';
            $message_count = $this->data_count('message', $where);;
            $is_message = 2;
        }
        // 系统公告
        $notice = $this->data_list('form_data_notice', " where id > 0", ' order by updated_at desc', 'limit 1');
        $notice_count = 0;
        $is_notice = 1;
        if ($userinfo['notice_time'] < $notice[0]['updated_at']) {
            $where = ' where id>0 and updated_at > "' . $userinfo['notice_time'] . '"';
            $notice_count = $this->data_count('form_data_notice', $where);
            $is_notice = 2;
        }
        // 评论信息页面
        $logList = $this->data_list('punch_card_log',' where  user_id = ' . $_SESSION['user_id']);
        $ids = implode(',', array_column($logList,'id'));

        // 收到的评论
        $count_comment = 0;
        if ($ids) {
            $sql = " select count(1) as num from {$this->model->pre}source_comment where source_id in (" . $ids . ") and created_at > " . $userinfo['comment_time'] . " and type=6";
            $countArr = $this->model->query($sql);
            $count_comment = $countArr[0]['num'] ? : 0;
        }
        // 收到的回复
        $where = ' where user_id = ' . $_SESSION['user_id'] . ' and reply_user_id > 0 and type = 6 and reply_at > ' . $userinfo['reply_time'];
        $count_reply = $this->data_count('source_comment', $where);
        $comment_count = (int)$count_comment + (int)$count_reply;

        $param = [
            'report_error_count' => (int)$report_error_count,
//            'is_report_error' => $is_report_error,
            'suggest_count' => (int)$suggest_count,
//            'is_suggest' => $is_suggest,
            'message_count' => (int)$message_count,
//            'is_message' => $is_message,
            'notice_count' => (int)$notice_count,
//            'is_notice' => $is_notice,
            'comment_count' => (int)$comment_count,
//            'is_comment' => $is_comment,
        ];
        $this->ajaxReturn(200, '信息获取成功！', $param);
    }

    /**
     * 收到的评价列表
     * page 当前页数
     *
     */
    public function getCommentList()
    {
        $this->checkLogin();
        $input = $this->post;
        $siteurl = $this->siteurl;

        $page = intval($_POST['page']) ? intval($_POST['page']) : 1;
        $pageSize = intval($_POST['pageSize']) ? intval($_POST['pageSize']) : 20;

        $logList = $this->data_list('punch_card_log',' where  user_id = ' . $_SESSION['user_id']);
        $ids = implode(',', array_column($logList,'id'));

        $param = [
            'list' => [],
        ];
        if (!$ids) {
            $this->ajaxReturn(200,'暂时没有数据', $param);
        }

        $where = ' where source_id in (' . $ids . ') and type=6 ';
        $count = $this->data_count('source_comment', $where);
        if (!$count) {
            $this->ajaxReturn(200,'暂时没有数据', $param);
        }

        $pageNum = ceil($count / $pageSize);
        if ($page > $pageNum) {
            $this->ajaxReturn(200, '数据加载完成！', $param);
        }

        $limit = ' limit ' . ($page - 1) * $pageSize . ',' . $pageSize;
        $list = $this->data_list('source_comment', $where, ' order by id desc', $limit);
        $items = [];
        foreach ($list as $k => $val) {
            $userinfo = $this->data_getinfo('user', ' id = ' . $val['user_id']);
            $reply = $this->data_getinfo('user', ' id = ' . $val['reply_user_id']);
            $items[] = [
                'comment_id' => (int)$val['id'],
                'user_id' => (int)$userinfo['id'],
                'user_nickname' => $userinfo['nickname'],
                'user_avatar' => formatAppImageUrl($userinfo['avatar'], $siteurl),
                'content' => $val['content'],
                'created_at' => $this->formatTime($val['created_at']),
                'reply_user_id' => $reply['id'] ?  (int)$reply['id']:0,
                'reply_nickname' => $reply['nickname'] ? :'',
                'reply_content' => $val['reply_content'] ? :'',
            ];
        }

        // 更新user表中的时间
        $this->data_edit('user', array('comment_time' => time()), ' id = ' . $_SESSION['user_id']);

        $param = [
            'list' => $items,
            'pageNum' => $pageNum,
        ];
        $this->ajaxReturn(200, '收到的评论列表获取成功！', $param);
    }

    /**
     * 收到的回复列表
     * page 当前页数
     */
    public function getReplyList()
    {
        $this->checkLogin();
        $input = $this->post;
        $userinfo = $this->userinfo;
        $siteurl = $this->siteurl;

        $page = intval($_POST['page']) ? intval($_POST['page']) : 1;
        $pageSize = intval($_POST['pageSize']) ? intval($_POST['pageSize']) : 20;

        $param = [
            'list' => [],
        ];
        $where = ' where id > 0 and user_id = ' . $_SESSION['user_id'] . ' and type=6 and reply_at > 0';
        $count = $this->data_count('source_comment', $where);
        if (!$count) {
            $this->ajaxReturn(200,'暂时没有数据', $param);
        }

        $pageNum = ceil($count / $pageSize);
        if ($page > $pageNum) {
            $this->ajaxReturn(200, '数据加载完成！', $param);
        }

        $limit = ' limit ' . ($page - 1) * $pageSize . "," . $pageSize;
        $list = $this->data_list('source_comment', $where, ' order by reply_at desc', $limit);

        $items = [];
        foreach ($list as $k => $val) {
            $userinfo = $this->data_getinfo('user', ' id = ' . $val['user_id']);
            $reply = $this->data_getinfo('user', ' id = ' . $val['reply_user_id']);
            $items[] = [
                'comment_id' => (int)$val['id'],
                'user_id' => (int)$userinfo['id'],
                'user_nickname' => $userinfo['nickname'],
                'user_avatar' => formatAppImageUrl($userinfo['avatar'], $siteurl),
                'content' => $val['content'],
                'created_at' => $this->formatTime($val['created_at']),
                'reply_user_id' => $reply['id'] ?  (int)$reply['id']:0,
                'reply_nickname' => $reply['nickname'] ? :'',
                'reply_content' => $val['reply_content'] ? :'',
            ];
        }

        // 更新user表中的时间
        $this->data_edit('user', array('reply_time' => time()), ' id = ' . $_SESSION['user_id']);
        $param = [
            'list' => $items,
            'pageNum' => $pageNum,
        ];
        $this->ajaxReturn(200,'收到的回复列表获取成功！', $param);
    }

    /**
     * 系统公告
     * page 页数
     */
    public function noticeList()
    {
        $siteurl = $this->siteurl;
        $page = intval($_POST['page']) ? intval($_POST['page']) : 1;
        $pageSize = intval($_POST['pageSize']) ? intval($_POST['pageSize']) : 20;

        $where = ' where id > 0 ';
        $order = ' order by updated_at desc';
        $count = $this->data_count('form_data_notice', $where);
        $param = [
            'list' => [],
        ];
        if (!$count) {
            $this->ajaxReturn(200, "暂时没有数据", $param);
        }
        $pageNum = ceil($count / $pageSize);
        if ($page > $pageNum) {
            $this->ajaxReturn(200, '数据加载完成！', $param);
        }

        $limit = ' LIMIT ' . ($page-1) * $pageSize . ',' . $pageSize;
        $list = $this->data_list('form_data_notice',$where,$order,$limit);
        $items = [];
        foreach($list as $k => $val) {
            $items[] = [
                'id' => $val['id'],
                'content' => $val['content'],
                'created_at' => date('Y-m-d H:i', $val['updated_at']),
            ];
        }
        // 编辑user表中进入时间
        if (isset($_SESSION['user_id'])) {
            $data = [
                'notice_time' => time(),
            ];
            $edit = $this->data_edit('user', $data, ' id= "' . $_SESSION['user_id'] . '"');
        }
        $param = [
            'list' => $items,
            'pageNum' => $pageNum,
        ];
        $this->ajaxReturn(200, '信息获取成功!', $param);
    }


    public function isShow()
    {
        $userinfo = $this->userinfo;
        // 素材纠错信息
        $report_error = $this->data_list('report_error', ' where user_id ="' . $_SESSION['user_id'] . '"', ' order by reply_at desc', ' limit 1');
        $report_error_count = 0;
        $is_report_error = 1;
        if ($userinfo['report_error_time'] < $report_error[0]['reply_at']) {
            $where = ' where user_id="' . $_SESSION['user_id'] . '" and reply_at > "' . $userinfo['report_error_time'] . '"';
            $is_report_error = 2;
            $report_error_count = $this->data_count('report_error','report_error', $where);
        }
        // 意见反馈信息 是否未读
        $suggest = $this->data_list('suggest', ' where user_id="' . $_SESSION['user_id'] . '" ', " order by reply_at desc",'limit 1');
        $suggest_count = 0;
        $is_suggest = 1;
        if ($userinfo['suggest_time'] < $suggest[0]['reply_at']) {
            $where = ' where user_id="' . $_SESSION['user_id'] . '" and reply_at > "' . $userinfo['suggest_time'] . '"';
            $is_suggest = 2;
            $suggest_count = $this->data_count('suggest', $where);
        }
        // 留言信息的时候
        $message = $this->data_list('message', ' where  user_id="' . $_SESSION['user_id'] . '"', ' order by reply_at desc', ' limit 1');
        $message_count = 0;
        $is_message = 1;
        if ($userinfo['message_time'] < $message[0]['reply_at']) {
            $where = ' where user_id="' . $_SESSION['user_id'] . '" and reply_at > "' . $userinfo['message_time'] . '"';
            $message_count = $this->data_count('message', $where);;
            $is_message = 2;
        }
        // 系统公告
        $notice = $this->data_list('form_data_notice', " where id > 0", ' order by updated_at desc', 'limit 1');
        $notice_count = 0;
        $is_notice = 1;
        if ($userinfo['notice_time'] < $notice[0]['updated_at']) {
            $where = ' where id>0 and updated_at > "' . $userinfo['notice_time'] . '"';
            $notice_count = $this->data_count('form_data_notice', $where);
            $is_notice = 2;
        }
        // 评论信息页面
        $logList = $this->data_list('punch_card_log',' where  user_id = ' . $_SESSION['user_id']);
        $ids = implode(',', array_column($logList,'id'));

        // 收到的评论
        $count_comment = 0;
        if ($ids) {
            $sql = " select count(1) as num from {$this->model->pre}source_comment where source_id in (" . $ids . ") and created_at > " . $userinfo['comment_time'] . " and type=6";
            $countArr = $this->model->query($sql);
            $count_comment = $countArr[0]['num'] ? : 0;
        }
        // 收到的回复
        $where = ' where user_id = ' . $_SESSION['user_id'] . ' and reply_user_id > 0 and type = 6 and reply_at > ' . $userinfo['reply_time'];
        $count_reply = $this->data_count('source_comment', $where);
        $comment_count = (int)$count_comment + (int)$count_reply;

        $param = [
            'report_error_count' => (int)$report_error_count,
//            'is_report_error' => $is_report_error,
            'suggest_count' => (int)$suggest_count,
//            'is_suggest' => $is_suggest,
            'message_count' => (int)$message_count,
//            'is_message' => $is_message,
            'notice_count' => (int)$notice_count,
//            'is_notice' => $is_notice,
            'comment_count' => (int)$comment_count,
//            'is_comment' => $is_comment,
        ];

        return $param;
    }


    // 个人中心我的收藏列表
    public function collectList()
    {
        $this->checkLogin();
        $input = $this->post;
        $siteurl = $this->siteurl;

        $page = intval($input['page']) > 0 ? intval($input['page']) : 1;
        $pageSize = intval($input['pageSize']) > 0 ? intval($input['pageSize']) : 15 ;


        $where = ' where id > 0 and user_id = ' . $_SESSION['user_id'];
        if ($input['type'] == 2) {
            $where .= ' and type =1';
        }
        $param = [
            'list' => [],
        ];
        $count = $this->data_count('collect', $where);
        if (!$count) {
            $this->ajaxReturn(200, '暂时没有数据',$param);
        }

        $pageNum = ceil($count / $pageSize);
        if ($page > $pageNum) {
            $this->ajaxReturn(200, '数据加载完成', $param);
        }

        $limit = ' limit ' . ($page - 1) * $pageSize . ',' . $pageSize;
        $list = $this->data_list('collect', $where, 'order by id desc', $limit);
        $items = [];
        foreach($list as $k => $val) {
            if ($val['type'] == 1) {
                $info = $this->data_getinfo('source', ' id = ' . $val['source_id']);
            }else{
                $info = $this->data_getinfo('good_course',' id = ' . $val['source_id']);
            }
            $items[] = [
                'id' => (int)$val['id'],
                'source_id' => (int)$val['source_id'],
                "image" => formatAppImageUrl($info['image'], $siteurl),
                "title" => $info['title'] ? :'',
                "created_at" => date("Y-m-d H:i", $info['created_at']),
                "type" => $info['type'] ? :'',
                'collect_type' => (int)$val['type'],
            ];
        }

        $param = [
            'pageNum' => $pageNum,
            'list' => $items,
        ];
        $this->ajaxReturn(200, '收藏列表获取成功！', $param);
    }

    // 刪除收藏 传值格式：1,2,3
    public function delCollect()
    {
        $input = $this->post;
        if (!$input['ids']) {
            $this->ajaxReturn(202, '请选择要删除的文章');
        }

        $del = $this->data_del('collect', ' id in (' . $input['ids'] . ')');
        $this->ajaxReturn(200, '收藏取消成功!');
    }
}