<?php
/**
 * 个人上传的素材接口
 * @音视频素材的各种听写 制作弹幕操作
 * @文本素材的各种朗读 翻译 添加生词操作
 */
class userSourceMod extends commonMod 
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
//        if ($userinfo['type'] != 3) {
//            if ($userinfo['type'] == 2) {
//                if ($userinfo['endtime'] < time()) {
//                    $this->ajaxReturn(202, "会员已经到期 无法操作");
//                }
//            } else {
//                $this->ajaxReturn(202, "不是会员，没有操作权限" . $_SESSION['user_id']);
//            }
//        }
        $file_size = $this->data_sum('user_source', ' where id > 0 and user_id = ' . $_SESSION['user_id'], 'file_size');
        $file_size_g = $file_size / 1024;
        $file_size_g = sprintf('%.2f', $file_size_g);

        $free_space = $this->data_list('form_data_file_space', ' where id >0 ', ' order by id desc',  ' limit 1');

        $unit = 'M';// 单位默认为兆
        $use_space = $file_size;
        $total_space = $free_space[0]['unmem_space'];

        if ($userinfo['type'] == 3) {
            $unit = 'G';
            $use_space = $file_size_g;
            $total_space = $free_space[0]['mem_space'];
        }else{
            if ($userinfo['type'] == 2) {
                if ($userinfo['endtime'] < time()) {
                    $unit = 'M';
                    $use_space = $file_size;
                    $total_space = $free_space[0]['unmem_space'];
                }else{
                    $unit = 'G';
                    $use_space = $file_size_g;
                    $total_space = $free_space[0]['mem_space'];
                }
            } else {
                $unit = 'M';
                $use_space = $file_size;
                $total_space = $free_space[0]['unmem_space'];
            }
        }

        $param = [
            'unit' => $unit,
            'use_space' => $use_space,
            'total_space' => $total_space,
        ];

        return $param;

    }


    /**
     * 个人素材列表接口
     * type 个人素材类型 1音频  2视频  3文本
     * page 页数
     */
    public function userSource()
    {
        $siteurl = $this->siteurl;
        $this->checkLogin();
        $input = $this->post;
        
        $where = "where id>0 and user_id='" . $_SESSION['user_id'] . "'"; 
        if (!empty($input['type'])) {
            $where .= "and type='" . $input['type'] . "'";
        }

        if (!empty($input['title'])) {
            $where .= ' AND title LIKE "%' . $input['title'] . '%"';
        }

        $order = "order by id desc";
        $page = intval($input['page']) > 0 ? intval($input['page'])  : 1;
        $pageSize = intval($input['pageSize']) > 0 ? intval($input['pageSize']) : 10;

        $count = $this->data_count("user_source", $where);

        $param = array(
            "userSourceList" => array(),
        );

        if (empty($count)) {
            $this->ajaxReturn(200, "暂时没有数据", $param);
        }

        $pagenum = ceil($count / $pageSize);
        if ($pagenum < $page) {
            $this->ajaxReturn(200, "数据加载完成", $param);
        }

        $limit = " limit " . ($page-1) * $pageSize . "," . $pageSize;
        $list = $this->data_list("user_source", $where, $order, $limit);
        $userSourceList = array();
        if (!empty($list)) {
            foreach ($list as $k => $val) {
                $userSourceList[] = array(
                    "id" => $val['id'],
                    "image" => formatAppImageUrl($val['image'], $siteurl),
                    "title" => $val['title'],
                    "created_at" => date("Y-m-d H:i", $val['created_at']),
                    "type" => $val['type'],
                );
            }
        }
        $param = array(
            "userSourceList" => $userSourceList,
            "pageNum" => $pagenum,
        );
        $this->ajaxReturn(200, "信息获取成功", $param);
    }

    /**
     * 个人素材的详情
     * source_id 素材id
     */
    public function userSourceInfo()
    {
        $this->checkLogin();
        $siteurl = $this->siteurl;
        $input = $this->post;
        if (empty($input['source_id'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        $info = $this->data_getinfo("user_source", "id='" . $input['source_id'] . "'");
        if (empty($info)) {
            $this->ajaxReturn(202, "素材不存在或者已经下架");
        }
        // 如果是文本
        $sourceText = array();
        if ($info['type'] == 3) {
            $sourceTextList = $this->data_list("user_source_text", "where user_period_id = '" . $input['source_id'] . "'", "order by id desc");
            if (!empty($sourceTextList)) {
                foreach ($sourceTextList as $k => $val) {
                    $where = "source_id = '" . $val['user_period_id'] . "' and source_period_id='" . $val['id'] . "' and type = 2 and user_id='" . $_SESSION['user_id'] . "'";
                    $translation = $this->data_getinfo("source_translation", $where);
                    $readInfo = $this->data_getinfo("source_read", $where);
                    if (!empty($readInfo)) {
                        $readInfo['path'] = $this->config['qiniu'] . $readInfo['path'];
                    }
                    $sourceText[] = array(
                        "id" => $val['id'],
                        "content" => $val['content'],
                        "info" => empty($translation) ? (object)array() : $translation,//翻译内容
                        "readInfo" => empty($readInfo) ? (object)array() : $readInfo, //朗读内容
                    );
                }
            }
        } else {
            $sql = "source_id='" . $input['source_id'] . "' and user_id='" . $_SESSION['user_id'] . "' and type=2";
            $dictationInfo = $this->data_getinfo("source_dictation", $sql);
            $content = $dictationInfo['content'];
            $time = $dictationInfo['time'];
            // 获取文章的标签
            $dictation_tag = $this->getTagInfo($_SESSION['user_id'], $input['source_id'], 2);
            $tagName = $this->getTagName($dictation_tag, $_SESSION['user_id']);

            // 查看听写内容的翻译
            $translation = $this->dictationTranslation($input['source_id'], 0, $_SESSION['user_id'], 2);
            // 制作字幕的信息
            $sql = " source_id = '" . $info['id'] . "' and source_period_id = 0 and user_id = '" . $_SESSION['user_id'] . "' and type = 2";
            $subtitles = $this->data_getinfo("source_subtitles", $sql);

            $subtitlesContent = json_decode($subtitles['content'], true);
            $subtitlesList = array();
            if (!empty($subtitlesContent)) {
                foreach ($subtitlesContent as $k => $val) {
                    $subtitlesList[] = $val;
                }
            }
        }
        // 合成的音频
        $merge_audio = $this->mergeAudio($info['id'], $_SESSION['user_id'], 2);
        // 我的听写->朗读
        $dictationRead = $this->dictationReadLog($input['source_id'], 0, 2, $_SESSION['user_id']);
        $sourceInfo = array(
            "id" => $info['id'],
            "merge_audio" => empty($merge_audio) ? "" : $this->config['qiniu'] . $merge_audio,
            "image" => formatAppImageUrl($info['image'], $siteurl),
            "title" => $info['title'],
            "created_at" => date("Y-m-d H:i", $info['created_at']),
            "content" => empty($content) ? "" : htmlspecialchars_decode($content),
            "time" => empty($time) ? 0 : $time,
            "dictation_tag" => empty($dictation_tag) ? "" : substr($dictation_tag, 1, strlen($dictation_tag) - 2),
            "dictation_tag_info" => $tagName,
            "translation" => empty($translation) ? (object)array() : $translation,
            "type" => $info['type'],
            "subtitlesList" => empty($subtitlesList) ? array() : $subtitlesList,
            "dictationRead" => empty($dictationRead) ? (object)array() : $dictationRead,
            "user_path" => $info['user_path'] ? $this->config['qiniu'] . $info['user_path'] : '',
        );

        $param = array(
            "sourceText" => $sourceText,
            "sourceInfo" => $sourceInfo,
        );
        $this->ajaxReturn(200, "信息获取成功", $param);
    }

    /**
     * 进入听写详情页面
     * source_id 素材主表的id
     */
    public function dictationInfo()
    {
        $this->checkLogin();
        $siteurl = $this->siteurl;
        $input = $this->post;
        if (empty($input['source_id'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        $info = $this->data_getinfo('user_source', ' id = ' . $input['source_id']);
        $items = [];
        if($info) {
            $items = [
                'id' => $info['id'],
                'user_path' => $info['user_path'] ?  $this->config['qiniu'] . $info['user_path'] : '',
                'type' => $info['type'],
            ];
        }
        // 获取文章的标签 一篇文章  一个标签
        $dictation_tag = $this->getTagInfo($_SESSION['user_id'], $input['source_id'], 2);
        
        $sql = " source_id='" . $input['source_id'] . "' and user_id='" . $_SESSION['user_id'] . "' and type = 2 and source_period_id = 0";

        // 查看dictation_log中有没有值  有的话 显示log表中的数据  无得话展示表中数据
        $infoLog = $this->data_getinfo("dictation_log", $sql);
        $info = $this->data_getinfo("source_dictation", $sql);
        if (! empty($infoLog)) {
            $infoLog['content'] = empty($infoLog['content']) ? "" : htmlspecialchars_decode($infoLog['content']);
        } 

        if (!empty($info)) {
            $info['content'] = htmlspecialchars_decode($info['content']);
        } 

        $dictationInfo = empty($infoLog) ? $info : $infoLog;
        $dictationInfo['dictation_tag'] = empty($dictation_tag) ? "" : substr($dictation_tag, 1, strlen($dictation_tag) - 2);
        $param = array(
            'info' => $items ?  : (object)[],
            "dictationInfo" => empty($dictationInfo) ? (object)array() : $dictationInfo,
        );
        $this->ajaxReturn(200, "信息获取成功", $param);
    }

    /**
     * 项目听写
     * source_id 资源的id
     * content 听写内容
     * time 听写使用的时间
     * dictation_tag 标签  格式传 1,2,3,4
     */
    public function userDictation()
    {   
        $input = $this->post;
        $this->checkLogin();
        $this->checkUser();

        $sourceInfo = $this->data_getinfo("user_source", "id = '" . $input['source_id'] . "'");
        if ($sourceInfo['type'] != 1  && $sourceInfo['type'] != 2) {
            $this->ajaxReturn(202, "非法操作");
        }
        
        if (empty($input['source_id'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        if (empty($input['content'])) {
            $this->ajaxReturn(202, "听写的内容不能为空");
        }

        if (empty($input['time'])) {
            $this->ajaxReturn(202, "传入听写时间");
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
            "type" => 2,
            "dictation_tag" => $dictation_tag,
        );
        $sql = "source_id='" . $input['source_id'] . "' and user_id='" . $_SESSION['user_id'] . "' and type=2 and source_period_id = 0";
        $userDictationInfo = $this->data_getinfo("source_dictation", $sql);

        // 更新标签  一篇文章一个标签
        $this->updateTag($_SESSION['user_id'], $input['source_id'], 2, $dictation_tag);
        // 加入朗读 听写操作表
        $this->sourceLog($input['source_id'], $_SESSION['user_id'], 2, 1, $dictation_tag);

        // 删除dictation_log表中的数据 （每隔10s更新一次de表）;
        $this->data_del("dictation_log", $sql);

        // 更新user表中的学习天数和最近学习的时间
        $this->doDays($_SESSION['user_id']);
        if (empty($userDictationInfo)) {
            $addDictation = $this->data_add("source_dictation", $data);
        } else {
            $editDictation = $this->data_edit("source_dictation", $data, "id='" . $userDictationInfo['id'] . "'");
        }
        $this->ajaxReturn(200, "听写保存成功");
    }


    /**
     * 每个十秒自动保存一次听写内容 （自动保存功能）
     * source_id 音视频的id (主表的id)
     * content 听写的内容
     * time 听写时间
     * dictation_tag  1,2,3
     */
    public function dictationLog()
    {
        $input = $this->post;
        $this->checkLogin();
        $this->checkUser();

        $sourceInfo = $this->data_getinfo("user_source", "id='" . $input['source_id'] . "'");
        if (empty($sourceInfo)) {
            $this->ajaxReturn(202, "视频已删除或者下架");
        }

        // 听写的标签
        $dictation_tag = "";
        if (!empty($input['dictation_tag'])) {
            $dictation_tag = "," . $input['dictation_tag'] . ",";
        }

        $where = " source_id='" . $input['source_id'] . "' and source_period_id= 0 and type= 2 and user_id='" . $_SESSION['user_id'] . "'";
        $dictationInfo = $this->data_getinfo("dictation_log", $where);

        $data = array(
            "source_id" => $input['source_id'],
            "source_period_id" => 0,
            "content" => $input['content'],
            "created_at" => time(),
            "time" => $input['time'],
            "user_id" => $_SESSION['user_id'],
            "type" => 2,
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
     * 进入制作弹幕的页面
     * source_id 资源的id
     */
    public function userSubtitlesInfo()
    {
        $input = $this->post;
        $this->checkLogin();
        if (empty($input['source_id'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }
        $info = $this->data_getinfo("user_source", " id = '" . $input['source_id'] . "'");

        $sql = "source_id = '" . $input['source_id'] . "' and user_id = '" . $_SESSION['user_id'] . "' and type = 2";
        $subtitles = $this->data_getinfo("source_subtitles", $sql);

        $content = json_decode($subtitles['content'], true);
        $subtitlesList = array();
        if (!empty($content)) {
            foreach ($content as $k => $val) {
                $subtitlesList[] = $val;
            }
        }

        $info = $this->data_getinfo('user_source', ' id = ' . $input['source_id']);
        $items = [];
        if($info) {
            $items = [
                'id' => $info['id'],
                'user_path' => $info['user_path'] ?  $this->config['qiniu'] . $info['user_path'] : '',
                'type' => $info['type'],
            ];
        }

        $param = array(
            'info' => $items ? :(object)[],
            "subtitlesList" => $subtitlesList,
        );
        $this->ajaxReturn(200, "信息获取成功", $param);
    }

    /**
     * 制作弹幕
     * source_id 住表的id
     * content 弹幕的内容
     */
    public function userSubtitles()
    {
        $this->checkLogin();
        $input = $this->post;
        $this->checkUser();
        if (empty($input['source_id'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        $sourceInfo = $this->data_getinfo("user_source", "id = '" . $input['source_id'] . "'");

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

        $sql = "source_id = '" . $input['source_id'] . "' and user_id = '" . $_SESSION['user_id'] . "' and type = 2";
        $info = $this->data_getinfo("source_subtitles", $sql);

        $data = array(
            "source_id" => $input['source_id'],
            "source_period_id" => 0,
            "content" => addslashes(json_encode($subtitles)),
            "created_at" => time(),
            "user_id" => $_SESSION['user_id'],
            "type" => 2,
        );

        // 加入朗读 听写操作表
        $this->sourceLog($input['source_id'], $_SESSION['user_id'], 2, 4);

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
     * 文本素材朗读页面 初次进入
     * source_period_id 素材文本附表的id
     * 
     */
    public function userReadInfo()
    {
        $this->checkLogin();
        $input = $this->post;
        if (empty($input['source_period_id'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        $info = $this->data_getinfo("user_source_text", "id='" . $input['source_period_id'] . "'");
        $sql = "source_id='" . $info['user_period_id'] . "' and source_period_id='" . $info['id'] . "' and user_id='" . $_SESSION['user_id'] . "' and type=2";
        $readInfo = $this->data_getinfo("source_read", $sql);

        $param = array(
            "info" => $info,
            "readInfo" => $readInfo,
        );
        $this->ajaxReturn(200, "信息获取成功", $param);
    }

    /**
     * 文本素材的朗读
     * source_period_id  附表的id 
     * path 朗读之后的七牛云的链接
     * 
     */
    public function userRead()
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

        $textInfo = $this->data_getinfo("user_source_text", " id='" . $input['source_period_id'] . "'");
        $data = array(
            "source_id" => $textInfo['user_period_id'],
            "source_period_id" => $input['source_period_id'],
            "path" => $input['path'],
            "created_at" => time(),
            "user_id" => $_SESSION['user_id'],
            "type" => 2,
            "pid" => 0,
        );

        $sql = "source_id='" . $textInfo['user_period_id'] . "' and source_period_id='" . $input['source_period_id'] . "' and user_id='" . $_SESSION['user_id'] . "' and type=2";
        $readInfo = $this->data_getinfo("source_read", $sql);

        // 加入朗读 听写操作表
        $this->sourceLog($textInfo['user_period_id'], $_SESSION['user_id'], 2, 2);

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
    public function userTranslationInfo()
    {
        $this->checkLogin();
        $input = $this->post;
        if (empty($input['source_period_id'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        $info = $this->data_getinfo("user_source_text", "id='" . $input['source_period_id'] . "'");
        $sql = "source_id='" . $info['user_period_id'] . "' and source_period_id='" . $info['id'] . "' and user_id='" . $_SESSION['user_id'] . "' and type=2";
        $translationInfo = $this->data_getinfo("source_translation", $sql);
        $param = array(
            "info" => $info,
            "translationInfo" => empty($translationInfo) ?  (object)array() : $translationInfo,
        );
        $this->ajaxReturn(200, "信息获取成功", $param);
    }


    /**
     * 文本翻译
     * source_period_id
     * content 翻译内容
     * grammar 语法
     * words 单词
     */
    public function userTranslation()
    {
        $input = $this->post;
        $this->checkLogin();
        $this->checkUser();
        if (empty($input['source_period_id'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        if (empty($input['content'])) {
            $this->ajaxReturn(202, "翻译内容不能为空");
        }

        if (empty($input['grammar'])) {
            $this->ajaxReturn(202, "语法不能为空");
        }

        if (empty($input['words'])) {
            $this->ajaxReturn(202, "单词不能为空");
        }

        $info = $this->data_getinfo("user_source_text", "id = '" . $input['source_period_id'] . "'");

        $data = array(
            "source_id" => $info['user_period_id'],
            "source_period_id" => $input['source_period_id'],
            "content" => $input['content'],
            "grammar" => $input['grammar'],
            "words" => $input['words'],
            "created_at" => time(),
            "user_id" => $_SESSION['user_id'],
            "type" => 2,
            "pid" => 0,
        );
        $sql = "source_id='" . $info['user_period_id'] . "' and source_period_id='" . $info['id'] . "' and user_id='" . $_SESSION['user_id'] . "' and type = 2";
        $translationInfo = $this->data_getinfo("source_translation", $sql);

        // 加入朗读 听写操作表
        $this->sourceLog($info['user_period_id'], $_SESSION['user_id'], 2, 3);

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
     * 生词列表
     * source_id 资源主表的id
     * page 页数
     */
    public function userSourceWordList()
    {
        $this->checkLogin();
        $input = $this->post;
        if (empty($input['source_id'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        $where = "WHERE source_id='" . $input['source_id'] . "' AND user_id='" . $_SESSION['user_id'] . "' AND type =2";
        $order = "ORDER BY id DESC";
        $page = intval($input['page']) > 0 ? intval($input['page']) : 1;
        $pageSize = 20;

        $count = $this->data_count("source_words", $where);

        $param = array("wordsList" => array());

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
    public function userWordsInfo()
    {
        $input = $this->post;
        $this->checkLogin();

        $words_id = intval($input['words_id']) > 0 ? intval($input['words_id'])  : 0;
        $wordsInfo = $this->data_getinfo("source_words", "id='" . $words_id . "' and type = 2");
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
     * 提交（保存/编辑）生词
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
    public function subUserWords()
    {
        $input = $this->post;
        $this->checkLogin();
        $this->checkUser();
        $res = $this->checkField($input);
        if ($res[0] == 202) {
            $this->ajaxReturn(202, $res[1]);
        }

        // 查是否有下级
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
            "type" => 2,
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
     * 进入个人素材编辑页的信息
     * user_source_id  个人素材的id
     */
    public function editUserSource()
    {
        $siteurl = "http://" . $this->config['siteurl'];
        $userSourceId = intval($_POST['user_source_id']) > 0 ? intval($_POST['user_source_id']) : 0;
        $info = $this->data_getinfo("user_source", " id = '" . $userSourceId . "'");

        $userSourceInfo = array();
        if (!empty($info)) {
            $userSourceInfo['id'] = (int)$info['id'];
            $userSourceInfo['title'] = $info['title'];
            $userSourceInfo['image'] = formatAppImageUrl($info['image'], $siteurl);
            $userSourceInfo['type'] = (int)$info['type'];
        }
        $param = array(
            "userSourceInfo" => empty($userSourceInfo) ? (object)array() : $userSourceInfo,
        );

        $this->ajaxReturn(200, "信息获取成功", $param);
    }
    /**
     * 上传音视频
     * user_source_id 编辑的时候
     * images 图片链接
     * title 上传的
     * image 封面图
     * type 1音频 2视频
     * 新增加的字段
     *
     * user_path :音频或者视频的路径
     * file_size 音视频的文件大小  大小按  M  计算
     */
    public function uploadSource()
    {
        $input = $this->post;
        $siteurl = "http://" . $this->config['siteurl'];
        $this->checkLogin();

        // 新增代码
        $result = $this->checkUser();
        $free = $result['total_space'] - $result['use_space'];
        if ($result['unit'] == 'G') {
            $free = ($result['total_space'] - $result['use_space']) * 1024;
        }
        if ($free < $input['file_size']) {
            $this->ajaxReturn(202, '个人云空间不足 无法上传');
        }
        // 新增代码

        if (empty($input['title'])) {
            $this->ajaxReturn(202, "上传的标题不能为空");
        }

        if (empty($_FILES['image']) && empty($input['images'])) {
            $this->ajaxReturn(202, "封面图上传失败");
        }

        $sourceImg = ""; 
        if (!empty($_FILES['image'])) {
            $folder = time().$_SESSION['user_id'];
            $image = imageUpload($_FILES['image'], $folder);
            $sourceImg = $image[0];
        } else {
            $sourceImg = str_replace($siteurl, "", $input['images']);
        }

        
        if ($input['type'] != 1 && $input['type'] != 2){
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        if (!$input['user_path']) {
            $this->ajaxReturn(202, '请上传音频或者视频');
        }
        $data = array(
            "title" => $input['title'],
            "image" => $sourceImg,
            "created_at" => time(),
            "type" => $input['type'],
            "path" => "",
            "user_id" => $_SESSION['user_id'],
            'user_path' => $input['user_path'],
            'file_size' => $input['file_size'],
        );

        if (empty($input['user_source_id'])) {
            $addUserSource = $this->data_add("user_source", $data);
            $userSourceId = $addUserSource;
        } else {
            $addUserSource = $this->data_edit("user_source", $data, " id = '" . $input['user_source_id'] . "'");
            $userSourceId = $input['user_source_id'];
        }
        if (empty($addUserSource)) {
            $this->ajaxReturn(202, "网络原因请刷新重试");
        } else {
            $param = array(
                "userSourceId" => $userSourceId,
            );
            $this->ajaxReturn(200, "资源上传成功", $param);
        }

    }


    /**
     * 上传素材文本
     * title 总的大标题
     * image 图片
     * content 文本的信息 json数组格式
     */
    public function uploadSourceText()
    {
        $input = $this->post;

        $this->checkLogin();
        $this->checkUser();
        if (empty($input['title'])) {
            $this->ajaxReturn(202, "标题不能为空");
        }

        if (empty($_FILES['image'])) {
            $this->ajaxReturn(202, "缩略图上传失败");
        }

        $subtitleNum = count($input['subtitle']);
        $contentNum = count($input['content']);
        // if (empty($subtitleNum)) {
        //     $this->ajaxReturn(202, "文本小段标题不能为空");
        // }

        if (empty($contentNum)) {
            $this->ajaxReturn(202, "文本内容不能为空");
        }

        // if ($subtitleNum != $contentNum) {
        //     $this->ajaxReturn(202, "上传的文本内容不能为空");
        // }

        $folder = time() . $_SESSION['user_id'];
        $image = imageUpload($_FILES['image'], $folder);

        $data = array(
            "title" => $input['title'],
            "image" => $image[0],
            "created_at" => time(),
            "type" => 3,
            "path" => "",
            "user_id" => $_SESSION['user_id'],
        );

        $this->model->query("START TRANSACTION");
        $addSourceText = $this->data_add("user_source", $data);
        if (empty($addSourceText)) {
            $this->model->query("ROLLBACK");
            $this->ajaxReturn(202, "网络原因请刷新重试");
        }

        // 循环将文本内容保存到表里 php
        for($i = 0; $i < $contentNum; $i++) {
            $sourceText = array(
                "user_period_id" => $addSourceText,
                "title" => empty($input['subtitle'][$i]) ? "" : $input['subtitle'][$i],
                "content" => $input['content'][$i],
                "created_at" => time(),
            ); 

            $addPeriodText = $this->data_add("user_source_text", $sourceText);
            if (empty($addPeriodText)) {
                $this->model->query("ROLLBACK");
                $this->ajaxReturn(202, "文本写入失败");
            }
        }

        $param = array("addSourceTextId" => $addSourceText);
        $this->model->query("COMMIT");
        $this->ajaxReturn(200, "文本上传成功");
    }

    /**
     * 检测文件的是否还有大小
     */
    public function checkSpaceSize()
    {
        $userinfo = $this->userinfo;
        $total_file_size = $this->data_sum('user_source', ' where id > 0 and user_id = ' . $_SESSION['user_id'],'file_size');
        $free_space = $this->data_list('form_data_file_space', ' where id >0 ', ' order by id desc',  ' limit 1');

        $total_file_size_g = $total_file_size / 1024; // 将剩余的空间由M 转换成G
        $total_file_size_g = sprintf('%.2f', $total_file_size_g);

        if ($userinfo['type'] != 3) {
            if ($userinfo['type'] == 2) {
                if ($userinfo['endtime'] < time()) {
                    //$this->ajaxReturn(202, "会员已经到期 无法操作");
                    if ($total_file_size >= $free_space[0]['unmem_space']) {
                        $this->ajaxReturn(202, '音视频的储存空间不足');
                    }
                }else{
                    // 是会员
                    if ($total_file_size_g >= $free_space[0]['mem_space']) {
                        $this->ajaxReturn(202, '音视频的储存空间不足');
                    }
                }
            } else {
                //$this->ajaxReturn(202, "不是会员，没有操作权限".$_SESSION['user_id']);
                if ($total_file_size >= $free_space[0]['unmem_space']) {
                    $this->ajaxReturn(202, '音视频的储存空间不足');
                }
            }
        }else{
            //永久会员
            if ($total_file_size_g >= $free_space[0]['mem_space']) {
                $this->ajaxReturn(202, '音视频的储存空间不足');
            }
        }
    }
    
    /**
     * 文件剩余的空间
     */
    public function freeSpace()
    {
        $this->checkLogin();
        $userinfo = $this->userinfo;

        $file_size = $this->data_sum('user_source', ' where id > 0 and user_id = ' . $_SESSION['user_id'], 'file_size');
        $file_size_g = $file_size / 1024;
        $file_size_g = sprintf('%.2f', $file_size_g);

        $free_space = $this->data_list('form_data_file_space', ' where id >0 ', ' order by id desc',  ' limit 1');

        $unit = 'M';// 单位默认为兆
        $use_space = $file_size;
        $total_space = $free_space[0]['unmem_space'];

        if ($userinfo['type'] == 3) {
            $unit = 'G';
            $use_space = $file_size_g;
            $total_space = $free_space[0]['mem_space'];
        }else{
            if ($userinfo['type'] == 2) {
                if ($userinfo['endtime'] < time()) {
                    $unit = 'M';
                    $use_space = $file_size;
                    $total_space = $free_space[0]['unmem_space'];
                }else{
                    $unit = 'G';
                    $use_space = $file_size_g;
                    $total_space = $free_space[0]['mem_space'];
                }
            } else {
                $unit = 'M';
                $use_space = $file_size;
                $total_space = $free_space[0]['unmem_space'];
            }
        }

        $param = [
            'unit' => $unit,
            'use_space' => $use_space,
            'total_space' => $total_space,
        ];
        $this->ajaxReturn(200, '空间使用情况获取成功!', $param);
    }

    /**
     * 刪除个人素材
     * source_ids 素材id 格式：1，2，3
     */
    public function delUserSource()
    {
        $this->checkLogin();
        $input = $this->post;
        if (!$input['source_ids']) {
            $this->ajaxReturn(202, '选择要删除的个人素材资源');
        }

        $source_ids_arr = explode(',', $input['source_ids']);
        for($i=0;$i<count($source_ids_arr);$i++) {
            $userSource = $this->data_getinfo('user_source', ' id = ' . $source_ids_arr[$i]);
            if ($userSource['user_id'] == $_SESSION['user_id']) {
                $del = $this->data_del('user_source',' id = ' . $source_ids_arr[$i]);
            }
        }
        $this->ajaxReturn(200, '个人素材删除成功！');
    }
}