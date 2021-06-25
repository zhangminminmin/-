<?php
/**
 * 朋友圈动态
 * 发布朋友圈 评论
 * 朋友圈的列表信息
 */
class circleMod extends commonMod 
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
        if (!$_SESSION['user_id']) {
            $this->ajaxReturn(203, "未登录");
        }

        $info = $this->data_getinfo("user", "id='" . $_SESSION['user_id'] . "'");
        if (empty($info['nickname']) || empty($info['avatar'])) {
            $this->ajaxReturn(401, "请先去个人中心完善资料", $_SESSION['user_id']);
        }

        $this->userinfo = $info;
    }

    /**
     * 检测是否可以进行听写朗读 等操作
     * 是会员的话  看是否到期 到期的话  不能操作
     * 不是会员的 不能操作
     * type  1时候
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


    /**
     * 朋友圈分类类表
     * 
     */
    public function friendCircleSort()
    {
        $categorys = $this->data_list("friend_circle_sort", "where id>0", "order by id desc");
        $categoryList = array();
        if (!empty($categorys)) {
            foreach ($categorys as $k => $val) {
                $categoryList[] = array(
                    "id" => $val['id'],
                    "name"=> $val['name'],
                );
            }
        }

        $param = array("categoryList" => $categoryList);
        $this->ajaxReturn(200, "分类数据获取成功", $param);
    }


    public function friendCircleSort_ios()
    {
        $categorys = $this->data_list("friend_circle_sort", "where id>0", "order by id desc");
        $categoryList = array();
        if (!empty($categorys)) {
            foreach ($categorys as $k => $val) {
                $categoryList[] = array(
                    "id" => (int)$val['id'],
                    "name"=> $val['name'],
                );
            }
        }

        $array = array(
            "id" => 0,
            "name" => "全部",
        );

        array_unshift($categoryList, $array);
        $param = array("categoryList" => $categoryList);
        $this->ajaxReturn(200, "分类数据获取成功", $param);
    }

    /**
     * 发布朋友圈
     * sort_id 分类id
     * title 标题
     * images 图片
     */
    public function subFriendsCircle()
    {
        $this->checkLogin();
        $input = $this->post;

        $this->checkUser();
        $sort_id = intval($input['sort_id']);
        if ($sort_id <= 0){
            $this->ajaxReturn(202, "请选择所属分类");
        }

        if (empty($input['title'])) {
            $this->ajaxReturn(202, "请输入动态信息描述");
        }

        $images = array();
        if (!empty($_FILES['images'])) {
            $images = imageUpload($_FILES['images'], "circle" . time() . '_' . $_SESSION['user_id']);
        }

        $data = array(
            "title" => $input['title'],
            "sort_id" => $input['sort_id'],
            "images" => json_encode($images),
            "created_at" => time(),
            "user_id" => $_SESSION['user_id'],
        );

        $addCircle = $this->data_add("friend_circle", $data);
        $this->ajaxReturn(200, "动态发布成功");
    }

    /**
     * 发布朋友圈
     * sort_id 分类id
     * title 标题
     * images 图片
     */
    public function subFriendsCircle_miniapp()
    {
        $this->checkLogin();
        $input = $this->post;

        $this->checkUser();
        $sort_id = intval($input['sort_id']);
        if ($sort_id <= 0){
            $this->ajaxReturn(202, "请选择所属分类");
        }

        if (empty($input['title'])) {
            $this->ajaxReturn(202, "请输入动态信息描述");
        }

        $images = array();
        if (!empty($input['images'])) {
            $images = explode(',', $input['images']);
        }
        $data = array(
            "title" => $input['title'],
            "sort_id" => $input['sort_id'],
            "images" => json_encode($images),
            "created_at" => time(),
            "user_id" => $_SESSION['user_id'],
            'status' => 1,
        );

        $addCircle = $this->data_add("friend_circle", $data);
        $this->ajaxReturn(0,'信息已发布,等待后台审核！');
//        $this->ajaxReturn(200, "动态发布成功");
    }
    /**
     * 动态朋友圈列表
     * sort_id 分类的id
     * page 页数
     */
    public function friendCircleList()
    {
        $input = $this->post;
        $siteurl = $this->siteurl;
        
        if (!$_SESSION['user_id']) {
            $this->ajaxReturn(203, "未登录");
        }
        // 查 屏蔽表
        $list = model('u')->data_list('shield', 'where user_id=' . $_SESSION['user_id'] .' and type=1');
        $list1 = model('u')->data_list('shield', 'where user_id=' . $_SESSION['user_id'] .' and type=2');

        $circle_ids  = 0;
        if (!empty($list)) {
            $circle_ids = array_column($list,'circle_id');
            $circle_ids = implode(',', $circle_ids);
        }

        $shield_ids = 0;
        if (!empty($list1)) {
            $shield_ids = array_column($list1,'shield_id');
            $shield_ids = implode(',', $shield_ids);
        }

        $where = "WHERE id > 0 and status=0 ";
        $order = "ORDER BY id DESC";
        if (!empty($circle_ids)) {
            $where .= 'AND id not in (' . $circle_ids . ')';
        }

        if (!empty($shield_ids)) {
            $where .= 'AND user_id not in (' . $shield_ids .')';
        }

        if (!empty($input['sort_id'])) {
            $where .= "AND sort_id = '" . $input['sort_id'] . "'";
        }

        $param = array("circleList" => array());

        $page = intval($input['page']) > 0 ? intval($input['page']) : 1;
        $pageSize = intval($input['pageSize']) > 0 ? intval($input['pageSize']) : 8;
        $count = $this->data_count("friend_circle", $where);
        if (empty($count)) {
            $this->ajaxReturn(200, "暂时没有数据", $param);
        }

        $pagenum = ceil($count / $pageSize);
        if ($page > $pagenum) {
            $this->ajaxReturn(200, "数据加载完了", $param);
        }

        $limit = " LIMIT " . ($page-1) * $pageSize . "," . $pageSize; 
        $list = $this->data_list("friend_circle", $where, $order, $limit);
        $circleList = array();
        if (!empty($list)) {
            foreach ($list as $k => $val) {
                $userinfo = $this->data_getinfo("user", "id='" . $val['user_id'] . "'");
                $images = $this->jsonImage($val['images'], $siteurl);
                $count = $this->data_count("comment", "where id>0 and circle_id='" . $val['id'] . "'");
                $comment = $this->data_list("comment", " where id > 0 and circle_id = '" . $val['id'] . "'", "order by id desc");
                $commentList = array();
                if (!empty($comment)) {
                    foreach($comment as $k => $v) {
                        $userInfo = $this->data_getinfo("user", "id = '" . $v['user_id']. "'");
                        $replyuserInfo = $this->data_getinfo("user", "id = '" . $v['reply_user_id']. "'");
                        $commentList[] = array(
                            "user_id" => $v['user_id'],
                            "username" => $userInfo['nickname'],
                            "reply_user_id" => empty($v['reply_user_id']) ? " " : $v['reply_user_id'],
                            "replyname" => empty($replyuserInfo['nickname']) ? "" : $replyuserInfo['nickname'],
                            "content" => $v['content'],
                        );
                    }
                }
                $time = $this->formatTime($val['created_at']);
                $circleList[] = array(
                    "id" => $val['id'],
                    "user_id" => $val['user_id'],
                    "avatar" => formatAppImageUrl($userinfo['avatar'], $siteurl),
                    "nickname" => $userinfo['nickname'],
                    "title" => $val['title'],
                    "images" => $images,
                    "count" => $count,
                    "time" => $time,
                    "commentList" => $commentList,
                );
            }
        }

        $param = array(
            "circleList" => $circleList,
            "pageNum" => $pagenum
        );
        $this->ajaxReturn(200, "动态圈数据获取成功", $param);
    }

    /**
     * 动态圈详情
     * id 动态圈的id
     */
    public function friendCircleInfo()
    {
        $siteurl = $this->siteurl;
        $this->checkLogin();
        $input = $this->post;
        if (empty($input['id'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }
        $where = "where id > 0 and circle_id='" . $input['id'] . "'";
        $circle = $this->data_getinfo("friend_circle", "id='" . $input['id'] . "'");

        if (empty($circle)) {
            $this->ajaxReturn(202, "动态已经被删除");
        }

        $userinfo = $this->data_getinfo("user", "id='" . $circle['user_id'] . "'");
        $count = $this->data_count("comment", $where);

        // 评论列表
        $comment = $this->data_list("comment", $where, "order by id desc");
        $commentList = array();
        if (!empty($comment)) {
            foreach ($comment as $k => $val) {
                $userInfo = $this->data_getinfo("user", "id='" . $val['user_id'] . "'");
                $replyuserinfo = $this->data_getinfo("user", "id='" . $val['reply_user_id'] . "'");
                $commentList[] = array(
                    "user_id" => $val['user_id'],
                    "username" => $userInfo['nickname'],
                    "reply_user_id" => $val['reply_user_id'],
                    "replyusername" => empty($replyuserinfo['nickname']) ? "" : $replyuserinfo['nickname'],
                    "content" => $val['content'],
                );  
            }
        }
        $circleInfo = array(
            "id" => $circle['id'],
            "user_id" => $circle['user_id'],
            "avatar" => formatAppImageUrl($userinfo['avatar'], $siteurl),
            "nickname" => $userinfo['nickname'],
            "time" => $this->formatTime($circle['created_at']),
            "title" => $circle['title'],
            "images" => $this->jsonImage($circle['images'], $siteurl),
            "count" => $count,
        );

        $param = array(
            "commentList" => $commentList,
            "circleInfo" => $circleInfo,
        );
        $this->ajaxReturn(200, "动态圈详情信息获取成功", $param);
    }

    /**
     * 评论提交
     * circle_id 动态圈的id
     * reply_user_id 被回复人的id
     * content 回复内容
     */
    public function doComment()
    {
        $this->checkLogin();
        $input = $this->post;
        $this->checkUser();
        if (empty($input['circle_id'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        if (empty($input['content'])) {
            $this->ajaxReturn(202, "评论内容不能为空");
        }
        $reply_user_id = empty($input['reply_user_id']) ? 0 : $input['reply_user_id'];

        $data = array(
            "user_id" => $_SESSION['user_id'],
            "circle_id" => $input['circle_id'],
            "reply_user_id" =>$reply_user_id,
            "created_at" => time(),
            "content" => $input['content'],
        );
        $add = $this->data_add("comment", $data);
        $this->ajaxReturn(200, "评论成功");
    }

    /**
     * 举报信息的分类
     */
    public function complainList()
    {
        $list = model('u')->data_list('form_data_complain', 'where id > 0', 'order by sort desc');
        $items = [];
        foreach ($list as $item) {
            $items[] = [
                'id' => (int)$item['id'],
                'name' => $item['name'],
            ];
        }
        $param = [
            'list' => $items,
        ];
        $this->ajaxReturn(200, '分类信息获取成功！', $param);
    }
    /**
     * 聚报某条动态
     * circle_id 朋友圈id
     * sort_id 分类id
     * content 举报内容
     * images 举报图片
     */
    public function reportCircle()
    {
        $this->checkLogin();
        $input = in($_POST);
        if (empty($input['circle_id'])) {
            $this->ajaxReturn(202, '参数错误 请刷新重试！',$input);
        }

        if (empty($input['sort_id'])) {
            $this->ajaxReturn(202, '请选择分类的id');
        }

        $info = model('u')->data_getinfo('form_data_complain', ' id = ' . $input['sort_id']);
        if (empty($input['content']) && empty($_FILES['images'])) {
            $this->ajaxReturn(202, '请写入你的举报信息');
        }
        $images = array();
        if (!empty($_FILES['images'])) {
            $images = imageUpload($_FILES['images'], "complain_circle" . time() . '_' . $_SESSION['user_id']);
        }

        $m = model('u')->data_list('complain_circle', 'where id >0 and user_id = "' . $_SESSION['user_id'] . '" and circle_id =' . $input['circle_id']);
        if ($m) {
            $this->ajaxReturn(202, '此条动态已举报  请等待处理！');
        }
        $data = [
            'user_id' => $_SESSION['user_id'],
            'circle_id' => $input['circle_id'],
            'sort_name' => $info['sort_name'],
            'sort_id' => $input['sort_id'],
            'content' => $input['content'],
            'images' => json_encode($images),
            'created_at' => time(),
            'status' => 1,
        ];
        $add = model('u')->data_add('complain_circle', $data);
        $this->ajaxReturn(200, '动态举报成功！');
    }

    /**
     * 聚报某条动态
     * circle_id 朋友圈id
     * sort_id 分类id
     * content 举报内容
     * images 举报图片
     */
    public function reportCircle_miniapp()
    {
        $this->checkLogin();
        $input = in($_POST);
        if (empty($input['circle_id'])) {
            $this->ajaxReturn(202, '参数错误 请刷新重试！',$input);
        }

        if (empty($input['sort_id'])) {
            $this->ajaxReturn(202, '请选择分类的id');
        }

        $info = model('u')->data_getinfo('form_data_complain', ' id = ' . $input['sort_id']);
        if (empty($input['content']) && empty($images)) {
            $this->ajaxReturn(202, '请写入你的举报信息');
        }
        $images = array();
        if (!empty($images)) {
            $images = explode(',',$images);
        }

        $m = model('u')->data_list('complain_circle', 'where id >0 and user_id = "' . $_SESSION['user_id'] . '" and circle_id =' . $input['circle_id']);
        if ($m) {
            $this->ajaxReturn(202, '此条动态已举报  请等待处理！');
        }
        $data = [
            'user_id' => $_SESSION['user_id'],
            'circle_id' => $input['circle_id'],
            'sort_name' => $info['sort_name'],
            'sort_id' => $input['sort_id'],
            'content' => $input['content'],
            'images' => json_encode($images),
            'created_at' => time(),
            'status' => 1,
        ];
        $add = model('u')->data_add('complain_circle', $data);
        $this->ajaxReturn(200, '动态举报成功！');
    }

    /**
     * 屏蔽某条动态 或者屏蔽某个人的动态
     * type 類型 1屏蔽动态  2屏蔽人
     * shield_id 被屏蔽人的id
     * circle_id 被屏蔽动态的id
     */
    public function shield()
    {
        $this->checkLogin();
        $input = $this->post;
        switch($input['type']) {
            case 1 :
                if (!$input['circle_id']) {
                    $this->ajaxReturn(202, '动态参数传入错误！');
                }
                $data = [
                    'user_id' => $_SESSION['user_id'],
                    'type' => 1,
                    'shield_id' => 0,
                    'circle_id' => $input['circle_id'],
                    'created_at' => time(),
                ];
                break;
            case 2:
                if (!$input['shield_id']) {
                    $this->ajaxReturn(202, '被屏蔽人参数错误');
                }

                $data = [
                    'user_id' => $_SESSION['user_id'],
                    'type' => 2,
                    'shield_id' => $input['shield_id'],
                    'circle_id' => 0,
                    'created_at' => time(),
                ];
                break;
            default :
                return ajaxReturn(202, '参数错误 请刷新重试！');
                break;
        }
        if ($_SESSION['user_id'] == $input['shield_id']) {
            $this->ajaxReturn(200, '无法屏蔽自己的文章或者动态！');
        }
        $add = model('u')->data_add('shield', $data);
        $this->ajaxReturn(200, '信息屏蔽成功！');
    }
}