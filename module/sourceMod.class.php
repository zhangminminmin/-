<?php
/**
 * 平台上传的素材接口
 * @音视频素材的各种听写 制作弹幕操作
 * @文本素材的各种朗读 翻译 添加生词操作
 * 
 */
class sourceMod extends commonMod 
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
     * 音视频的分类以及素材格式
     */
    public function sourceCategory()
    {   
        // 三级分类
        $sourceCategory = $this->data_list("source_category", "where id>0 ", "order by id desc");
        $result = $this->cation($sourceCategory);
        // 素材的格式
        $typeList = $this->config['source'];
        $param = array(
                "sourceCategory" => $result,
                "typeList" => $typeList,
            );
        $this->ajaxReturn(200, '数据分类获取成功', $param);
    }

    /**
     * 音视频的素材分类格式
     * PC端专用
     */
    public function sourceCategoryPC()
    {   
        // 三级分类
        $sourceCategory = $this->data_list("source_category", "where id>0 ", "order by id desc");
        $result = $this->categorys($sourceCategory);
        // 素材的格式
        $typeList = $this->config['source'];
        $param = array(
                "sourceCategory" => $result,
                "typeList" => $typeList,
            );
        $this->ajaxReturn(200, '数据分类获取成功', $param);
    }

    /**
     * 平台素材分页(列表)
     * page 页数
     * category_one_id  分类的一级id
     * category_two_id  分类的二级id
     * category_three_id  分类的三级id
     * type 资源的格式
     * title 资源的名称
     * miniapp  1
     */
    public function sourceList()
    {   
        $siteurl = $this->siteurl;
        $this->checkLogin();
        $input = $this->post;
        $where = "WHERE id>0 ";
        $order = "ORDER BY created_at DESC";
        $page = intval($input['page']) >0 ? intval($input['page']) : 1;
        $pageSize = intval($input['pageSize']) >0 ? intval($input['pageSize']) : 8;

        if (!empty($input['category_one_id'])) {
            $where .= " AND category_one_id ='" . $input['category_one_id'] . "'";
        }

        if (!empty($input['category_two_id'])) {
            $where .= " AND category_two_id ='" . $input['category_two_id'] . "'";
        }

        if (!empty($input['category_three_id'])) {
            $where .= " AND category_three_id ='" . $input['category_three_id'] . "'";
        }
//        以下是小程序骗审的时候 开启的代码
//        if ($input['miniapp'] == 1) {
//            $where .= " AND type =3";
//        }else{
//            if (!empty($input['type'])) {
//                $where .= " AND type ='" . $input['type'] . "'";
//            }
//        }


        if (!empty($input['type'])) {
            $where .= " AND type ='" . $input['type'] . "'";
        }


        if (!empty($input['title'])) {
            $where .= ' AND title LIKE "%' . $input['title'] . '%"';
        }

        $param = array(
            "sourcelist" => array(),
        );

        $count = $this->data_count("source", $where);
        if (empty($count)) {
            $this->ajaxReturn(200, "暂时没有数据", $param);
        }
        $pagenum = ceil($count / $pageSize);
        if ($pagenum < $page) {
            $this->ajaxReturn(200, "数据已经加载完成", $param);
        }

        $limit = " LIMIT " . ($page-1) * $pageSize . "," . $pageSize;
        $list = $this->data_list("source", $where, $order, $limit);
        $sourcelist = array();
        if (!empty($list)) {
            foreach ($list as $k => $val) {
                $sourcelist[] = array(
                    "id" => $val['id'],
                    "title" => $val['title'],
                    "image" => formatAppImageUrl($val['image'], $siteurl),
                    "created_at" => date("Y-m-d", $val['created_at']),
                    "type" => $val['type'],
                );
            }
        }
        $param = array(
            "sourcelist" => $sourcelist,
            "pageNum" => $pagenum,
            'count' => $count,
        );
        $this->ajaxReturn(200, "数据获取成功", $param);
    }


    /**
     * 平台素材详情
     * id 素材的id 主表的id
     */
    public function sourceInfo()
    {
        $input = $this->post;
        $this->checkLogin();
        $siteurl = $this->siteurl;
        if (!$input['id']) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }
        // 个人信息
        $userinfo = $this->userinfo;
        // 素材的详情  素材
        $info = $this->data_getinfo("source", " id='" . $input['id'] . "'");
        if (empty($info)) {
            $this->ajaxReturn(202, "素材被下架或者已经删除" . $input['id']);
        }
        $merge_audio = $this->mergeAudio($input['id'], $_SESSION['user_id'], 1);

        $info['image'] = formatAppImageUrl($info['image'], $siteurl);
        $info['created_at'] = date("Y-m-d H:i:s", $info['created_at']);
        $info['description'] = getImgThumbUrl($info['description'], $siteurl);
        $info['merge_audio'] = empty($merge_audio) ? "" : $this->config['qiniu'] . $merge_audio;
        $info['view_count'] = (int)$info['view_count'];
        if ($info['type'] != 3) {
            $info['notice'] = getImgThumbUrl($info['notice'], $siteurl);
            $info['words'] = getImgThumbUrl($info['words'], $siteurl);
            $info['answer'] = getImgThumbUrl($info['answer'], $siteurl); 
        }
        if ($info['type'] == 4 || $info['type'] == 5) {

            $info['subtitles'] = formatAppImageUrl($info['subtitles'], $siteurl);
        }

        // 音视频素材
        $avInfo = $this->data_list("source_info", "where id>0 and source_id = '" . $info['id'] . "'");
        // 获取标签
        $dictation_tag = $this->getTagInfo($_SESSION['user_id'], $input['id'], 1);
        $tagName = $this->getTagName($dictation_tag, $_SESSION['user_id']);
        $info['dictation_tag'] = $tagName;
        // print_r($info);die;
        $av = array();
        if (!empty($avInfo)) {
            foreach ($avInfo as $k => $val) {
                // 听写记录 
                $sql = "source_id = '" . $input['id'] . "' and source_period_id = '" . $val['id'] . "' and type=1 and user_id = '" . $_SESSION['user_id'] . "'";
                $dictationInfo = $this->data_getinfo("source_dictation", $sql);
                if (! empty($dictationInfo)) {
                    // 查看听写内容的翻译
                    $translation = $this->dictationTranslation($input['id'], $val['id'], $_SESSION['user_id'], 1);
                    $dictationInfo['content'] = htmlspecialchars_decode($dictationInfo['content']);
                    $dictationInfo['translation'] = empty($translation) ? (object)array() : $translation;
                }
                $dictationInfo['dictation_tag'] = empty($dictation_tag) ? "" : substr($dictation_tag, 1, strlen($dictation_tag) - 2);
                $dictationInfo['dictationRead'] = empty($dictationRead) ? (object)array() : $dictationRead;
                // 字幕记录
                $subtitles = $this->data_getinfo("source_subtitles", $sql);

                $content = json_decode($subtitles['content'], true);
                $subtitlesList = array();
                if (!empty($content)) {
                    foreach ($content as $key => $value) {
                        $subtitlesList[] = $value;
                    }
                }
                $dictationRead = $this->dictationReadLog($input['id'], $val['id'], 1, $_SESSION['user_id']);
                $av[] = array(
                    "id" => $val['id'],
                    "path" => $val['path'],
                    "subtitle" => $val['subtitle'],
                    "dictationInfo" => empty($dictationInfo) ? (object)array() : $dictationInfo,
                    "subtitlesList" => $subtitlesList,
                    // "dictationRead" => empty($dictationRead) ? (object)array() : $dictationRead,
                );
            }
        }
        // 文本素材
        $textInfo = $this->data_list("source_text", "where id>0 and source_id = '" . $info['id'] . "'");
        $text = array();
        if (!empty($textInfo)) {
            foreach ($textInfo as $k => $val) {
                // 朗读记录  翻译记录
                $sql = "source_id = '" . $input['id'] . "' and source_period_id = '" . $val['id'] . "' and type=1 and user_id = '" . $_SESSION['user_id'] . "'";
                $translation = $this->data_getinfo("source_translation", $sql);
                $readInfo = $this->data_getinfo("source_read", $sql);
                $t = new StdClass();
                $text[] = array(
                    "id" => $val['id'],
                    "source_id" => $val['source_id'],
                    "content" => htmlspecialchars_decode($val['content']),
                    "translation" => empty($translation) ? $t : $translation,
                    "read" => empty($readInfo) ? "" : $this->config['qiniu'] . $readInfo['path'],
                );        
            }
        }
        // 是否展示标准答案 1代表不展示   2代表展示
        $dictationInfo = $this->data_getinfo("source_dictation", "source_id = '" . $info['id'] . "' and user_id=" . $_SESSION['user_id']);
        $is_show = empty($dictationInfo) ? 1 : 2;

        // 标准答案展示新逻辑
        $is_show = 2;
        if ($userinfo['type'] != 3) {
            if ($userinfo['type'] == 2) {
                if ($userinfo['endtime'] < time()) {
                    $is_show = 1;
                }
            } else {
                $is_show = 1;
            }
        }
        // 平台素材的推荐位（PC端）
        $list = $this->data_list("source", "where id > 0 and position like '%". ',2,' ."%'", "order by id desc", "limit 10");
        $positionList = array();
        if (!empty($list)) {
            foreach ($list as $k => $val) {
                $positionList[] = array(
                    "id" => $val['id'],
                    "title" => $val['title'],
                    "created_at" => date("Y-m-d H:i", $val['created_at']),
                );  
            }
        }

        // 浏览次数  浏览次数+1  1、听写次数  2、制作弹幕次数  3、翻译 
        $this->data_self_add("source", "view_count", 1, "where id>0 and id = '" . $info['id'] . "'");
        $countList = array();
        if ($info['type'] != 3) {
            $where = " where id > 0 and source_id = '" . $info['id'] . "' and source_type = 1 and do_type = 1";
            $countList['dictationCount'] = (int)$this->data_count("source_log", $where);
            $where = " where id > 0 and source_id = '" . $info['id'] . "' and source_type = 1 and do_type = 4";
            $countList['subtitlesCount'] = (int)$this->data_count("source_log", $where);
        }

        if ($info['type'] == 3 || $info['type'] == 4 || $info['type'] == 5) {
            $where = " where id > 0 and source_id = '" . $info['id'] . "' and source_type = 1 and do_type = 3 ";
            $countList['translationCount'] = (int)$this->data_count("source_log", $where);
        }
        // 是否收藏  是否点赞，点赞个数  评论个数  听写数量
        $collect = $this->data_getinfo('collect', ' source_id="' . $input['id'] . '" and type=1 and user_id="' . $_SESSION['user_id'] . '"');
        $is_collect = 1;
        if ($collect) {
            $is_collect = 2;
        }
        // 点赞 以及点赞个数
        $praise= $this->data_count('source_praise',' where source_id ="' . $input['id'] . '" and type=1 and user_id="' . $_SESSION['user_id'] . '"');
        $is_praise = 1;
        if ($praise > 0) {
            $is_praise = 2;
        }
        $praise_count = $this->data_count('source_praise', ' where source_id ="' . $input['id'] . '" and type=1');
        //评论个数
        $comment_count = $this->data_count('source_comment', ' where source_id="' . $input['id'] . '" and type=1');
        // 听写量
        $dictation_count = $this->data_count('source_dictation', ' where source_id="' . $input['id'] . '" and type=1');

        $info['is_collect'] = $is_collect;
        $info['is_praise'] = $is_praise;
        $info['praise_count'] = $praise_count;
        $info['comment_count'] = $comment_count;
        $info['dictation_count'] = $dictation_count;

        $param = array(
            "info" => $info,
            "avInfo" => $av,
            "textInfo" => $text,
            "isShow" => $is_show,
            "position" => $positionList,
            "countList" => empty($countList) ? (object)array() : $countList,
        );
        $this->ajaxReturn(200, "详情信息获取成功", $param);
    }

    /**
     * 进入听写详情页面
     * source_id 素材主表的id
     * source_period_id 素材性情表的id
     */
    public function dictationInfo()
    {
        $this->checkLogin();
        $siteurl = $this->siteurl;
        $input = $this->post;
        if (empty($input['source_id']) || empty($input['source_period_id'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }
        // 平台资源主表
        $sql = " id = '" . $input['source_id'] . "'";
        $source = $this->data_getinfo("source", $sql);
        $sourceInfo = array(
            "id" => $source['id'],
            "type" => $source['type'],
        );

        // 获取文章的标签 一篇文章  一个标签
        $dictation_tag = $this->getTagInfo($_SESSION['user_id'], $input['source_id'], 1);

        // 
        $sql = " id= '" . $input['source_period_id'] . "' and source_id = '" . $input['source_id'] . "' ";
        $avInfo = $this->data_getinfo("source_info", $sql);

        // 听写的内容
        $sql = " source_id='" . $input['source_id'] . "' and source_period_id='" . $input['source_period_id'] . "' and user_id='" . $_SESSION['user_id'] . "' and type = 1";
        $dictation = $this->data_getinfo("source_dictation", $sql);
        if (! empty($dictation)) {
            $dictation['content'] = htmlspecialchars_decode($dictation['content']);
        } 

        // 自动保存的内容
        $dictation_log = $this->data_getinfo("dictation_log", $sql);
        if (! empty($dictation_log)) {
            $dictation_log['content'] = empty($dictation_log['content']) ? "" : htmlspecialchars_decode($dictation_log['content']);
        }

        $info = empty($dictation_log) ? $dictation : $dictation_log;
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
     * dictation_tag 听写的记录标签选择 格式 1,2,3
     */
    public function dictation()
    {
        $this->checkLogin();
        $this->checkUser();

        $input = $this->post;
        $sourceInfo = $this->data_getinfo("source_info", "id='" . $input['id'] . "'");
        if (empty($sourceInfo)) {
            $this->ajaxReturn(202, "视频已删除或者下架");
        }

        if (empty($input['content'])) {
            $this->ajaxReturn(202, "听写内容不能为空");
        }
        $where = " source_id='" . $sourceInfo['source_id'] . "' and source_period_id='" . $input['id'] . "' and type= 1 and user_id='" . $_SESSION['user_id'] . "'";
        $dictationInfo = $this->data_getinfo("source_dictation", $where);
        
        // 查看标签
        $dictation_tag = "";
        if (!empty($input['dictation_tag'])) {
            $dictation_tag = "," . $input['dictation_tag'] . ",";
        }
        $data = array(
            "source_id" => $sourceInfo['source_id'],
            "source_period_id" => $input['id'],
            "content" => $input['content'],
            "created_at" => time(),
            "time" => $input['time'],
            "user_id" => $_SESSION['user_id'],
            "type" => 1,
            "dictation_tag" => $dictation_tag,
        );
        // 更新标签  一篇文章一个标签
        $this->updateTag($_SESSION['user_id'], $sourceInfo['source_id'], 1, $dictation_tag);
        // 加入听写操作表
        $this->sourceLog($sourceInfo['source_id'], $_SESSION['user_id'], 1, 1, $dictation_tag);
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
     * id 音视频的id (附表的id  即详情信息的id)
     * content 听写的内容
     * time 听写时间
     * dictation_tag  1,2,3
     */
    public function dictationLog()
    {
        $input = $this->post;
        $this->checkLogin();
        $this->checkUser();

        $sourceInfo = $this->data_getinfo("source_info", "id='" . $input['id'] . "'");
        if (empty($sourceInfo)) {
            $this->ajaxReturn(202, "视频已删除或者下架");
        }

        // 听写的标签
        $dictation_tag = "";
        if (!empty($input['dictation_tag'])) {
            $dictation_tag = "," . $input['dictation_tag'] . ",";
        }

        $where = " source_id='" . $sourceInfo['source_id'] . "' and source_period_id='" . $input['id'] . "' and type= 1 and user_id='" . $_SESSION['user_id'] . "'";
        $dictationInfo = $this->data_getinfo("dictation_log", $where);

        $data = array(
            "source_id" => $sourceInfo['source_id'],
            "source_period_id" => $input['id'],
            "content" => $input['content'],
            "created_at" => time(),
            "time" => $input['time'],
            "user_id" => $_SESSION['user_id'],
            "type" => 1,
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

    // 
    // 
    // 
    // 
    // 
    /**
     * 每个十秒自动保存一次听写内容 （自动保存功能）
     * id 音视频的id (附表的id  即详情信息的id)
     * content 听写的内容
     * time 听写时间
     * dictation_tag  1,2,3
     * pid  1平台  2个人  3精品课程 （只有type == 3 的时候  需要填写）；
     * type 类型 1平台  2 个人  3 合成的音视频听写  4精品课程
     */
    // 
    // 
    // 
    // 
    // 
    // 
    public function dictationLog1()
    {
        $input = $this->post;
        $this->checkLogin();
        $this->checkUser();

        $sourceInfo = $this->data_getinfo("source_info", "id='" . $input['id'] . "'");
        if (empty($sourceInfo)) {
            $this->ajaxReturn(202, "视频已删除或者下架");
        }

        $input['type'] = intval($input['type']) > 0 ? intval($input['type']) : 0;
        // 听写的标签
        $dictation_tag = "";
        if (!empty($input['dictation_tag'])) {
            $dictation_tag = "," . $input['dictation_tag'] . ",";
        }

        $pid = 0;
        if ($input['type'] == 3) {
            $pid = $input['pid'];
        }

        $where = " source_id='" . $sourceInfo['source_id'] . "' and source_period_id='" . $input['id'] . "' and type= '" . $input['type'] . "' and user_id='" . $_SESSION['user_id'] . "' and pid = '" . $pid . "'";
        $dictationInfo = $this->data_getinfo("dictation_log", $where);

        $data = array(
            "source_id" => $sourceInfo['source_id'],
            "source_period_id" => $input['id'],
            "content" => $input['content'],
            "created_at" => time(),
            "time" => $input['time'],
            "user_id" => $_SESSION['user_id'],
            "type" => $input['type'],
            "dictation_tag" => $dictation_tag,
            "pid" => $pid,
        );

        if (empty($dictationInfo)) {
            $dictationInfo = $this->data_add("dictation_log", $data);
        } else {
            $dictationInfo = $this->data_edit("dictation_log", $data, "id='" . $dictationInfo['id'] . "'");
        }
        $this->ajaxReturn(200, "听写信息保存成功");
    }

    /**
     * 进入弹幕页面的时候
     * source_period_id 资源的id
     * 
     */
    public function subtitlesInfo()
    {
        $this->checkLogin();
        $input = $this->post;
        if (empty($input['source_period_id'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }
        $sourceInfo = $this->data_getinfo("source_info", "id = '" . $input['source_period_id'] . "'");
        if (empty($sourceInfo)) {
            $this->ajaxReturn(202, "资源不存在或者已经被下架");
        }

        $info = $this->data_getinfo("source", " id = '" . $sourceInfo['source_id'] . "'");
        $sourceInfo['type'] = (int)$info['type'];

        $sql = " source_id = '" . $sourceInfo['source_id'] . "' and source_period_id = '" . $input['source_period_id'] . "' and user_id = '" . $_SESSION['user_id'] . "' and type = 1";
        $subtitles = $this->data_getinfo("source_subtitles", $sql);

        $content = json_decode($subtitles['content'], true);
        $subtitlesList = array();
        if (!empty($content)) {
            foreach ($content as $k => $val) {
                $subtitlesList[] = $val;
            }
        }

        $param = array(
            "subtitlesList" => $subtitlesList,
            "sourceInfo" => $sourceInfo,
        );
        $this->ajaxReturn(200, "字幕信息获取成功", $param);
    } 

    /**
     * 音视频制作弹幕
     * source_period_id 附表的id
     * content 弹幕的内容
     * 
     */
    public function subtitles()
    {
        $this->checkLogin();
        $this->checkUser();
        $input = $this->post;
        if (empty($input['source_period_id'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        $sourceInfo = $this->data_getinfo("source_info", "id = '" . $input['source_period_id'] . "'");

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

        $sql = "source_id = '" . $sourceInfo['source_id'] . "' and source_period_id = '" . $input['source_period_id'] . "' and user_id = '" . $_SESSION['user_id'] . "' and type=1 ";
        $info = $this->data_getinfo("source_subtitles", $sql);

        $data = array(
            "source_id" => $sourceInfo['source_id'],
            "source_period_id" => $input['source_period_id'],
            "content" => addslashes(json_encode($subtitles)),
            "created_at" => time(),
            "user_id" => $_SESSION['user_id'],
            "type" => 1,
        );

        // 加入朗读 听写操作表中  方便个人中心的列表展示
        $this->sourceLog($sourceInfo['source_id'], $_SESSION['user_id'], 1, 4);
        // 更新user表中的学习天数和最近学习的时间
        $this->doDays($_SESSION['user_id']);

        if (empty($info)) {
            $addSubtitles = $this->data_add("source_subtitles", $data);
        } else {
            $editSubtitles = $this->data_edit("source_subtitles", $data, " id = '" . $info['id'] . "'");
        }

        $this->ajaxReturn(200, "字幕制作成功");
    }

    /**
     * 文本素材朗读页面 初次进入
     * source_period_id 素材文本附表的id
     * 
     */
    public function readInfo()
    {
        $input = $this->post;
        if (empty($input['source_period_id'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        $info = $this->data_getinfo("source_text", "id='" . $input['source_period_id'] . "'");
        $sql = "source_id='" . $info['source_id'] . "' and source_period_id='" . $info['id'] . "' and user_id='" . $_SESSION['user_id'] . "' and type=1";
        $readInfo = $this->data_getinfo("source_read", $sql);
        if (!empty($readInfo)) {
            $readInfo['path'] = $this->config['qiniu'] . $readInfo['path'];
        }
        $param = array(
            "info" => $info,
            "readInfo" => $readInfo,
        );
        $this->ajaxReturn(200, "信息获取成功", $param);
    }

    /**
     * 文本素材的朗读 
     * source_period_id 附表的id
     * path 朗读的音频地址
     */
    public function read()
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

        $textInfo = $this->data_getinfo("source_text", "id = '" . $input['source_period_id'] . "' ");
        $data = array(
            "source_id" => $textInfo['source_id'],
            "source_period_id" => $input['source_period_id'],
            "path" => str_replace($this->config['qiniu'],'',$input['path']),
            "created_at" => time(),
            "user_id" => $_SESSION['user_id'],
            "type" => 1,
            "pid" => 0,
        );

        $sql = "source_id='" . $textInfo['source_id'] . "' and source_period_id='" . $input['source_period_id'] . "' and user_id='" . $_SESSION['user_id'] . "' and type=1";
        $readInfo = $this->data_getinfo("source_read", $sql);

        // 加入朗读 听写操作表中  方便个人中心的列表展示
        $this->sourceLog($textInfo['source_id'], $_SESSION['user_id'], 1, 2);
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
     * 文本的翻译 进入详情
     * source_period_id 附表的id
     * 
     */
    public function translationInfo()
    {
        $this->checkLogin();
        $input = $this->post;
        if (empty($input['source_period_id'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        $info = $this->data_getinfo("source_text", "id='" . $input['source_period_id'] . "'");
        $sql = "source_id='" . $info['source_id'] . "' and source_period_id='" . $info['id'] . "' and user_id='" . $_SESSION['user_id'] . "' and type=1";
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
    public function translation()
    {
        $this->checkLogin();
        $this->checkUser();
        $input = $this->post;
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

        $info = $this->data_getinfo("source_text", "id = '" . $input['source_period_id'] . "'");

        $data = array(
            "source_id" => $info['source_id'],
            "source_period_id" => $input['source_period_id'],
            "content" => $input['content'],
            "grammar" => $input['grammar'],
            "words" => $input['words'],
            "created_at" => time(),
            "user_id" => $_SESSION['user_id'],
            "type" => 1,
            "pid" => 0,
        );

        $sql = "source_id='" . $info['source_id'] . "' and source_period_id='" . $info['id'] . "' and user_id='" . $_SESSION['user_id'] . "' and type = 1";
        $translationInfo = $this->data_getinfo("source_translation", $sql);

        // 加入朗读 听写操作表中  方便个人中心的列表展示
        $this->sourceLog($info['source_id'], $_SESSION['user_id'], 1, 3);
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
    public function otherTranslation()
    {
        $this->checkLogin();
        $siteurl = $this->siteurl;
        $input = $this->post;
        if (empty($input['source_period_id'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }        
        $page = intval($input['page']) > 0 ? intval($input['page']) : 1;
        $pageSize = 8;
        $info = $this->data_getinfo("source_text", " id='" . $input['source_period_id'] . "'");
        $where = "WHERE id > 0 and source_id = '" . $info['source_id'] . "' AND source_period_id = '" . $info['id'] . "' AND type = 1 and user_id !='" . $_SESSION['user_id'] . "'";
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
                $praise = $this->data_count("praise", "where translation_id='" . $val['id'] . "'");
                $sql = "translation_id='" . $val['id'] . "' and user_id='" . $_SESSION['user_id'] . "'";
                $praiseInfo = $this->data_getinfo("praise", $sql);
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
    public function otherTranslationPraise()
    {
        $this->checkLogin();
        $this->checkUser();
        $input = $this->post;
        if (empty($input['id'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        $info = $this->data_getinfo("praise", "translation_id='" . $input['id'] . "' and user_id='" . $_SESSION['user_id'] . "'");
        if (!empty($info)) {
            $delPraise = $this->data_del("praise", " id='" . $info['id'] . "'");
            $this->ajaxReturn(200, "取消赞成功");
        } else {
            $data = array(
                "translation_id" => $input['id'],
                "created_at" => time(),
                "user_id" => $_SESSION['user_id'],
            );
            $addPraise = $this->data_add("praise", $data);
            $this->ajaxReturn(200, "点赞成功");
        }

    }

    /**
     * 生词列表
     * source_id 资源主表的id
     * page 页数
     */
    public function sourceWordList()
    {
        $this->checkLogin();
        $input = $this->post;
        if (empty($input['source_id'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        $where = "WHERE source_id='" . $input['source_id'] . "' AND user_id='" . $_SESSION['user_id'] . "' AND type =1";
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
    public function wordsInfo()
    {
        $input = $this->post;
        $this->checkLogin();
        $this->checkUser();


        $words_id = intval($input['words_id']) > 0 ? intval($input['words_id'])  : 0;
        $wordsInfo = $this->data_getinfo("source_words", "id='" . $words_id . "'");
        if (!empty($wordsInfo['pronunciation'])) {
            $wordsInfo['path'] = $this->config['qiniu'] . $wordsInfo['pronunciation'];

        }
        $userinfo = $this->data_getinfo("user", "id = '" . $wordsInfo['user_id'] . "'");
        $wordsInfo['nickname'] = empty($userinfo['nickname']) ? "----" : $userinfo['nickname'];

        $sql = " where id >0 and user_id = '" . $_SESSION['user_id'] . "' and name = '" . $wordsInfo['name'] . "'";
        $myWords = $this->data_list("source_words", $sql);
        $exist = empty($myWords) ? 1 : 2; //1 代表不存在生词  2 代表已经存在此生词
        $wordsInfo['is_exist'] = $exist;
        // 分类
        $where =  "where id > 0 and (user_id='" . $_SESSION['user_id'] . "' or pid = 0)";
        $data = $this->data_list("source_word_sort", $where);
        $wordsCategory = $this->categorys($data);

        $word = new StdClass();
        $param = array(
            "wordsInfo" => empty($wordsInfo) ? $word : $wordsInfo,
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
     * pronunciation_word 读音拼写
     * sentences 例句
     * associate 联想
     */
    public function subWords()
    {
        $input = $this->post;
        $this->checkLogin();
        $this->checkUser();
        $res = $this->checkField($input);
        if ($res[0] == 202) {
            $this->ajaxReturn(202, $res[1]);
        }
        // 查看是否有二级
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
            "type" => 1,
            "source_id" => $input['source_id'],
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

    

    /**
     * 新增加的
     * source_id 资源的id
     * linkstyle 联系方式
     * content 问题描述
     * type  1平台素材  2课程素材
     */
    public function reportError()
    {
        $this->checkLogin();
        $input = $this->post;
        if (empty($input['type'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        if (empty($input['source_id'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        if (empty($input['linkstyle'])) {
            $this->ajaxReturn(202, "联系方式不能为空");
        }

        if (empty($input['content'])) {
            $this->ajaxReturn(202, "问题反馈的内容不能为空");
        }

        $data = array(
            "source_id" => $input['source_id'],
            "linkstyle" => $input['linkstyle'],
            "content" => $input['content'],
            "created_at" => time(),
            "user_id" => $_SESSION['user_id'],
            "type" => $input['type'],
        );

        $addReportError = $this->data_add("report_error", $data);
        if (empty($addReportError)) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        } else {
            $this->ajaxReturn(200, "文章纠错信息反馈成功");
        }
    }

    // 制作弹幕
    // 合成音频
    // 听写完成 点击保存 可以分类保存起来
    // 进入素材详情页的时候 有操作信息没有补全
    // 疑问：1，朗读的时候 七牛云链接是我自己合成吗  还是前端自己传？ ----前端直接合成
    //       2，听写的时候  文字的颜色  字体的大小


    /**
     * 生词分类
     */
    public  function wordsCategoryList()
    {
        $this->checkLogin();
        $where =  "where id > 0 and (user_id='" . $_SESSION['user_id'] . "' or pid = 0)";
        $data = $this->data_list("source_word_sort", $where);
        $wordsCategory = $this->categorys($data);

        $param = array(
            "wordsCategory" => $wordsCategory,
        );

        $this->ajaxReturn(200, "信息获取成功", $param);
    }



    /**
     * 我的听写的标签
     * id 标签的id
     * name 标签的名称
     */
    public function modifyDictationTag()
    {
        $this->checkLogin();
        $input = $this->post;
        if (empty($input['name'])) {
            $this->ajaxReturn(202, "标签的名称不能为空");
        }
        // 查看标签是否存在
        $sql = " name = '" . $input['name'] . "' and user_id = '" . $_SESSION['user_id'] . "'";
        $dictationTag = $this->data_getinfo("dictation_tag", $sql);
        if (!empty($dictationTag)) {
            $this->ajaxReturn(202, "此标签已经存在 勿重复添加");
        }

        $data = array(
            "name" => $input['name'],
            "created_at" => time(),
            "user_id" => $_SESSION['user_id'],
        );

        if (empty($input['id'])) {
            $addDictationTag = $this->data_add("dictation_tag", $data);
            $param = array(
                "id" => (int)$addDictationTag
            );
            $this->ajaxReturn(200, "标签添加成功", $param);
        } else {
            $editDictationTag = $this->data_edit("dictation_tag", $data, " id = '" . $input['id'] . "'");
            $param = array(
                "id" => (int)$input['id'],
            );
            $this->ajaxReturn(200, "标签编辑成功", $param);
        }
    }


    /**
     * 展示的标签列表
     */
    public function dictationTagList()
    {
        $this->checkLogin();
        $list = $this->data_list("dictation_tag", " where id > 0 and user_id = '" . $_SESSION['user_id'] . "'");
        $dictationTag = array();
        if (!empty($list)) {
            foreach ($list as $k => $val) {
                $dictationTag[] = array(
                    "id" => (int)$val['id'],
                    "name" => $val['name'],
                );
            }
        }

        $param = array(
            "dictationTag" => $dictationTag,
        );
        $this->ajaxReturn(200, "获取标签列表成功！", $param);
    }


    /**
     * 删除此标签
     * id  标签的id
     */
    public function delDictationTag()
    {
        $this->checkLogin();
        $input = $this->post;
        if (empty($input['id'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        // 查看标签是否被使用
        $where = "where id > 0 and dictation_tag like '%". ",". $input['id'] ."," ."%' and user_id = '" . $_SESSION['user_id'] . "'";
        $info = $this->data_list("source_log", $where);
        if (! empty($info)) {
            $this->ajaxReturn(202, "标签已经被使用无法删除");
        }

        // 删除标签
        $delDictationTag = $this->data_del("dictation_tag", " id = '" . $input['id'] . "'");
        if (empty($delDictationTag)) {
            $this->ajaxReturn(202, "网络原因请刷新重试");
        } else {
            $this->ajaxReturn(200, "标签删除成功");
        }
    }


    /**
     * 热门推荐的列表
     * page 
     * pageSize
     * 
     */
    public function hotSourceList()
    {   
        $siteurl = "http://" . $this->config['siteurl'];
        $input = $this->post;

        $page = intval($input['page']) > 0 ? intval($input['page']) : 1;
        $pageSize = intval($input['pageSize']) > 0 ? intval($input['pageSize']) : 10;

        $where = "where id > 0 and position like '%" . ",1," . "%'";
        if (!empty ($input['type'])) {
            $where .= " and type = '" . $input['type'] . "'";
        }
        $order = "order by id desc";

        $param = array(
            "hotList" => array(),
        ); 

        $count = $this->data_count("source", $where);
        if (empty($count)) {
            $this->ajaxReturn(200, "暂时没有数据", $param);
        }

        $pagenum = ceil($count / $pageSize);
        if ($page > $pagenum) {
            $this->ajaxReturn(200, "数据加载完成", $param);
        }

        $limit = " LIMIT " . ($page-1) * $pageSize . "," . $pageSize;
        $list = $this->data_list("source", $where, $order, $limit);
        $hotList = array();
        if (! empty($list)) {
            foreach ($list as $k => $val) {
                $hotList[] = array(
                    "id" => $val['id'],
                    "title" => $val['title'],
                    "image" => formatAppImageUrl($val['image'], $siteurl),
                    "created_at" => date("Y-m-d H:i", $val['created_at']),
                    "type" => $val['type'],
                );
            }
        }

        $param = array(
            "hotList" => $hotList,
            "pagenum" => $pagenum,
        ); 
        $this->ajaxReturn(200, "热门推荐获取成功", $param);
    }



    /**
     * 精品课程的描述
     * 平台素材的描述
     * type 1平台素材  3课程素材
     * source_id 素材的id
     */
    public function description()
    {
        $input = in($_POST);
        $siteurl = "https://" . $this->config['siteurl'];
        if (empty($input['source_id'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        $table = $this->doTable($input['type']);
        if (empty($table)) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        $info = $this->data_getinfo($table, " id = '" . $input['source_id'] . "'");
        $param = array( 
            "description" => empty($info['description']) ? "" : getImgThumbUrl($info['description'], $siteurl),
        );

        $this->ajaxReturn(200, "描述获取成功", $param);
    }


    public function shareInfo()
    {
        $siteurl = "http://" . $this->config['siteurl'];
        $list = $this->data_list("form_data_share_pc", "where id>0", " order by id desc", "limit 1");

        $info = array();
        if (!empty($list)) {
            $info = array(
                "iosPath" => $list[0]['iosPath'],
                "anPath" => $list[0]['anPath'],
                "image" => formatAppImageUrl($list[0]['code'], $siteurl),
            );
        }

        $param = array(
            "info" => empty($info) ? (object)array() : $info,
        );
        $this->ajaxReturn(200, "ok", $param);
    }

        // 三级分类 加上全部
    public function cation1111($arr,$num=0,$m=1)
    {
        $list = [];
        foreach($arr as $k=>$v){
            if($v['pid'] == $num){
                // $v['level'] = $m;
                if ($m == 2) {
                    $ar = array(
                        "id" => 0,
                        "pid"=> (int)$v['pid'], 
                        "name" => "全部",
                        "son" => array(
                            array(
                                "id" => 0,
                                "pid" => 0,
                                "name" => "全部",
                            ),
                        ),
                    );
                } else {
                    $ar = array("id" => 0,"pid"=> (int)$v['pid'], "name" => "全部");  
                }

                if ($m < 3) {
                    $v['son'] = $this->cation1111($arr, $v['id'], $m+1);
                }
                $list[] = $v;
            }
        }

        // 判断节点 加上全部
        array_unshift($list, $ar);
       
        return $list;
    }


    public function sourceCategory11()
    {   
        // 三级分类
        $sourceCategory = $this->data_list("source_category", "where id>0 ", "order by id desc");
        $result = $this->cation1111($sourceCategory);
        $param = array(
                "sourceCategory" => $result,
            );
        $this->ajaxReturn(200, '数据分类获取成功', $param);
    }

    /**
     * 素材的评论 点赞 收藏
     * source_id 资源的id
     * type 1 平台素材  2 精品课程
     */
    public function collect()
    {
        $this->checkLogin();
        $input = $this->post;
        if (empty($input['source_id'])) {
            $this->ajaxReturn(202, '素材参数出错 刷新重试！');
        }

        if ($input['type'] != 1 && $input['type'] != 2) {
            $this->ajaxReturn(202, '素材参数出错 刷新重试！');
        }
        $info = $this->data_getinfo('collect', ' source_id="' . $input['source_id'] . '" and user_id ="' . $_SESSION['user_id'] . '" and type=' . $input['type']);
        if ($info) {
            $del = $this->data_del('collect', ' source_id="' . $input['source_id'] . '" and user_id="' . $_SESSION['user_id'] . '" and type =' . $input['type']);
            if (!$del) {
                $this->ajaxReturn(202, '网络出错 取消收藏失败');
            }else{
                $this->ajaxReturn(200, '取消收藏成功！');
            }
        }else{
            $data = [
                'user_id' => $_SESSION['user_id'],
                'source_id' => $input['source_id'],
                'created_at' => time(),
                'type' => $input['type']
            ];
            $add = $this->data_add('collect', $data);
            if (!$add) {
                $this->ajaxReturn(202, '网络原因 刷新重试！');
            }else{
                $this->ajaxReturn(200, '收藏成功！');
            }
        }

    }

    /**
     * 点赞 praise
     * user_id 点赞人
     * source_id 点赞的资源id
     */
    public function sourcePraise()
    {
        $this->checkLogin();
        $input = $this->post;
        if (!$input['source_id']) {
            $this->ajaxReturn(202,'参数错误 刷新重试！');
        }

        $info = $this->data_getinfo('source_praise', ' source_id="' . $input['source_id'] . '" and user_id ="' . $_SESSION['user_id'] . '" and type=1');
        if ($info) {
            $del = $this->data_del('source_praise', ' source_id="' . $input['source_id'] . '" and user_id="' . $_SESSION['user_id'] . '" and type =1');
            if (!$del) {
                $this->ajaxReturn(202, '网络原因 请刷新重试！');
            }else{
                $this->ajaxReturn(200, '取消点赞成功！');
            }
        }else{
            $data = [
                'user_id' => $_SESSION['user_id'],
                'source_id' => $input['source_id'],
                'created_at' => time(),
                'type' => 1,
            ];
            $add = $this->data_add('source_praise', $data);
            if (!$add) {
                $this->ajaxReturn(202, '网络原因 刷新重试！');
            }else{
                $this->ajaxReturn(200, '点赞成功！');
            }
        }
    }

    /**
     * 评价列表
     * user_id 评价人
     * content 评价内容
     * created_at 评价时间、
     * source_id 素材id
     * type 1平台素材
     */
    public function sourceComment()
    {
        $this->checkLogin();
        $input = $this->post;
        if (!$input['source_id']) {
            $this->ajaxReturn(202, '参数错误 请刷新重试！');
        }

        if (!$input['content']) {
            $this->ajaxReturn(202, '评价的内容不能为空！');
        }

        $data = [
            'user_id' => $_SESSION['user_id'],
            'content' => $input['content'],
            'created_at' => time(),
            'source_id' => $input['source_id'],
            'type' => $input['type'],
        ];
        $add = $this->data_add('source_comment', $data);
        if (!$add) {
            $this->ajaxReturn(202, '网络原因  评价失败');
        }else{
            $this->ajaxReturn(200, '评价成功！');
        }
    }

    /**
     * 评价列表
     * page
     * source_id
     */
    public function commentList()
    {
        $siteurl = $this->siteurl;
        $this->checkLogin();
        $page = intval($_POST['page']) ? intval($_POST['page']) : 1;
        $pageSize = intval($_POST['pageSize']) ? intval($_POST['pageSize']) : 20;
        $source_id = intval($_POST['source_id']) ? intval($_POST['source_id']) :0;

        if (!$source_id) {
            $this->ajaxReturn(202, '参数错误 请刷新重试！');
        }
        $where = ' where id > 0 and source_id="' . $source_id . '" and type=1';
        $order = ' order by created_at desc';
        $count = $this->data_count('source_comment', $where);
        $param = [
            'list' => [],
            'count' => $count,
        ];
        if (!$count) {
            $this->ajaxReturn(200, "暂时没有数据", $param);
        }
        $pageNum = ceil($count / $pageSize);
        if ($page > $pageNum) {
            $this->ajaxReturn(200, '数据加载完成！', $param);
        }

        $limit = ' LIMIT ' . ($page-1) * $pageSize . ',' . $pageSize;
        $list = $this->data_list('source_comment',$where,$order,$limit);
        $items = [];
        foreach($list as $k => $val) {
            $userinfo = $this->data_getinfo('user', ' id=' . $val['user_id']);
            $items[] = [
                'id' => (int)$val['id'],
                'content' => $val['content'],
                'nickname' => $userinfo['nickname'] ? :'',
                'avatar' => formatAppImageUrl($userinfo['avatar'], $siteurl),
                'created_at' => $this->formatCommentTime($val['created_at']),
                'reply_content' => $val['reply_content'],
            ];
        }
        $param = [
            'count' => (int)$count,
            'pageNum' => $pageNum,
            'list' => $items,
        ];
        $this->ajaxReturn(200, '信息获取成功!', $param);
    }

    /**
     * @param $time
     * @return false|string
     * 格式化时间
     */
    public function formatCommentTime($time){
        $ms = 24 * 3600;
        $diff_time = time() - $time;
        if ($diff_time < $ms) {
            if ($diff_time < 60) {
                return $diff_time . "秒前";
            } elseif ($diff_time < (60 * 60)) {
                return round($diff_time / 60) . "分钟前";
            } elseif ($diff_time <= (24 * 60 * 60)) {
                return round($diff_time / 60 / 60) . "小时前";
            }
        }else{
            return date('m-d H:i', $time);
        }
    }
}