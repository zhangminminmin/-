<?php
/**
 * 精品课程列表
 * 关于精品课程的操作  听写 朗读 翻译等
 * 
 */
class goodCourseMod extends commonMod 
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
                $this->ajaxReturn(202, "不是会员，没有操作权限", $_SESSION['user_id']);
            }
        }
    }

    /**
     * 精品课程分类
     * 
     */
    public function goodCourseCategory()
    {   
        // 三级分类
        $goodCourseCategory = $this->data_list("good_course_category", "where id>0 ", "order by id desc");
        $result = $this->cation($goodCourseCategory);
        // 素材的格式
        $param = array(
                "goodCourseCategory" => $result,
            );
        $this->ajaxReturn(200, '精品课程分类获取成功', $param);
    }
    
    // pc端精品分类接口
    public function goodCourseCategoryPC()
    {   
        // 三级分类
        $goodCourseCategory = $this->data_list("good_course_category", "where id>0 ", "order by id desc");
        $result = $this->categorys($goodCourseCategory);
        // 素材的格式
        $param = array(
                "goodCourseCategory" => $result,
            );
        $this->ajaxReturn(200, '精品课程分类获取成功', $param);
    }

    /**
     * 精品课程的列表
     * page 页数
     * category_one_id  一级分类id
     * category_two_id  二级分类id
     * category_three_id  三级分类id
     * orderTime  时间排序 1 时间倒序 2 时间正序
     * orderNum   购买数量排序  1数量倒序  2 时间正序
     */
    public function goodCourseList()
    {
        $siteurl = $this->siteurl;
        $this->checkLogin();
        $input = $this->post;

        $page = intval($input['page']) > 0 ? intval($input['page']) : 1;
        $pageSize = 10;

        $where = "where id > 0 and pid =0";
        if (!empty($input['category_one_id'])) {
            $where .= " and category_one_id = '" . $input['category_one_id'] . "'";
        }

        if (!empty($input['category_two_id'])) {
            $where .= " and category_two_id = '" . $input['category_two_id'] . "'";
        }

        if (!empty($input['category_three_id'])) {
            $where .= " and category_three_id = '" . $input['category_three_id'] . "'";
        }

        // 热门推荐位
        $hotList = $this->data_list("good_course", " where id > 0 and pid=0 and position like '%" . ',2,' . "%'", " order by id desc", " limit 6");
        $hotCourseList = array();
        if (!empty($hotList)) {
            foreach ($hotList as $k => $val) {
                $hotCourseList[] = array(
                    "id" => (int)$val['id'],
                    "title" => $val['title'],
                    "updated_at" => date("Y-m-d H:i", $val['updated_at']),
                    "buynum" => (int)$val['buynum'],
                );
            }
        }

        $order = $this->courseSort($input['orderTime'], $input['orderNum']);
        // echo $order;die;
        $param = array(
            "goodCourseList" => array(),
            "hotCourseList" => $hotCourseList, 
        );
        $count = $this->data_count("good_course", $where);

        if (empty($count)) {
            $this->ajaxReturn(200, "暂时没有数据", $param);
        }

        $pagenum = ceil($count / $pageSize);
        if ($pagenum < $page) {
            $this->ajaxReturn(200, "数据加载完成", $param);
        }

        $limit = " limit " . ($page - 1) * $pageSize . "," . $pageSize;
        $list = $this->data_list("good_course", $where, $order, $limit);
        $goodCourseList = array();
        if (!empty($list)) {
            foreach ($list as $k => $val) {
                $goodCourseList[] = array(
                    "id" => (int)$val['id'],
                    "title" => $val['title'],
                    "image" => formatAppImageUrl($val['image'], $siteurl),
                    "created_at" => date("Y-m-d H:i", $val['created_at']),
                    "updated_at" => date("Y-m-d H:i", $val['updated_at']),
                    "price" => sprintf("%01.2f", ($val['price'] / 100)),
                    "buynum" => (int)$val['buynum'],
                );
            }
        }
        
        $param = array(
            "goodCourseList" => $goodCourseList,
            "pagenum" => $pagenum,
            "hotCourseList" => $hotCourseList,
        );

        $this->ajaxReturn(200, "精品课程列表获取成功", $param);
    }

    /**
     * 精品课程的详情页面
     * id 课程的id
     */
    public function goodCourseInfo()
    {
        $siteurl = $this->siteurl;
        $input = $this->post;
        $this->checkLogin();
        if (empty($input['id'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        $info = $this->data_getinfo("good_course", " id = '" . $input['id'] . "'");

        $goodCourseInfo = array();
        if (empty($info)) {
            $this->ajaxReturn(202, "此课程信息不存在或者已经下架");
        }

        // 查看是否购买此视频 已经支付并且课程id
        $sql = " status = 2 and good_course_id = '" . $input['id'] . "' and user_id = '" . $_SESSION['user_id'] . "'";
        $orderInfo = $this->data_getinfo("order", $sql);
        $orderStatus = 1;//未购买
        if (!empty($orderInfo)) {
            $orderStatus = 2;//已经购买
        }
        $goodCourseInfo = array(
            "id" => (int)$info['id'],
            "title" => $info['title'],
            "image" => formatAppImageUrl($info['image'], $siteurl),
            "created_at" => date("Y-m-d H:i", $info['created_at']),
            "price" => sprintf("%01.2f", ($info['price'] / 100)),
            "buynum" => (int)$info['buynum'],
            "description" => getImgThumbUrl($info['description'], $siteurl),
            "orderStatus" => $orderStatus,
        );


        $param = array(
            "goodCourseInfo" => $goodCourseInfo,
        ); 
        $this->ajaxReturn(200, "获取信息成功", $param);
    }

    /**
     * 精品课程里面
     * 购买的精品课程列表
     * page 页数 
     */
    public function myGoodCourse()
    {
        $siteurl = $this->siteurl;
        $this->checkLogin();
        $input = $this->post;

        $page = intval($input['page']) > 0 ? intval($input['page']) : 1;
        $pageSize = intval($input['pageSize']) > 0 ? intval($input['pageSize']) : 10;

        $where = " where  id > 0 and status = 2 and user_id = '" . $_SESSION['user_id'] . "'";

        $param = array(
            "myGoodCourse" => array(),
        );

        $count = $this->data_count("order", $where);
        if (empty($count)) {
            $this->ajaxReturn(200, "暂时没有数据", $param);
        }

        $pagenum = ceil($count / $pageSize);
        if ($pagenum < $page) {
            $this->ajaxReturn(200, "数据加载完成", $param);
        }

        $limit = " limit " . ($page - 1) * $pageSize . "," . $pageSize;
        $list = $this->data_list("order", $where, $limit);

        $myGoodCourse = array();
        foreach ($list as $k => $val) {
            $courseInfo = $this->data_getinfo("good_course", " id = '" . $val['good_course_id'] . "'");
            $myGoodCourse[] = array(
                "id" => (int)$val['id'],
                "good_course_id" => (int)$val['good_course_id'],
                "title" => $val['title'],
                "image" => formatAppImageUrl($courseInfo['image'], $siteurl),
                "created_at" => date("Y-m-d H:i", $val['created_at']),
                "type" => (int)$courseInfo['type'],
            );
        }

        $param = array(
            "myGoodCourse" => $myGoodCourse,
            "pagenum" => $pagenum,
        );

        $this->ajaxReturn(200, "信息获取成功", $param);
    }

    /**
     * 列表的id
     * page 当前页数
     * course_id 素材的id
     */
    public function packageList()
    {
        $input = $this->post;
        $siteurl = 'http://' . $this->config['siteurl'];
        $this->checkLogin();
        if (!$input['course_id']) {
            $this->ajaxReturn(202, '参数错误 请刷新重试！');
        }
        $order = ' order by id desc';
        $sql = " status = 2 and good_course_id = '" . $input['course_id'] . "' and user_id = '" . $_SESSION['user_id'] . "'";
        $orderInfo = $this->data_getinfo("order", $sql);
        if (empty($orderInfo)) {
            $this->ajaxReturn(202, '请先购买此套餐课程！');
        }

        $page = intval($input['page']) > 0 ? intval($input['page']) : 1;
        $pageSize = intval($input['pageSize']) > 0 ? intval($input['pageSize']) : 18;

        $where = ' where id > 0 and pid ="' . $input['course_id'] . '"';

        $count = $this->data_count("good_course", $where);
        if (empty($count)) {
            $this->ajaxReturn(200, "暂时没有数据");
        }

        $pagenum = ceil($count / $pageSize);
        if ($pagenum < $page) {
            $this->ajaxReturn(200, "数据加载完成");
        }

        $limit = " limit " . ($page - 1) * $pageSize . "," . $pageSize;
        $list = $this->data_list("good_course", $where, $order, $limit);

        $goodCourseList = array();
        if (!empty($list)) {
            foreach ($list as $k => $val) {
                $goodCourseList[] = array(
                    "id" => (int)$val['id'],
                    "title" => $val['title'],
                    "image" => formatAppImageUrl($val['image'], $siteurl),
                    "created_at" => date("Y-m-d H:i", $val['created_at']),
                    "updated_at" => date("Y-m-d H:i", $val['updated_at']),
                    'type' => $val['type'],
                    "buynum" => (int)$val['buynum'],
                );
            }
        }

        $param = array(
            "goodCourseList" => $goodCourseList,
            "pagenum" => $pagenum,
        );
        $this->ajaxReturn(200, "精品课程列表获取成功", $param);
    }

    /**
     * 进入精品课程的详情页面  可以执行听写等操作的
     * source_id 主表的id 
     */
    public function myGoodCourseInfo()
    {
        $input = $this->post;
        $this->checkLogin();
        $siteurl = $this->siteurl;
        if (empty($input['source_id'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        $info = $this->data_getinfo("good_course", " id = '" . $input['source_id'] . "'");
        if (empty($info)) {
            $this->ajaxReturn(202, "精品课程不存在或者或者已经下架");
        }

        // 查看是否购买此课程
        $sql = " user_id = '" . $_SESSION['user_id'] . "' and good_course_id = '" . $info['pid'] . "' and status = 2";
        $orderInfo = $this->data_getinfo("order", $sql);
        if (empty($orderInfo)) {
            $this->ajaxReturn(202, "没有购买此课程 暂无权限查看");
        }
        // 合成的音频
        $merge_audio = $this->mergeAudio($input['source_id'], $_SESSION['user_id'], 3);

        // 查看课程信息
        $info['id'] = (int)$info['id'];
        $info["merge_audio"] = empty($merge_audio) ? "" : $this->config['qiniu'] . $merge_audio;
        $info['image'] = formatAppImageUrl($info['image'], $siteurl);
        $info['updated_at'] = date("Y-m-d H:i", $info['updated_at']);
        $info['type'] = (int)$info['type'];
        $info['created_at'] = date("Y-m-d H:i:s", $info['created_at']);
        $info['description'] = getImgThumbUrl($info['description'], $siteurl);
        $info['price'] = sprintf("%01.2f", ($info['price'] / 100));
        $info['buynum'] = (int)$info['buynum'];

        // 获取文章的标签
        $dictation_tag = $this->getTagInfo($_SESSION['user_id'], $input['source_id'], 3);
        $tagName = $this->getTagName($dictation_tag, $_SESSION['user_id']);
        $info['dictation_tag'] = $tagName;
        // 音视频素材
        $avInfo = $this->data_list("good_course_info", "where id>0 and source_id = '" . $info['id'] . "'");
        $av = array();
        if (!empty($avInfo)) {
            foreach ($avInfo as $k => $val) {
                // 听写记录 
                $sql = "source_id = '" . $input['source_id'] . "' and source_period_id = '" . $val['id'] . "' and type = 4 and user_id = '" . $_SESSION['user_id'] . "'";
                $dictationInfo = $this->data_getinfo("source_dictation", $sql);

                // 听写内容在翻译
                $translation = $this->dictationTranslation($input['source_id'], $val['id'], $_SESSION['user_id'], 3);
                if (! empty($dictationInfo)){
                    $dictationInfo['content'] = htmlspecialchars_decode($dictationInfo['content']);
                    $dictationInfo['translation'] = empty($translation) ? (object)array() : $translation;
                }
                $dictationInfo['dictation_tag'] = empty($dictation_tag) ? "" : substr($dictation_tag, 1, strlen($dictation_tag) - 2);
                $dictationInfo['dictationRead'] = empty($dictationRead) ? (object)array() : $dictationRead;
                // 字幕记录 用于判断是修改  还是新上传
                $subtitles = $this->data_getinfo("source_subtitles", $sql);
                $content = json_decode($subtitles['content'], true);
                $subtitlesList = array();
                if (!empty($content)) {
                    foreach ($content as $k => $value) {
                        $subtitlesList[] = $value;
                    }
                }
                
                // 获取我的听写的朗读记录
                $dictationRead = $this->dictationReadLog($input['source_id'], $val['id'], 3, $_SESSION['user_id']);
                $av[] = array(
                    "id" => $val['id'],
                    "path" => $val['path'],
                    "subtitle" => $val['subtitle'],
                    "dictationInfo" => empty($dictationInfo) ? (object)array() : $dictationInfo,
                    "subtitlesList" => empty($subtitlesList) ? array() : $subtitlesList,
                    // "dictationRead" => empty($dictationRead) ? (object)array() : $dictationRead,
                );
            }
        }
        // 文本素材
        $textInfo = $this->data_list("good_course_text", "where id>0 and source_id = '" . $info['id'] . "'");
        $text = array();
        if (!empty($textInfo)) {
            foreach ($textInfo as $k => $val) {
                // 朗读记录  翻译记录
                $sql = "source_id = '" . $input['source_id'] . "' and source_period_id = '" . $val['id'] . "' and type= 4 and user_id = '" . $_SESSION['user_id'] . "'";
                $translation = $this->data_getinfo("source_translation", $sql);
                $readInfo = $this->data_getinfo("source_read", $sql);
                $text[] = array(
                    "id" => $val['id'],
                    "source_id" => $val['source_id'],
                    "content" => $val["content"],
                    "translation" => empty($translation) ? (object)array() : $translation,
                    "read" => empty($readInfo['path']) ? "" : $this->config['qiniu'] . $readInfo['path'],
                );        
            }
        }

        // 浏览次数  浏览次数+1  1、听写次数  2、制作弹幕次数  3、翻译 
        $this->data_self_add("good_course", "view_count", 1, "where id>0 and id = '" . $info['id'] . "'");
        $countList = array();
        if ($info['type'] != 3) {
            $info['notice'] = getImgThumbUrl($info['notice'], $siteurl);
            $info['words'] = getImgThumbUrl($info['words'], $siteurl);
            $info['answer'] = getImgThumbUrl($info['answer'], $siteurl); 
            $where = " where id > 0 and source_id = '" . $info['id'] . "' and source_type = 3 and do_type = 1";
            $countList['dictationCount'] = (int)$this->data_count("source_log", $where);
            $where = " where id > 0 and source_id = '" . $info['id'] . "' and source_type = 3 and do_type = 4";
            $countList['subtitlesCount'] = (int)$this->data_count("source_log", $where);
        }

        if ($info['type'] == 3 || $info['type'] == 4 || $info['type'] == 5) {
            $where = " where id > 0 and source_id = '" . $info['id'] . "' and source_type = 3 and do_type = 3 ";
            $countList['translationCount'] = (int)$this->data_count("source_log", $where);
        }


        // 是否展示标准答案 1代表不展示   2代表展示
        $dictationInfo = $this->data_getinfo("source_dictation", "source_id = '" . $info['id'] . "'");
        $is_show = empty($dictationInfo) ? 1 : 2;
        $param = array(
            "info" => $info,
            "avInfo" => $av,
            "textInfo" => $text,
            "isShow" => $is_show,
            "countList" => $countList,
        );
        $this->ajaxReturn(200, "详情信息获取成功", $param);

    }

    /**
     * 精品素材的描述页面
     */

    /**
     * 针对于已经购买的精品课程进行的操作
     * 课程分类:音频  视频  文本 音频文本  视频文本
     * 音频 视频 : 听写  制作弹幕
     * 文本: 翻译 朗读  添加生词
     */
    
    /**
     * 进入听写详情页面
     * source_id 素材主表的id
     * source_period_id 素材性情表的id
     */
    public function myCourseDictationInfo()
    {
        $this->checkLogin();
        $siteurl = $this->siteurl;
        $input = $this->post;
        if (empty($input['source_id']) || empty($input['source_period_id'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        $info = model('u')->data_getinfo('good_course', ' id =' . $input['source_id']);
        // 查看是否购买此课程
        $sql = " user_id = '" . $_SESSION['user_id'] . "' and good_course_id = '" . $info['pid'] . "' and status = 2";
        $orderInfo = $this->data_getinfo("order", $sql);
        if (empty($orderInfo)) {
            $this->ajaxReturn(202, "没有购买此课程 暂无任何权限");
        }

        // 获取文章的标签 一篇文章  一个标签
        $dictation_tag = $this->getTagInfo($_SESSION['user_id'], $input['source_id'], 3);

        // 精品课程的主表的id
        $sql = " id = '" . $input['source_id'] . "'";
        $source = $this->data_getinfo("good_course", $sql);
        $sourceInfo = array(
            "id" => (int)$source['id'],
            "type" => (int)$source['type'],
        );

        // 
        $sql = " id= '" . $input['source_period_id'] . "' and source_id = '" . $input['source_id'] . "' ";
        $avInfo = $this->data_getinfo("good_course_info", $sql);

        // 
        $sql = " source_id='" . $input['source_id'] . "' and source_period_id='" . $input['source_period_id'] . "' and user_id='" . $_SESSION['user_id'] . "' and type = 4";
        $dictation = $this->data_getinfo("source_dictation", $sql);
        $dictation_log = $this->data_getinfo("dictation_log", $sql);

        $dictation_t = empty($dictation) ? array() : $dictation;
        if (!empty($dictation_t)) {
            $dictation_t['content'] = empty($dictation_t['content']) ? "" : htmlspecialchars_decode($dictation_t['content']);
        }

        $dictation_log_t = empty($dictation_log) ? array() : $dictation_log;
        if (!empty($dictation_log_t)) {
            $dictation_log_t['content'] = empty($dictation_log_t['content']) ? "" : htmlspecialchars_decode($dictation_log_t['content']);
        }

        $info = empty($dictation_log) ? $dictation_t : $dictation_log_t;
        $info['dictation_tag'] = empty($dictation_tag) ? "" : substr($dictation_tag, 1, strlen($dictation_tag) - 2);

        $param = array(
            "sourceInfo" => $sourceInfo,
            "avInfo" => $avInfo,
            "dictationInfo" => empty($info) ? (object)array() : $info,
        );
        $this->ajaxReturn(200, "信息获取成功", $param);

    }
    
    /**
     * 音视频的听写
     * id 音视频的id (附表的id  即详情信息的id)
     * content 听写的内容
     * time 听写时间
     * dictation_tag 选择标签
     */
    public function myCourseDictation()
    {
        $this->checkLogin();
        $this->checkUser();

        $input = $this->post;
        if (empty($input['id'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        $sourceInfo = $this->data_getinfo("good_course_info", "id='" . $input['id'] . "'");
        if (empty($sourceInfo)) {
            $this->ajaxReturn(202, "视频已删除或者下架");
        }

        // 查看是否购买此课程
        $info = model('u')->data_getinfo('good_course', ' id =' . $sourceInfo['source_id']);
        $sql = " user_id = '" . $_SESSION['user_id'] . "' and good_course_id = '" . $info['pid'] . "' and status = 2";
        $orderInfo = $this->data_getinfo("order", $sql);
        if (empty($orderInfo)) {
            $this->ajaxReturn(202, "没有购买此课程 暂无权限查看");
        }

        if (empty($input['content'])) {
            $this->ajaxReturn(202, "听写内容不能为空");
        }
        $where = " source_id='" . $sourceInfo['source_id'] . "' and source_period_id='" . $input['id'] . "' and type= 4 and user_id='" . $_SESSION['user_id'] . "'";
        $dictationInfo = $this->data_getinfo("source_dictation", $where);
        
        // 查看标签
        $dictation_tag = "";
        if (!empty($input['dictation_tag'])) {
            $dictation_tag = "," . $input['dictation_tag'] . ",";
        }

        // type 4精品课程
        $data = array(
            "source_id" => $sourceInfo['source_id'],
            "source_period_id" => $input['id'],
            "content" => $input['content'],
            "created_at" => time(),
            "time" => $input['time'],
            "user_id" => $_SESSION['user_id'],
            "type" => 4,
            "dictation_tag" => $dictation_tag,
        );

        // 更新标签  一篇文章一个标签
        $this->updateTag($_SESSION['user_id'], $sourceInfo['source_id'], 3, $dictation_tag);
        // 加入听写操作表
        $this->sourceLog($sourceInfo['source_id'], $_SESSION['user_id'], 3, 1, $dictation_tag);
        // 删除dictation_log表中的数据 （每隔10s更新一次de表）;
        $this->data_del("dictation_log", $where);

        // 更新user表中的学习天数和最近学习的时间
        $this->doDays($_SESSION['user_id']);

        if (empty($dictationInfo)) {
            $dictationInfo = $this->data_add("source_dictation", $data);
        } else {
            $dictationInfo = $this->data_edit("source_dictation", $data, "id='" . $dictationInfo['id'] . "'");
        }

        $this->ajaxReturn(200, "听写保存成功");
    }

    /**
     * 每个十秒自动保存一次听写内容 （自动保存功能）
     * source_period_id 音视频的id (附表的id)
     * content 听写的内容
     * time 听写时间
     * dictation_tag  1,2,3
     */
    public function dictationLog()
    {
        $input = $this->post;
        $this->checkLogin();
        $this->checkUser();

        $sourceInfo = $this->data_getinfo("good_course_info", "id='" . $input['source_period_id'] . "'");
        if (empty($sourceInfo)) {
            $this->ajaxReturn(202, "视频已删除或者下架");
        }

        // 查看是否购买此课程
        $info = model('u')->data_getinfo('good_course', 'id = ' . $sourceInfo['source_id']);
        $sql = " user_id = '" . $_SESSION['user_id'] . "' and good_course_id = '" . $info['pid'] . "' and status = 2";
        $orderInfo = $this->data_getinfo("order", $sql);
        if (empty($orderInfo)) {
            $this->ajaxReturn(202, "没有购买此课程 暂无权限查看");
        }

        // 听写的标签
        $dictation_tag = "";
        if (!empty($input['dictation_tag'])) {
            $dictation_tag = "," . $input['dictation_tag'] . ",";
        }

        $where = " source_id='" . $sourceInfo['source_id'] . "' and source_period_id= '" . $input['source_period_id'] . "' and type= 4 and user_id='" . $_SESSION['user_id'] . "'";
        $dictationInfo = $this->data_getinfo("dictation_log", $where);

        $data = array(
            "source_id" => $sourceInfo['source_id'],
            "source_period_id" => $input['source_period_id'],
            "content" => $input['content'],
            "created_at" => time(),
            "time" => $input['time'],
            "user_id" => $_SESSION['user_id'],
            "type" => 4,
            "dictation_tag" => $dictation_tag,
            "pid" => 0,
        );

        if (empty($dictationInfo)) {
            $dictationInfo = $this->data_add("dictation_log", $data);
        } else {
            $dictationInfo = $this->data_edit("dictation_log", $data, "id='" . $dictationInfo['id'] . "'");
        }
        $this->ajaxReturn(200, "听写信息保存成功");
        
    }

    /**
     * 进入制作字幕的页面
     * source_period_id 资源的id
     */
    public function myCourseSubtitlesInfo()
    {
        $this->checkLogin();
        $input = $this->post;
        if (empty($input['source_period_id'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }
        $sourceInfo = $this->data_getinfo("good_course_info", "id = '" . $input['source_period_id'] . "'");
        if (empty($sourceInfo)) {
            $this->ajaxReturn(202, "视频已删除或者下架");
        }

        // 查看是否购买此课程
        $info = model('u')->data_getinfo('good_course', 'id = ' . $sourceInfo['source_id']);
        $sql = " user_id = '" . $_SESSION['user_id'] . "' and good_course_id = '" . $info['pid'] . "' and status = 2";
        $orderInfo = $this->data_getinfo("order", $sql);
        if (empty($orderInfo)) {
            $this->ajaxReturn(202, "没有购买此课程 暂无权限查看");
        }


        $sql = " source_id = '" . $sourceInfo['source_id'] . "' and source_period_id = '" . $input['source_period_id'] . "' and user_id = '" . $_SESSION['user_id'] . "' and type = 4";
        $subtitles = $this->data_getinfo("source_subtitles", $sql);

        $content = json_decode($subtitles['content'], true);
        $subtitlesList = array();
        if (!empty($content)) {
            foreach ($content as $k => $val) {
                $subtitlesList[] = $val;
            }
        }

        $param = array(
            "subtitlesList" => empty($subtitlesList) ? array() : $subtitlesList,
            "sourceInfo" => $sourceInfo,
        );
        $this->ajaxReturn(200, "字幕信息获取成功", $param);
    }

    /**
     * 音视频制作弹幕
     * source_period_id 附表的id
     * content 弹幕的内容
     */
    public function myCourseSubtitles()
    {
        $this->checkLogin();
        $this->checkUser();
        $input = $this->post;
        if (empty($input['source_period_id'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        $sourceInfo = $this->data_getinfo("good_course_info", "id = '" . $input['source_period_id'] . "'");
        if (empty($sourceInfo)) {
            $this->ajaxReturn(202, "视频已删除或者下架");
        } 

        // 查看是否购买此课程
        $info = model('u')->data_getinfo('good_course', 'id = ' . $sourceInfo['source_id']);
        $sql = " user_id = '" . $_SESSION['user_id'] . "' and good_course_id = '" . $info['pid'] . "' and status = 2";
        $orderInfo = $this->data_getinfo("order", $sql);
        if (empty($orderInfo)) {
            $this->ajaxReturn(202, "没有购买此课程 暂无权限查看");
        }

        if (empty($input['content'])) {
            $this->ajaxReturn(202, "弹幕的内容不能为空");
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

        $sql = "source_id = '" . $sourceInfo['source_id'] . "' and source_period_id = '" . $input['source_period_id'] . "' and user_id = '" . $_SESSION['user_id'] . "' and type = 4 ";
        $info = $this->data_getinfo("source_subtitles", $sql);

        $data = array(
            "source_id" => $sourceInfo['source_id'],
            "source_period_id" => $input['source_period_id'],
            "content" => addslashes(json_encode($subtitles)),
            "created_at" => time(),
            "user_id" => $_SESSION['user_id'],
            "type" => 4,
        );

        // 更新user表中的学习天数和最近学习的时间
        $this->doDays($_SESSION['user_id']);

        // 加入朗读 听写操作表中  方便个人中心的列表展示
        $this->sourceLog($sourceInfo['source_id'], $_SESSION['user_id'], 3, 4);
        if (empty($info)) {
            $addSubtitles = $this->data_add("source_subtitles", $data);
        } else {
            $editSubtitles = $this->data_edit("source_subtitles", $data, " id = '" . $info['id'] . "'");
        }

        $this->ajaxReturn(200, "字幕制作成功");
    }



    /**
     * 文本的操作
     * 朗读  翻译  添加生词  
     */
    
    /**
     * 文本素材朗读页面 初次进入
     * source_period_id 素材文本附表的id
     * 
     */
    public function myCourseReadInfo()
    {
        $input = $this->post;
        if (empty($input['source_period_id'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        $info = $this->data_getinfo("good_course_text", "id='" . $input['source_period_id'] . "'");
        if (empty($info)) {
            $this->ajaxReturn(202, "资源不存在或者已经下架");
        }
        $info['created_at'] = date("Y-m-d H:i", $info['created_at']);

        $sql = "source_id='" . $info['source_id'] . "' and source_period_id='" . $info['id'] . "' and user_id='" . $_SESSION['user_id'] . "' and type=4";

        $readInfo = $this->data_getinfo("source_read", $sql);
        if (!empty($readInfo)) {
            $readInfo['path'] = $this->config['qiniu'] . $readInfo['path'];
        }
        $param = array(
            "info" => $info,
            "readInfo" => empty($readInfo) ? (object)array() : $readInfo,
        );
        $this->ajaxReturn(200, "信息获取成功", $param);
    }


    /**
     * 文本的朗读
     * source_period_id
     * path
     */
    public function myCourseRead()
    {
        $this->checkLogin();
        $this->checkUser();
        $input = $this->post;
        if (empty($input['source_period_id'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        if (empty($input['path'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        $textInfo = $this->data_getinfo("good_course_text", "id = '" . $input['source_period_id'] . "' ");

        // 查看是否购买此课程
        $info = model('u')->data_getinfo('good_course', 'id = ' . $textInfo['source_id']);
        $sql = " user_id = '" . $_SESSION['user_id'] . "' and good_course_id = '" . $info['pid'] . "' and status = 2";
        $orderInfo = $this->data_getinfo("order", $sql);
        if (empty($orderInfo)) {
            $this->ajaxReturn(202, "没有购买此课程 暂无权限查看");
        }

        $data = array(
            "source_id" => $textInfo['source_id'],
            "source_period_id" => $input['source_period_id'],
            "path" => $input['path'],
            "created_at" => time(),
            "user_id" => $_SESSION['user_id'],
            "type" => 4,
        );

        $sql = "source_id='" . $textInfo['source_id'] . "' and source_period_id='" . $input['source_period_id'] . "' and user_id='" . $_SESSION['user_id'] . "' and type = 4";
        $readInfo = $this->data_getinfo("source_read", $sql);

        // 加入朗读 听写操作表中  方便个人中心的列表展示
        $this->sourceLog($textInfo['source_id'], $_SESSION['user_id'], 3, 2);
        // 更新user表中的学习天数和最近学习的时间
        $this->doDays($_SESSION['user_id']);

        if (empty($readInfo)) {
            $addRead = $this->data_add("source_read", $data);
        } else {
            $editRead = $this->data_edit("source_read", $data, "id='" . $readInfo['id'] . "'");
        }

        $this->ajaxReturn(200, "朗读保存成功");
    }

    /**
     * 进入翻译操作
     * source_period_id  附表的id
     */
    public function myCourseTranslationInfo()
    {
        $this->checkLogin();
        $input = $this->post;
        if (empty($input['source_period_id'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        $info = $this->data_getinfo("good_course_text", "id='" . $input['source_period_id'] . "'");
        $sql = "source_id='" . $info['source_id'] . "' and source_period_id='" . $info['id'] . "' and user_id='" . $_SESSION['user_id'] . "' and type = 4";
        $translationInfo = $this->data_getinfo("source_translation", $sql);
        $param = array(
            "info" => $info,
            "translationInfo" => empty($translationInfo) ? (object)array() : $translationInfo,
        );
        $this->ajaxReturn(200, "信息获取成功", $param);
    }

    /**
     * 文本的翻译 
     * source_period_id 附表的id
     * content 翻译内容
     * grammar 语法
     * words 单词
     */
    public function myCourseTranslation()
    {
        $this->checkLogin();
        $this->checkUser();
        $input = $this->post;

        $info = $this->data_getinfo("good_course_text", "id = '" . $input['source_period_id'] . "'");

        $goodCourseInfo = model('u')->data_getinfo('good_course', ' id =' . $info['source_id']);
        $sql = " user_id = '" . $_SESSION['user_id'] . "' and good_course_id = '" . $goodCourseInfo['pid'] . "' and status = 2";
        $orderInfo = $this->data_getinfo("order", $sql);
        if (empty($orderInfo)) {
            $this->ajaxReturn(202, "没有购买此课程 暂无权限查看");
        }

        if (empty($input['source_period_id'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        if (empty($input['content'])) {
            $this->ajaxReturn(202, "翻译内容不能为空");
        }

//        if (empty($input['grammar'])) {
//            $this->ajaxReturn(202, "语法不能为空");
//        }

        if (empty($input['words'])) {
            $this->ajaxReturn(202, "单词不能为空");
        }

        $data = array(
            "source_id" => $info['source_id'],
            "source_period_id" => $input['source_period_id'],
            "content" => $input['content'],
            "grammar" => $input['grammar'],
            "words" => $input['words'],
            "created_at" => time(),
            "user_id" => $_SESSION['user_id'],
            "type" => 4,
        );

        $sql = "source_id='" . $info['source_id'] . "' and source_period_id='" . $info['id'] . "' and user_id='" . $_SESSION['user_id'] . "' and type = 4";
        $translationInfo = $this->data_getinfo("source_translation", $sql);

        // 加入朗读 听写操作表中  方便个人中心的列表展示
        $this->sourceLog($info['source_id'], $_SESSION['user_id'], 3, 3);
        // 更新user表中的学习天数和最近学习的时间
        $this->doDays($_SESSION['user_id']);

        if (empty($translationInfo)) {
            $addTranslation = $this->data_add("source_translation", $data);
        } else {
            $editTranslation = $this->data_edit("source_translation", $data, " id = '" . $translationInfo['id'] . "'");
        }
        $this->ajaxReturn(200, "翻译保存成功");
    }

    /**
     * 其他人翻译列表
     * source_period_id 附表的id
     * page 页数
     */
    public function myCourseOtherTranslation()
    {
        $this->checkLogin();
        $siteurl = $this->siteurl;
        $input = $this->post;
        if (empty($input['source_period_id'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }        
        $page = intval($input['page']) > 0 ? intval($input['page']) : 1;
        $pageSize = 8;
        $info = $this->data_getinfo("good_course_text", " id='" . $input['source_period_id'] . "'");
        $where = "WHERE id > 0 and source_id = '" . $info['source_id'] . "' AND source_period_id = '" . $info['id'] . "' AND type = 4 and user_id !='" . $_SESSION['user_id'] . "'";
        $order = " order by id desc";
        $count = $this->data_count("source_translation", $where);
        
        $param = array(
            "otherTranslation" => array(),
        );

        if (empty($count)) {
            $this->ajaxReturn(200, "暂时没有数据", $param);
        }

        $pagenum = ceil($count / $pageSize);
        if ($pagenum < $page) {
            $this->ajaxReturn(200, "数据已经加载完成", $param);
        }

        $limit = " LIMIT " . ($page-1) * $pageSize . "," . $pageSize;
        $list = $this->data_list("source_translation", $where, $order, $limit);
        $otherTranslation = array(); 
        if (!empty($list)) {
            foreach ($list as $k => $val) {
                $userinfo = $this->data_getinfo("user", "id = '" . $val['user_id'] . "'");
                $praise = $this->data_count("good_course_praise", "where translation_id='" . $val['id'] . "'");
                $sql = "translation_id='" . $val['id'] . "' and user_id='" . $_SESSION['user_id'] . "'";
                $praiseInfo = $this->data_getinfo("good_course_praise", $sql);
                $is_praise = empty($praiseInfo) ? 1 : 2; //1未赞  2已赞
                $otherTranslation[] = array(
                    "id" => $val['id'],
                    "avatar" => formatAppImageUrl($userinfo['avatar'], $siteurl),
                    "nickname" => $userinfo['nickname'],
                    "created_at" => $this->formatTime($val['created_at']),
                    "user_id" => $val['user_id'],
                    "content" => $val['content'],
                    "grammar" => $val['grammar'],
                    "words" => $val['words'],
                    "praiseNum" => intval($praise) > 0 ? intval($praise) : 0,
                    "is_praise" => $is_praise,
                );
            }
        }
        $param = array(
            "otherTranslation" => $otherTranslation,
        );
        $this->ajaxReturn(200, "信息获取成功", $param);
    }


    /**
     * 其他人翻译点赞的
     * id 翻译列表的id
     */
    public function myCourseOtherTranslationPraise()
    {
        $this->checkLogin();
        $this->checkUser();
        $input = $this->post;
        if (empty($input['id'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        $sql = "translation_id = '" . $input['id'] . "' and user_id = '" . $_SESSION['user_id'] . "'";
        $info = $this->data_getinfo("good_course_praise", $sql);
        if (!empty($info)) {
            $delPraise = $this->data_del("good_course_praise", " id='" . $info['id'] . "'");
            $this->ajaxReturn(200, "取消赞成功");
        } else {
            $data = array(
                "translation_id" => $input['id'],
                "created_at" => time(),
                "user_id" => $_SESSION['user_id'],
            );
            $addPraise = $this->data_add("good_course_praise", $data);
            $this->ajaxReturn(200, "点赞成功");
        }

    }


    /**
     * 生词列表
     * source_id 资源主表的id
     * page 页数
     */
    public function myCourseSourceWordList()
    {
        $this->checkLogin();
        $input = $this->post;
        if (empty($input['source_id'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        $where = "WHERE source_id='" . $input['source_id'] . "' AND user_id='" . $_SESSION['user_id'] . "' AND type = 4";
        $order = "ORDER BY id DESC";
        $page = intval($input['page']) > 0 ? intval($input['page']) : 1;
        $pageSize = 20;

        $param = array("wordsList" => array());

        $count = $this->data_count("source_words", $where);
        if (empty($count)) {
            $this->ajaxReturn(200, "暂时没有数据", $param);
        }

        $pagenum = ceil($count / $pageSize);
        if ($pagenum < $page) {
            $this->ajaxReturn(200, "数据加载完成", $param);
        }

        $limit = " LIMIT " . ($page-1) * $pageSize . "," . $pageSize;
        $list = $this->data_list("source_words", $where, $order, $limit);
        $wordsList = array();
        if (!empty($list)) {
            foreach ($list as $k => $val) {
                $wordsList[] = array(
                    "id" => $val['id'],
                    "name" => $val['name'],
                    "paraphrase" => $val['paraphrase'],
                    "pronunciation_words" => $val['pronunciation_words'],
                );
            }
        }
        $param = array("wordsList" => $wordsList);
        $this->ajaxReturn(200, "生词列表获取成功", $param);
    }


    /**
     * 进入添加生词 分类列表 生词
     * words_id 生词表的id 
     * 
     */
    public function myCourseWordsInfo()
    {
        $input = $this->post;
        $this->checkLogin();
        $this->checkUser();

        $words_id = intval($input['words_id']) > 0 ? intval($input['words_id'])  : 0;
        $wordsInfo = $this->data_getinfo("source_words", "id='" . $words_id . "'");
        
        if (!empty($wordsInfo['pronunciation'])) {
            $wordsInfo['path'] = $this->config['qiniu'] . $wordsInfo['pronunciation'];
        }
        // 分类
        $where =  "where id > 0 and (user_id='" . $_SESSION['user_id'] . "' or pid = 0)";
        $data = $this->data_list("source_word_sort", $where);

        $wordsCategory = $this->categorys($data);
        $param = array(
            "wordsInfo" => $wordsInfo,
            "wordsCategory" => $wordsCategory,
        );

        $this->ajaxReturn(200, "信息获取成功", $param);
    }


    /**
     * 保存/编辑 生词
     * source_id 素材主表的id
     * words_id 生词表的id
     * name 生词名称
     * sort_id 分类
     * paraphrase 释义
     * pronunciation 读音
     * pronunciation_words 读音拼写
     * sentences 例句
     * associate 联想
     */
    public function myCourseSubWords()
    {
        $input = $this->post;
        $this->checkLogin();
        $this->checkUser();
        $res = $this->checkField($input);
        if ($res[0] == 202) {
            $this->ajaxReturn(202, $res[1]);
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
            "type" => 4,
            "source_id" => $input['source_id'],
        );

        // 更新user表中的学习天数和最近学习的时间
        $this->doDays($_SESSION['user_id']);
        
        if (empty($input['words_id'])) {
            $addWords = $this->data_add("source_words", $data);
            $this->ajaxReturn(200, "生词添加成功");

        } else {
            $editWords = $this->data_edit("source_words", $data, "id='" . $input['words_id'] . "'");
            $this->ajaxReturn(200, "生词编辑成功");

        }

    }


    // 推荐位
    public function positionList()
    {
        $list = $this->data_list("good_course", "where id > 0 and pid=0 and position like '%". ',2,' ."%'", "order by id desc", "limit 10");
        $positionList = array();
        if (!empty($list)) {
            foreach ($list as $k => $val) {
                $positionList[] = array(
                    "id" => (int)$val['id'],
                    "title" => $val['title'],
                    "updated_at" => date("Y-m-d H:i", $val['updated_at']),
                    "buynum" => (int)$val['buynum'],
                    "created_at" => date("Y-m-d H:i", $val['created_at']),
                );  
            }
        }
        $param = array(
            "positionList" => $positionList,
        );
        $this->ajaxReturn(200, "推荐位获取成功", $param);
    }

}