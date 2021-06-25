<?php

/**
 * Class punchCardMod
 * 打卡活动操作
 * 类型：图文区 :1朗读 2打卡内容
 *      音频区  :1听写 2制作字幕
 *      视频区  :1听写 2制作字幕
 *      纯文本区 :1朗读 2翻译
 */
class punchCardMod extends commonMod
{
    protected $userinfo = [];
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
     * 打卡素材列表数据
     * page        页数
     * pageSize    每页展示的条数
     * type  类型  1图文区 2音频区 3视频区 4纯文本区
     */
    public function punchCard()
    {
        $input = $this->post;
        $siteurl = $this->siteurl;

        $page = intval($input['page']) ? intval($input['page']) : 1;
        $pageSize = intval($input['pageSize']) ? intval($input['pageSize']) : 15;
        $type = intval($input['type']) ? intval($input['type']) : 0;
        if (empty($type)) {
            $this->ajaxReturn(202, '参数错误 刷新重试！');
        }

        $created_at = strtotime(date('Y-m-d 00:00:00'));
        $where = ' where id > 0 and created_at < "' . $created_at . '" and type=' . $input['type'];

        $count = $this->data_count('punch_card', $where);
        $param = [
            'list' => [],
            'count' => $count,
        ];
        if (empty($count)) {
            $this->ajaxReturn(200, '暂时没有数据', $param);
        }
        $pageNum = ceil($count / $pageSize);
        if ($page > $pageSize) {
            $this->ajaxReturn(200, '数据加载完成', $param);
        }

        $limit = " LIMIT " . ($page-1) * $pageSize . "," . $pageSize;
        $list = $this->data_list('punch_card', $where,' order by created_at desc', $limit);

        $items = [];
        foreach($list as $k => $val) {
            $punch_card_count = $this->data_count('punch_card_log', ' where id > 0 and source_id ="' . $val['id'] . '"');
            $items[] = [
                'id' => (int)$val['id'],
                'image' => formatAppImageUrl($val['image'],$siteurl),
                'title' => $val['title'],
                'label_name' => $this->formatLabel($val['label_ids']),
                'type' => (int)$val['type'],
                'punch_card_count' => (int)$punch_card_count,
                'punch_card_user' => $this->getAvatar($val['id'], $siteurl),
            ];
        }
        $param = [
            'list' => $items,
            'count' => $count,
            'pageNum' => $pageNum,
        ];
        $this->ajaxReturn(200, '数据获取成功！', $param);
    }


    /*
     * 获取需要今日打卡素材
     */
    public function todayPunchCard()
    {
        $input = $this->post;
        $this->checkLogin();
        $userinfo = $this->userinfo;

        if (empty($input['type'])) {
            $this->ajaxReturn(202, '参数错误刷新重试！');
        }
        $siteurl = $this->siteurl;
        $time = strtotime(date('Y-m-d 00:00:00')); // 原来是 strtotime(date('Y-m-d 08:00:00'))
        $stime = strtotime(date('Y-m-d 00:00:00'));
        $etime = strtotime(date('Y-m-d 23:59:59'));
        $today = [];
        if (time() > $time) {
            $where = ' where id > 0 and created_at >=' . $stime . ' and created_at <= ' . $etime . ' and type=' . $input['type'];
            $list = $this->data_list('punch_card', $where, ' order by id desc');
            foreach($list as $k => $val) {
                $punch_card_count = $this->data_count('punch_card_log', ' where id > 0 and source_id ="' . $val['id'] . '"');
                $today[] = [
                    'id' => (int)$val['id'],
                    'image' => formatAppImageUrl($val['image'],$siteurl),
                    'title' => $val['title'],
                    'label_name' => $this->formatLabel($val['label_ids']),
                    'type' => (int)$val['type'],
                    'punch_card_count' => (int)$punch_card_count,
                    'punch_card_user' => $this->getAvatar($val['id'], $siteurl),
                    'created_at' => date('Y-m-d', $val['created_at']),
                ];
            }
        }
        //连续打卡的天数和总天数
        $punch_card_days = 0;
        $punch_card_days_total = $this->data_count('punch_card_log', ' where user_id = ' . $_SESSION['user_id']);

        if ($userinfo['punch_card_time'] > $stime) {
            $punch_card_days = $userinfo['punch_card_days'];
        }
        $param = [
            'punch_card_days' => (int)$punch_card_days,
            'punch_card_days_total' => (int)$punch_card_days_total,
            'list' => $today,
        ];
        $this->ajaxReturn(200, '今日打卡素材获取成功！', $param);
    }

    /**
     * 打卡素材詳情
     * source_id 打卡素材的id
     */
    public function punchCardInfo()
    {
        $input = $this->post;
        $siteurl = $this->siteurl;
        $this->checkLogin();
        $userinfo = $this->userinfo;
        if (empty($input['source_id'])) {
            $this->ajaxReturn(202, '参数错误 刷新重试！');
        }

        $info = $this->data_getinfo('punch_card', ' id = ' . $input['source_id']);
        if (empty($info)) {
            $this->ajaxReturn(202, '活动素材不存在 请刷新重试');
        }

        // 打卡活动素材 基础的信息
        $punch_card_count = $this->data_count('punch_card_log', ' where id > 0 and source_id ="' . $info['id'] . '"');
        // 其他打卡人数
        $other_punch_card_count = $this->data_count('punch_card_log', 'where id > 0 and source_id ="' . $info['id'] . '" and user_id !='.$_SESSION['user_id']);
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
        $param = [
            'id' => (int)$info['id'],
            'image' => formatAppImageUrl($info['image'], $siteurl),
            'title' => $info['title'],
            'label_name' => $this->formatLabel($info['label_ids']),
            'punch_card_count' => (int)$punch_card_count,
            'punch_card_user' => $this->getAvatar($info['id'], $siteurl),
            'other_punch_card_count' => (int)$other_punch_card_count,
            'notice' => isset($info['notice']) ? htmlspecialchars_decode($info['notice']):'',
            'words' => isset($info['words']) ? htmlspecialchars_decode($info['words']):'',
            'answer' => isset($info['answer']) ? htmlspecialchars_decode($info['answer']):'',
            'type' => (int)$info['type'],
        ];
        switch($info['type']) {
            case 1:
                $param['content'] = htmlspecialchars_decode($info['html_text']);
                break;
            case 2:
                // 音频
                $param['source_path'] = $info['source_path'];
                $avinfo = $this->audio_vedio($info);
                break;
            case 3:
                // 视频
                $param['source_path'] = $info['source_path'];
                $avinfo = $this->audio_vedio($info);
                break;
            case 4:
                $textList = $this->text($info);
        }

        $param = [
            'info' => $param,
            'isShow' => $is_show,
            'avInfo' => empty($avinfo) ? [] : $avinfo,
            'textInfo' => empty($textList) ? [] : $textList,
        ];
        $this->ajaxReturn(200, '数据获取成功！', $param);
    }



    /*
     * 打卡素材操作
     * type  1 朗读的 可以加上文字描述 新增字段  （添加打卡内容）
     *       2 听写  制作弹幕
     *       3 听写  制作弹幕
     *       4 朗读  翻译  添加生词  合成录音
     */

    /**
     * 进入听写页面
     * source_id   打卡活动主表的id
     * source_period_id  打卡活动的附表id
     */
    public function dictationInfo()
    {
        $this->checkLogin();
        $siteurl = $this->siteurl;
        $input = $this->post;
        if (empty($input['source_id']) || empty($input['source_period_id'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        // 打卡资源主信息
        $sql = " id = '" . $input['source_id'] . "'";
        $source = $this->data_getinfo("punch_card", $sql);
        $sourceInfo = array(
            "id" => (int)$source['id'],
            "type" => (int)$source['type'],
        );

        // 附表的信息
        $sql = " id= '" . $input['source_period_id'] . "'";
        $avInfo = $this->data_getinfo("punch_card_av", $sql);

        // 获取听写信息
        $sql = " source_id='" . $input['source_id'] . "' and source_period_id='" . $input['source_period_id'] . "' and user_id='" . $_SESSION['user_id'] . "' and type = 6";
        $dictation = $this->data_getinfo("source_dictation", $sql);
        if (! empty($dictation)) {
            $dictation['content'] = htmlspecialchars_decode($dictation['content']);
            $dictation['dictation_tag'] = empty($dictation['dictation_tag']) ? "" : substr($dictation['dictation_tag'], 1, strlen($dictation['dictation_tag']) - 2);
        }

        $param = [
            'sourceInfo' => $sourceInfo,
            'avInfo' => $avInfo,
            'dictationInfo' => empty($dictation) ? (object)[] : $dictation,
        ];
        $this->ajaxReturn(200, '听写页面信息获取成功！',$param);
    }

    /**
     * 音视频的听写操作 保存
     * id 音视频的id (附表的id  即详情信息的id)
     * content 听写的内容
     * time 听写时间
     * dictation_tag 听写的记录标签选择 格式 1,2,3
     */
    public function dictation()
    {
        $this->checkLogin();
        $userinfo = $this->userinfo;

        $input = $this->post;
        $sourceInfo = $this->data_getinfo("punch_card_av", "id='" . $input['id'] . "'");
        if (empty($sourceInfo)) {
            $this->ajaxReturn(202, "音頻/视频已删除或者下架");
        }
        // 判断是否是今日的打卡素材
        $punch_card = $this->data_getinfo("punch_card", ' id=' . $sourceInfo['source_id']);
        $res = $this->isTodayPunchCard($sourceInfo['source_id']);
        if ($res[0] != 0) {
            $this->ajaxReturn(202, $res[1]);
        }
        // 1，判断并更新连续打卡的天数 2，打卡记录表中是否有记录  有的话更新  没有的话 添加
        $res = $this->isUserLog($userinfo, $punch_card);
        // 保存或者编辑听写记录
        $where = " source_id='" . $sourceInfo['source_id'] . "' and source_period_id='" . $input['id'] . "' and type= 6 and user_id='" . $_SESSION['user_id'] . "'";
        $dictationInfo = $this->data_getinfo("source_dictation", $where);

        $dictation_tag = "";
        if (!empty($input['dictation_tag'])) {
            $dictation_tag = "," . $input['dictation_tag'] . ",";
        }
        $data = [
            "source_id" => $sourceInfo['source_id'],
            "source_period_id" => $input['id'],
            "content" => $input['content'],
            "created_at" => time(),
            "time" => $input['time'],
            "user_id" => $_SESSION['user_id'],
            "type" => 6,
            "dictation_tag" => $dictation_tag,
        ];
        if ($dictationInfo) {
            $dictationInfo = $this->data_edit("source_dictation", $data, "id='" . $dictationInfo['id'] . "'");
        }else{
            $dictationInfo = $this->data_add("source_dictation", $data);
        }
        $this->ajaxReturn(200, "听写保存成功");
    }

    /**
     * 进入制作弹幕的页面数据
     * source_period_id 资源附表的id
     */
    public function subtitlesInfo()
    {
        $this->checkLogin();
        $input = $this->post;
        if (empty($input['source_period_id'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }
        $sourceInfo = $this->data_getinfo("punch_card_av", "id = '" . $input['source_period_id'] . "'");
        if (empty($sourceInfo)) {
            $this->ajaxReturn(202, "打卡素材不存在或者已经被下架");
        }

        $punch_card = $this->data_getinfo('punch_card', ' id =' . $sourceInfo['source_id']);
        if (empty($punch_card)) {
            $this->ajaxReturn(202, '打卡素材不存在或者已经被下架');
        }
        $sourceInfo['type'] = (int)$punch_card['type'];

        $sql = " source_id = '" . $sourceInfo['source_id'] . "' and source_period_id = '" . $input['source_period_id'] . "' and user_id = '" . $_SESSION['user_id'] . "' and type = 6";
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
     * 制作字幕操作
     * source_period_id 素材附表的id
     * content 制作字幕的内容
     */
    public function subtitles()
    {
        $this->checkLogin();
        $userinfo = $this->userinfo;
        $input = $this->post;

        if (empty($input['source_period_id'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        $sourceInfo = $this->data_getinfo("punch_card_av", "id = '" . $input['source_period_id'] . "'");
        if (!$sourceInfo) {
            $this->ajaxReturn(202, '打卡素材不存在或者已被下架');
        }

        $punch_card = $this->data_getinfo('punch_card', ' id=' . $sourceInfo['source_id']);
        if (!$punch_card) {
            $this->ajaxReturn(202, '打卡素材不存在或者已被下架');
        }

        // 判断是否是今日的打卡活动素材
        $res = $this->isTodayPunchCard($sourceInfo['source_id']);
        if ($res[0] != 0) {
            $this->ajaxReturn(202, $res[1]);
        }
        // 1，判断并更新连续打卡的天数 2，打卡记录表中是否有记录  有的话更新  没有的话 添加
        $res = $this->isUserLog($userinfo, $punch_card);

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

        $sql = "source_id = '" . $sourceInfo['source_id'] . "' and source_period_id = '" . $input['source_period_id'] . "' and user_id = '" . $_SESSION['user_id'] . "' and type=6 ";
        $info = $this->data_getinfo("source_subtitles", $sql);

        $data = array(
            "source_id" => $sourceInfo['source_id'],
            "source_period_id" => $input['source_period_id'],
            "content" => addslashes(json_encode($subtitles)),
            "created_at" => time(),
            "user_id" => $_SESSION['user_id'],
            "type" => 6,
        );

        if (empty($info)) {
            $addSubtitles = $this->data_add("source_subtitles", $data);
        } else {
            $editSubtitles = $this->data_edit("source_subtitles", $data, " id = '" . $info['id'] . "'");
        }

        $this->ajaxReturn(200, "字幕制作成功");
    }

    /**
     * 图文区 纯文本区间
     * 进入图文区
     * source_id  主表的id
     */
    public function imageTextReadInfo()
    {
        $this->checkLogin();
        $input = $this->post;
        $userinfo = $this->userinfo;

        if (empty($input['source_id'])) {
            $this->ajaxReturn(202, '参数错误 请刷新重试！');
        }
        $info = $this->data_getinfo('punch_card', ' id = ' . $input['source_id']);
        if (!$info) {
            $this->ajaxReturn(202, '打卡素材已下架或者已被删除');
        }

        $sql = ' source_id ="' . $input['source_id'] . '" and type = 6 and user_id = ' . $_SESSION['user_id'];
        $readinfo = $this->data_getinfo('source_read', $sql);
        $read = [];
        if ($readinfo) {
            $read = [
                'content' => $readinfo['read_info'] ? :'',
                'path' => $this->config['qiniu'] . $readinfo['path'] ? :'',
            ];
        }

        $imageTextRead = $read ? $read : (object)[];
        $param = [
            'image_text_read' => $imageTextRead,
        ];
        $this->ajaxReturn(200, '打卡信息获取成功！', $param);
    }

    /**
     * 图文区朗读操作的按钮
     * source_id 课程素材的主表id
     * content 打卡内容
     * path 朗读的录音
     */
    public function imageTextRead()
    {
        $this->checkLogin();
        $userinfo = $this->userinfo;
        $input = $this->post;
        if (empty($input['source_id'])) {
            $this->ajaxReturn(202, '参数错误 请刷新重试！');
        }

        $punch_card = $this->data_getinfo('punch_card', ' id = ' . $input['source_id']);
        if (!$punch_card) {
            $this->ajaxReturn(202, '打卡活动素材不存在或者已被删除');
        }

        // 判断操作的是都是当天的打卡素材
        $res = $this->isTodayPunchCard($input['source_id']);
        if ($res[0] != 0) {
            $this->ajaxReturn(202, $res[1]);
        }
        // 判断并更新连续打卡的时间和连续打卡的天数
        $res = $this->isUserLog($userinfo, $punch_card);

        if (empty($input['content']) && empty($input['path'])) {
            $this->ajaxReturn(202, '打卡内容和录音不能同时为空');
        }
        // 判断是否已经有听写记录
        $sql = ' source_id ="' . $input['source_id'] . '" and type = 6 and user_id = ' . $_SESSION['user_id'];
        $readinfo = $this->data_getinfo('source_read', $sql);

        $data = [
            "source_id" => $input['source_id'],
            "source_period_id" => 0,
            "path" => $input['path'] ? str_replace($this->config['qiniu'], '', $input['path']):'',
            "created_at" => time(),
            "user_id" => $_SESSION['user_id'],
            "type" => 6,
            "pid" => 0,
            'read_info' => $input['content'] ?  :'',
        ];

        if (empty($readinfo)) {
            $addRead = $this->data_add("source_read", $data);
        } else {
            $editRead = $this->data_edit("source_read", $data, "id='" . $readinfo['id'] . "'");
        }
        $this->ajaxReturn(200, '朗读信息保存成功！');
    }

    /**
     * 純文本去 进去朗读页面
     * source_period_id  附表素材的id
     *
     */
    public function textReadInfo()
    {
        $this->checkLogin();
        $input = $this->post;
        if (empty($input['source_period_id'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        $info = $this->data_getinfo("punch_card_text", "id='" . $input['source_period_id'] . "'");
        $sql = "source_id='" . $info['source_id'] . "' and source_period_id='" . $input['source_period_id'] . "' and user_id='" . $_SESSION['user_id'] . "' and type=6";
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
     * 纯文本的保存听写操作
     * source_period_id 附表id
     * path 朗读的链接
     */
    public function textRead()
    {
        $this->checkLogin();
        $userinfo = $this->userinfo;
        $input = $this->post;
        if (empty($input['source_period_id'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        if (empty($input['path'])) {
            $this->ajaxReturn(202, "录音路径不能为空");
        }

        $textInfo = $this->data_getinfo("punch_card_text", "id = '" . $input['source_period_id'] . "' ");
        if (!$textInfo) {
            $this->ajaxReturn(202, '打卡活动素材不存在或者已被删除');
        }
        $punch_card = $this->data_getinfo('punch_card', ' id = ' . $textInfo['source_id']);
        if (!$punch_card) {
            $this->ajaxReturn(202, '打卡活动素材不存在或者已被删除');
        }
        // 判断是否是今天的打卡视频
        $res = $this->isTodayPunchCard($punch_card['id']);
        if ($res[0] != 0) {
            $this->ajaxReturn(202, $res[1]);
        }
        // 判断并更新连续打卡的时间和连续打卡的天数  以及打卡记录
        $res = $this->isUserLog($userinfo, $punch_card);

        $sql = "source_id='" . $textInfo['source_id'] . "' and source_period_id='" . $input['source_period_id'] . "' and user_id='" . $_SESSION['user_id'] . "' and type=6";
        $readInfo = $this->data_getinfo("source_read", $sql);

        $data = [
            "source_id" => $textInfo['source_id'],
            "source_period_id" => $input['source_period_id'],
            "path" => str_replace($this->config['qiniu'],'',$input['path']),
            "created_at" => time(),
            "user_id" => $_SESSION['user_id'],
            "type" => 6,
            "pid" => 0,
        ];
        if (empty($readInfo)) {
            $addRead = $this->data_add("source_read", $data);
        } else {
            $editRead = $this->data_edit("source_read", $data, "id='" . $readInfo['id'] . "'");
        }
        $this->ajaxReturn(200, '朗读信息保存成功！');
    }

    /**
     * 进入翻译页面
     * source_period_id 附表的id
     */
    public function translationInfo()
    {
        $this->checkLogin();
        $input = $this->post;
        if (empty($input['source_period_id'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        $info = $this->data_getinfo("punch_card_text", "id='" . $input['source_period_id'] . "'");
        $sql = "source_id='" . $info['source_id'] . "' and source_period_id='" . $input['source_period_id'] . "' and user_id='" . $_SESSION['user_id'] . "' and type=6";
        $translationInfo = $this->data_getinfo("source_translation", $sql);
        $param = array(
            "info" => $info,
            "translationInfo" => empty($translationInfo) ? (object)array() : $translationInfo,
        );
        $this->ajaxReturn(200, "信息获取成功", $param);
    }

    /**
     * 纯文本翻译
     * source_period_id 附表的id
     * content 翻译内容
     * grammar 语法
     * words 单词
     */
    public function translation()
    {
        $this->checkLogin();
        $userinfo = $this->userinfo;
        $input = $this->post;
        if (empty($input['source_period_id'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        if (empty($input['content'])) {
            $this->ajaxReturn(202, "翻译内容不能为空");
        }

        if (empty($input['words'])) {
            $this->ajaxReturn(202, "单词不能为空");
        }

        $info = $this->data_getinfo("punch_card_text", "id = '" . $input['source_period_id'] . "'");
        if (!$info) {
            $this->ajaxReturn(202, '打卡活动素材不存在或者已被删除');
        }
        $punch_card = $this->data_getinfo('punch_card', ' id = ' .  $info['source_id']);
        if (!$punch_card) {
            $this->ajaxReturn(202, '打卡活动素材不存在或者已被删除');
        }

        // 判断是否是今天的打卡视频
        $res = $this->isTodayPunchCard($punch_card['id']);
        if ($res[0] != 0) {
            $this->ajaxReturn(202, $res[1]);
        }
        // 判断并更新连续打卡的时间和连续打卡的天数  以及打卡记录
        $res = $this->isUserLog($userinfo, $punch_card);

        // 判断是否已经翻译
        $sql = "source_id='" . $info['source_id'] . "' and source_period_id='" . $info['id'] . "' and user_id='" . $_SESSION['user_id'] . "' and type = 6";
        $translationInfo = $this->data_getinfo("source_translation", $sql);

        $data = [
            "source_id" => $info['source_id'],
            "source_period_id" => $input['source_period_id'],
            "content" => $input['content'],
            "grammar" => $input['grammar'] ? :'',
            "words" => $input['words'],
            "created_at" => time(),
            "user_id" => $_SESSION['user_id'],
            "type" => 6,
            "pid" => 0,
        ];

        if (empty($translationInfo)) {
            $addTranslation = $this->data_add("source_translation", $data);
        } else {
            $editTranslation = $this->data_edit("source_translation", $data, " id = '" . $translationInfo['id'] . "'");
        }
        $this->ajaxReturn(200, "翻译保存成功");
    }

    /***
     * 我的生词列表
     * source_id
     * page
     */
    public function wordsList()
    {
        $this->checkLogin();
        $input = $this->post;
        if (empty($input['source_id'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        $where = "WHERE source_id='" . $input['source_id'] . "' AND user_id='" . $_SESSION['user_id'] . "' AND type =6";
        $order = " ORDER BY id DESC ";
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
     * 进儒生词的详情页的接口
     * words_id 生词表的id
     */
    public function wordsInfo()
    {
        $input = $this->post;
        $this->checkLogin();

        $words_id = intval($input['words_id']) > 0 ? intval($input['words_id'])  : 0;
        $wordsInfo = $this->data_getinfo("source_words", "id='" . $words_id . "' and user_id= " . $_SESSION['user_id']);
        if (!empty($wordsInfo['pronunciation'])) {
            $wordsInfo['path'] = $this->config['qiniu'] . $wordsInfo['pronunciation'];
        }

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
        $userinfo = $this->userinfo;
        if (!$input['source_id']) {
            $this->ajaxReturn(202, '参数错误 轻刷新重试！');
        }

        $punch_card = $this->data_getinfo('punch_card', ' id = ' . $input['source_id']);
        if (!$punch_card) {
            $this->ajaxReturn(202, '素材不存在或者已下架');
        }

        // 判断是否是今天的打卡视频
        $res = $this->isTodayPunchCard($punch_card['id']);
        if ($res[0] != 0) {
            $this->ajaxReturn(202, $res[1]);
        }
        // 判断并更新连续打卡的时间和连续打卡的天数  以及打卡记录
        $res = $this->isUserLog($userinfo, $punch_card);

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
            "type" => 6,
            "source_id" => $input['source_id'],
        );

        if (empty($input['words_id'])) {
            $addWords = $this->data_add("source_words", $data);
        } else {
            $editWords = $this->data_edit("source_words", $data, "id='" . $input['words_id'] . "'");
        }

        $this->ajaxReturn(200, "生词添加成功");
    }

    /**
     * 其他人打卡列表
     * source_id 主素材的id
     * page 页数
     */
    public function otherUserPunchCard()
    {
        $this->checkLogin();
        $siteurl = $this->siteurl;

        $input = $this->post;
        if (!$input['source_id']) {
            $this->ajaxReturn(202, '参数错误 刷新重试！');
        }
        $punch_card = $this->data_getinfo('punch_card', ' id = ' . $input['source_id']);
        if (!$punch_card) {
            $this->ajaxReturn(202, '素材资源被下架或者删除');
        }

        $page = intval($input['page']) ? intval($input['page']) : 1;
        $pageSize = intval($input['pageSize']) ? intval($input['pageSize']) : 20;

        $where = ' where  source_id = "' . $input['source_id'] . '" and user_id != ' . $_SESSION['user_id'];
        $count = $this->data_count('punch_card_log', $where);

        $param = [
            'list' => [],
            'pageNum' => 0,
            'count' => $count,
        ];
        if (empty($count)) {
            $this->ajaxReturn(200, '暂时没有数据', $param);
        }
        $pageNum = ceil($count / $pageSize);
        if ($page >$pageNum) {
            $this->ajaxReturn(200, '数据加载完成!', $param);
        }

        $limit = ' LIMIT ' . ($page - 1) * $pageSize . "," . $pageSize;
        $list = $this->data_list('punch_card_log', $where, "order by created_at desc", $limit);

        $items = [];
        foreach($list as $k => $val) {
            $user = $this->data_getinfo('user', ' id = ' . $val['user_id']);
            // 是否点赞  评论条数
            $praise =  $this->data_getinfo('source_praise', ' user_id = ' . $_SESSION['user_id'] . ' and source_id = ' . $val['id'] . ' and type = 6');
            $comment_count = $this->data_count('source_comment', ' where  user_id = ' . $_SESSION['user_id'] . ' and source_id = ' . $val['id'] . ' and type = 6');
            $items[] = [
                'id' => $val['id'],
                'source_id' => $input['source_id'],
                'user_id' => $val['user_id'],
                'nickname' => $user['nickname'] ? :'',
                'avatar' => formatAppImageUrl($user['avatar'], $siteurl),
                'content' => $val['content'] ? :'',
                'created_at' => $this->formatTime($val['created_at']),
                'info' => $this->punchCardDoLog($punch_card, $val),
                'is_praise' => $praise ? 1 : 2, // 1 已经点赞  2未点赞
                'comment_count' => $comment_count ? : 0,
            ];
        }

        $param = [
            'list' => $items,
            'pageNum' => $pageNum,
            'count' => $count,
        ];
        $this->ajaxReturn(200, '数据获取成功！', $param);
    }


    /**
     * 进入学习笔记的按钮
     * source_id 主资源的id
     */
    public function studyLog()
    {
        $this->checkLogin();
        $input = $this->post;
        if (empty($input['source_id'])) {
            $this->ajaxReturn(202, '参数错误 刷新重试！');
        }
        $info = $this->data_getinfo('punch_card_log', ' user_id = ' . $_SESSION['user_id'] . ' and source_id = ' . $input['source_id']);
        $param = [
            'source_id' => $input['source_id'],
            'content' => isset($info['content']) ? $info['content'] : '',
        ];
        $this->ajaxReturn(200, '学习笔记信息获取成功！', $param);
    }

    /**
     * 提交学习笔记
     * source_id
     * content 笔记
     */
    public function subStudyLog()
    {
        $this->checkLogin();
        $input = $this->post;
        if (empty($input['source_id'])) {
            $this->ajaxReturn(202, '参数错误 刷新重试！');
        }

        if (empty($input['content'])) {
            $this->ajaxReturn(202, '学习笔记不能为空');
        }

        $sql = ' source_id = ' . $input['source_id'] . ' and user_id = ' . $_SESSION['user_id'];
        $info = $this->data_getinfo('punch_card_log', $sql);
        $data = [
            'user_id' => $_SESSION['user_id'],
            'content' => $input['content'],
            'source_id' => $input['source_id'],
            'created_at' => time(),
        ];
        if ($info) {
            $editLog = $this->data_edit('punch_card_log', $data, ' id = ' . $info['id']);
        }else{
            $addLog = $this->data_add('punch_card_log', $data);
        }
        $this->ajaxReturn(200, '学习笔记更新成功！');
    }

    /**
     * 其他人打卡内容详情
     * source_id
     * user_id
     */
    public function otherPunchCardInfo()
    {
        $this->checkLogin();
        $input = $this->post;
        if (empty($input['source_id']) || empty($input['user_id'])) {
            $this->ajaxReturn(202,'参数错误 请刷新重试!');
        }

        $punch_card = $this->data_getinfo('punch_card', ' id = ' . $input['source_id']);
        if (empty($punch_card)) {
            $this->ajaxReturn(202, '打卡素材已被删除或者下架');
        }

        $info = [];
        switch($punch_card['type']) {
            case 1:
                $source_read = $this->data_getinfo('source_read', ' source_id = ' . $input['source_id'] . ' and user_id = ' . $input['user_id']. ' and type =6');
                $info[0] = [
                    'path' => $source_read['path'] ? $this->config['qiniu'] . $source_read['path'] :'',
                    'content' => $source_read['read_info'] ? :'',
                ];
                break;
            case 2:
                $info = $this->otherAvInfo($input);
                break;
            case 3:
                $res = $this->otherAvInfo($input);
                $info = $res;
                break;
            case 4:
                $punch_card_text = $this->data_list('punch_card_text', ' where  source_id = ' . $input['source_id']);
                foreach($punch_card_text as $k => $val) {
                    $sql = ' source_period_id = ' . $val['id'] . ' and type=6 and user_id = ' . $input['user_id'];
                    $source_read = $this->data_getinfo('source_read', $sql);
                    $source_translation = $this->data_getinfo('source_translation', $sql);
                    $info[] = [
                        'text_content' => $val['content'],
                        'read_path' => $source_read['path'] ? $this->config['qiniu'] . $source_read['path'] : '',
                        'translation_info' => $source_translation ? : (object)[],
                    ];
                }
                break;
        }

        $param = [
            'info' => $info,
        ];
        $this->ajaxReturn(200, '打卡内容详情信息获取成功！', $param);
    }

    /**
     * 查看其他人的学习笔记
     * source_id 打卡素材的主素材
     * user_id  要查看学习笔记的人的id
     */
    public function otherStudyLog()
    {
        $this->checkLogin();
        $input = $this->post;
        if (empty($input['source_id']) || empty($input['user_id'])) {
            $this->ajaxReturn(202,'参数错误 请刷新重试!');
        }

        $info = $this->data_getinfo('punch_card_log', ' source_id = ' . $input['source_id'] . ' and user_id = ' . $input['user_id']);

        $content = '';
        if ($info) {
            $content = $info['content'] ? :'';
        }

        $param = [
            'content' => $content,
        ];
        $this->ajaxReturn(200, '学习笔记获取成功！', $param);
    }

    /**
     * 点赞 学习（打卡）记录
     * log_id 打卡记录id
     */
    public function doPraise()
    {
        $this->checkLogin();
        $input = $this->post;

        if (!$input['log_id']) {
            $this->ajaxReturn(202, '参数错误 请刷新重试！');
        }

        $source_praise = $this->data_getinfo('source_praise', ' user_id = ' . $_SESSION['user_id'] . ' and source_id = ' . $input['log_id'] . ' and type =6');
        if (!$source_praise) {
            $data = [
                'user_id' => $_SESSION['user_id'],
                'source_id' => $input['log_id'],
                'type' => 6,
                'created_at' => time(),
            ];
            $this->data_add('source_praise', $data);
            $this->ajaxReturn(200, '点赞成功');
        }else{
            $this->data_del('source_praise', ' id = ' . $source_praise['id']);
            $this->ajaxReturn(200, '取消点赞成功');
        }
    }


    /**
     * 别人学习内容评论列表
     * log_id 別人學習記錄的id
     * page
     */
    public function commentList()
    {
        $this->checkLogin();
        $userinfo = $this->userinfo;
        $siteurl = $this->siteurl;
        $input = $this->post;

        $page = intval($input['page']) ? intval($input['page']) : 1;
        $pageSize = intval($input['pageSize']) ? intval($input['pageSize']) : 15;
        if (!$input['log_id']) {
            $this->ajaxReturn(202,'参数错误 刷新重试！');
        }

        $param = [
            'list' => [],
            'pageNum' => 0,
        ];
        $where = ' where id > 0 and source_id = ' . $input['log_id'] . ' and  type =6';
        $count = $this->data_count('source_comment', $where);
        if (!$count) {
            $this->ajaxReturn(200, '暂时没有数据！', $param);
        }

        $pageNum = ceil($count / $pageSize);
        if ($page > $pageSize) {
            $this->ajaxReturn(200, '数据加载完成', $param);
        }

        $limit = " limit  " . ($page - 1) * $pageSize . "," . $pageSize;
        $list = $this->data_list("source_comment", $where, ' order by id desc', $limit);

        $items = [];
        foreach($list as $k => $val) {
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
        $param = [
            'list' => $items,
            'pageNum' => $pageNum,
        ];
        $this->ajaxReturn(200, '评论列表获取成功！', $param);
    }

    /**
     * 发布评论信息
     * log_id 发布评论的id
     * content 发布的内容
     */
    public function sendComment()
    {
        $this->checkLogin();
        $userinfo = $this->userinfo;
        $input = $this->post;
        if (!$input['log_id']) {
            $this->ajaxReturn(202,'参数错误 请刷新重试！');
        }

        if (!$input['content']) {
            $this->ajaxReturn(202, '评价的内容不能为空！');
        }

        $data = [
            'user_id' => $_SESSION['user_id'],
            'source_id' => $input['log_id'],
            'content' => $input['content'],
            'type' => 6,
            'created_at' => time(),
        ];
        $info = $this->data_getinfo('source_comment', ' user_id = ' . $_SESSION['user_id'] . ' and source_id = ' . $input['log_id'] . ' and type = 6');
//        if ($info) {
//            $this->ajaxReturn(202, '已经评论过了 无须再评论');
//        }else{
            $add = $this->data_add('source_comment', $data);
            $this->ajaxReturn(200,'评价成功！');
//        }
    }

    /**
     * 回复信息的内容
     * comment_id 评价列表的id
     * content 评价内容
     *
     */
    public function replyContent()
    {
        $this->checkLogin();
        $userinfo = $this->userinfo;
        $input = $this->post;

        if (!$input['comment_id']) {
            $this->ajaxReturn(202,'参数错误 请刷新重试！');
        }

        if (!$commentInfo = $this->data_getinfo('source_comment', ' id =' . $input['comment_id'])) {
            $this->ajaxReturn(202, '非法操作！');
        }

        if (!$logInfo = $this->data_getinfo('punch_card_log', ' id = ' . $commentInfo['source_id'])) {
            $this->ajaxReturn(202, '参数错误 请刷新重试！');
        }

        if ($logInfo['user_id'] != $_SESSION['user_id']) {
            $this->ajaxReturn(202,'没有权限回复此评论');
        }

        $data = [
            'reply_content' => $input['content'],
            'reply_user_id' => $_SESSION['user_id'],
            'reply_at' => time(),
        ];

        $this->data_edit('source_comment', $data, ' id = ' . $input['comment_id']);
        $this->ajaxReturn(200, '信息回复成功！');
    }














    /**
     * 个人中心自己的打卡记录 我的打卡记录
     * page 页数
     */
    public function myPunchCardLog()
    {
        $this->checkLogin();
        $input = $this->post;
        $siteurl = $this->siteurl;

        $page = intval($_POST['page']) ? intval($_POST['page']) : 1;
        $pageSize = intval($_POST['pageSize']) ? intval($_POST['pageSize']) : 20;

        $param = [
            'list' => [],
        ];
        $where = ' where id > 0 and user_id = ' . $_SESSION['user_id'];
        $count = $this->data_count('punch_card_log', $where);

        if (!$count) {
            $this->ajaxReturn(200, '暂时没有数据', $param);
        }

        $pageNum = ceil($count / $pageSize);
        if ($page > $pageNum) {
            $this->ajaxReturn(200, '数据加载完成', $param);
        }

        $limit = ' LIMIT ' . ($page - 1) * $pageSize . "," . $pageSize;
        $items = [];
        $list = $this->data_list('punch_card_log', $where, ' order by id desc', $limit);
        foreach($list as $k => $val) {
            $punch_card = $this->data_getinfo('punch_card', ' id = ' . $val['source_id']);
            $punch_card_count = $this->data_count('punch_card_log', ' where id > 0 and source_id ="' . $val['source_id'] . '"');

            $items[] = [
                'log_id' => (int)$val['id'],
                'source_id' => (int)$val['source_id'],
                'user_id' => (int)$val['user_id'],
                'created_at' => $this->formatTime($val['created_at']),
                'image' => formatAppImageUrl($punch_card['image'],$siteurl),
                'title' => $punch_card['title'],
                'label_name' => $this->formatLabel($punch_card['label_ids']),
                'type' => (int)$punch_card['type'],
                'info' => $this->punchCardDoLog($punch_card, $val),
                'punch_card_count' => (int)$punch_card_count,
                'punch_card_user' => $this->getAvatar($val['source_id'], $siteurl),
            ];
        }
        $param = [
            'list' => $items
        ];
        $this->ajaxReturn(200, '我的打卡记录获取成功！', $param);
    }















    /**
     * 以下是辅助函数
     */

    /**
     * 查看別人的打卡内容
     */
    public function otherAvInfo($input)
    {
        $info = [];
        $punch_card_av = $this->data_list('punch_card_av', ' where  source_id = ' . $input['source_id']);
        foreach($punch_card_av as $k => $val) {
            $sql = ' source_period_id = ' . $val['id'] . ' and type=6 and user_id = ' . $input['user_id'];
            $source_dictation = $this->data_getinfo('source_dictation', $sql);
            $info[] = [
                'subtitle' => $val['subtitle'],
                'source_dictation' => $source_dictation['content'] ?  htmlspecialchars_decode($source_dictation['content']):'',
            ];
        }
        return $info;
    }
    /**
     * 判断是否是今日的素材
     */
    public function isTodayPunchCard($source_id)
    {
        $punch_card = $this->data_getinfo('punch_card', ' id =' . $source_id);
        if (empty($punch_card)) {
            return array(202, '音頻/视频已删除或者下架');
        }
        $nowtime = date("Y-m-d");
        $stime = date('Y-m-d', $punch_card['created_at']);
        if ($nowtime != $stime) {
            return array(202, '只能打卡今日的素材哦');
        }
        return array(0, '验证成功！');
    }

    /**
     * 判断user表中的连续打卡天数
     * 加入打卡punch_card_log表中
     */
    public function isUserLog($userinfo,$punch_card)
    {
        // 记录连续打卡的天数
        $formatNowTime = strtotime(date('Y-m-d 00:00:00'));
        $yesterday = $formatNowTime - (24 * 3600);
        if ($userinfo['punch_card_time'] >= $formatNowTime) {
            // 无操作
        }elseif($userinfo['punch_card_time'] >= $yesterday) {
            // 更新最后打开时间 连续打卡天数+1
            $this->data_self_add('user','punch_card_days', 1, ' where id =' . $_SESSION['user_id']);
        }else{
            $data = [
                'punch_card_days' => 1,
                'punch_card_time' => time(),
            ];
        }
        $data['punch_card_time'] = time();
        $this->data_edit('user',$data,' id = ' . $_SESSION['user_id']);
        // 是否有打卡记录  有打卡记录的话更新  没有的话添加 punch_card_log
        $sql = ' user_id="' . $_SESSION['user_id'] . '" and source_id="' . $punch_card['id'] . '"';
        $logInfo = $this->data_getinfo('punch_card_log', $sql);
        if (!$logInfo) {
            $data = [
                'user_id' => $_SESSION['user_id'],
                'content' => '',
                'source_id' => $punch_card['id'],
                'created_at' => time(),
            ];
            $this->data_add('punch_card_log', $data);
        }else{
            $data = [
                'created_at' => time(),
            ];
            $this->data_edit('punch_card_log', $data,' id =' .$logInfo['id']);
        }
    }

    /**
     * 格式化时间
     */
    public function formatTime($created_at)
    {
        $created_at  = $created_at ? : 0;
        $time = time() - $created_at;
        if ($time > 24 * 3600) {
            return date('Y-m-d H:i', $created_at);
        }else{
            return parent::formatTime($created_at); // TODO: Change the autogenerated stub
        }
    }

    /**
     * 音视频的 详情信息
     */
    public function audio_vedio($info)
    {
        $list = $this->data_list('punch_card_av', " where source_id = " . $info['id']);
        $items = [];
        foreach ($list as $k => $val) {
            //是否听写和制作字幕！
            $sql = "source_id = '" . $info['id'] . "' and source_period_id = '" . $val['id'] . "' and type=6 and user_id = '" . $_SESSION['user_id'] . "'";
            $dictationInfo = $this->data_getinfo("source_dictation", $sql);

            //字幕信息
            $subtitles = $this->data_getinfo("source_subtitles", $sql);

            $content = json_decode($subtitles['content'], true);
            $subtitlesList = array();
            if (!empty($content)) {
                foreach ($content as $key => $value) {
                    $subtitlesList[] = $value;
                }
            }
            $items[] = [
                'id' => (int)$val['id'],
                'subtitle' => $val['subtitle'],
                'path' => $val['path'],
                'is_dictationInfo' => $dictationInfo ? 1 : 2, //1 继续听写  2开始听写
                'dictationInfo' => $dictationInfo ? : (object)[],
                'subtitlesList' => $subtitlesList,
                'is_subtitles' => $subtitles ? 1 : 2,// 1 修改字幕  2制作字幕
            ];
        }

        return $items;
    }

    /**
     * 分段文本的詳情
     */
    public function text($info)
    {
        $textInfo = $this->data_list("punch_card_text", "where id>0 and source_id = '" . $info['id'] . "'");
        $items = [];
        foreach($textInfo as $k => $val) {
            $sql = "source_id = '" . $info['id'] . "' and source_period_id = '" . $val['id'] . "' and type=6 and user_id = '" . $_SESSION['user_id'] . "'";
            $translation = $this->data_getinfo("source_translation", $sql);
            $readInfo = $this->data_getinfo("source_read", $sql);
            $items[] = [
                "id" => (int)$val['id'],
                "source_id" => $val['source_id'],
                "content" => htmlspecialchars_decode($val['content']),
                "translation" => empty($translation) ? (object)[] : $translation,
                "read" => empty($readInfo) ? "" : $this->config['qiniu'] . $readInfo['path'],
            ];
        }

        return $items;
    }



    /**
     * 格式化標簽
     */
    public function formatLabel($label_ids)
    {
        $label = [];
        if (!empty($label_ids)) {
            $label_ids_arr = explode(',', $label_ids);
            for($i=0; $i < count($label_ids_arr); $i++) {
                $info = $this->data_getinfo('punch_card_label',' id = '. $label_ids_arr[$i]);
                $label[] = $info['name'] ?  :'';
            }
        }
        return $label;
    }
    /**
     * 打卡的人頭像
     */
    public function getAvatar($source_id, $siteurl)
    {
        $order = ' order by created_at desc';
        $list = $this->data_list('punch_card_log', ' where id > 0 and source_id ="' . $source_id . '"', $order, ' limit 5');
        $user_list = [];
        foreach ($list as $k => $val) {
            $userinfo = $this->data_getinfo('user', ' id= ' . $val['user_id']);
            $user_list[] = [
                'user_id' => $val['user_id'],
                'avatar' => formatAppImageUrl($userinfo['avatar'],$siteurl),
            ];
        }
        return $user_list;
    }

    /**
     * 其他人打卡辅助函数
     * 打卡素材操作記錄
     */
    public function punchCardDoLog($punch_card,$val)
    {
        $info = (object)[];
        switch($punch_card['type']) {
            case 1:
                $where = ' where source_id = ' . $punch_card['id'] . ' and type = 6 and  user_id = ' . $val['user_id'];
                $source_read = $this->data_list('source_read', $where, ' order by created_at desc', ' limit 1');
                $info = [
                    'content' => $source_read[0]['read_info'] ? :'',
                    'path' => $source_read[0]['path'] ? :'',
                ];
                break;
            case 2:
                $info = $this->av($punch_card,$val);
                break;
            case 3:
                $res = $this->av($punch_card , $val);
                $info = $res;
                break;
            case 4:
                $where = ' where source_id = ' . $punch_card['id'] . ' and type = 6 and  user_id = ' . $val['user_id'];
                $source_translation = $this->data_list('source_translation', $where, ' order by created_at desc', ' limit 1');
                if ($source_translation) {
                    $info  = [
                        'content' => $source_translation[0]['content'],
                    ];
                }else{
                    $source_words = $this->data_list('source_words', $where, 'order by id desc', ' limit 1');
                    if ($source_words) {
                        $info = [
                            'content' => '生词：' . $source_words[0]['name'] . '释义：' . $source_words[0]['paraphrase'] . '例句：' . $source_words[0]['sentences'],
                        ];
                    }else{
                        $source_read = $this->data_list('source_read', $where, ' order by created_at desc', ' limit 1');
                        $info = [
                            'content' => '',
                            'path' => $source_read[0]['path'] ? :'',
                        ];
                    }
                }
                break;
            default :
                break;
        }
        return $info;

    }


    public function av($punch_card, $val)
    {
        $info = (object)[];
        $where = " where source_id = " . $punch_card['id'] . ' and type = 6 and user_id = ' . $val['user_id'];
        $source_dictation = $this->data_list('source_dictation', $where, ' order by created_at desc', ' limit 1');
        if ($source_dictation) {
            $info = [
                'content' => htmlspecialchars_decode($source_dictation[0]['content']),
            ];
        }else{
            $source_subtitles = $this->data_list('source_subtitles', $where, ' order by created_at desc', ' limit 1');
            $content = json_decode($source_subtitles[0]['content'], true);
            if (!empty($content)) {
                $content = '';
                foreach ($content as $k => $val) {
                    $content  .= $val['content'] . " ";
                }
                $info = [
                    'content' => $content,
                ];
            }
        }
        return $info;
    }










}