<?php

/*
 * @打卡活动 素材列表
 * @Version 1.0
 */

class punchCardMod extends commonMod {

    protected $tablename = 'u';
    public function __construct() {

        parent::__construct();
    }

    // 打卡活动素材 列表页面
    public function index() {
        $input = $this->get;
        $where = " where id > 0 ";
        $where_url1 = "?1-1";
        $order = " order by id desc";

        if (!empty($input['title'])) {
            $where .= ' and `title` like "%' . $input['title'] . '%" ';
            $where_url1 .= '&title=' . $input['title'];
        }

        if (!empty($input['id'])) {
            $where .= ' and `type` = '.$input['id'];
            $where_url1 .= '&id=' . urlencode($input['id']);
        }
        //分页处理
        $url = __URL__ . '/index/page-{page}'.$where_url.'.html' . $where_url1; //分页基准网址
        $listRows = 20;
        $limit = $this->pagelimit($url, $listRows);
        //内容列表
        $list = $this->plist("punch_card", $limit, $where, $order);
        if (!empty($list)) {
            foreach($list as $k => $val) {
                switch ($val['type']) {
                    case 1:
                        $typename = "图文区";
                        break;

                    case 2:
                        $typename = "音频区";
                        break;

                    case 3:
                        $typename = "视频区";
                        break;

                    case 4:
                        $typename = "纯文本区";
                        break;

                    default;
                }
                //标签
                $label_ids_arr = explode(',', $val['label_ids']);
                $name = '';
                $len = count($label_ids_arr);
                for ($y=0 ; $y < $len ; $y++) {
                    $labelInfo = $this->data_getinfo('punch_card_label', ' id = "' . $label_ids_arr[$y] . '"');

                    $name .= $labelInfo['name'] . "/";
                }
                $list[$k] = array(
                    "id" => $val['id'],
                    "title" => $val['title'],
                    "typename" => $typename,
                    'name' => $name,
                    "created_at" => date("Y-m-d", $val['created_at']),
                );
            }
        }

        $options = $this->config['punch_card'];
        array_unshift($options, array('id' => 0, 'name' => '全部'));
        $options = json_encode($options);
        //统计总内容数量
        $count = $this->pcount("punch_card", $where);
        //分页处理
        $id = empty($input['id']) ? " " : (int)$input['id'];
        $this->assign("count", $count);
        $this->assign("id", $id);
        $this->assign("source", $input['title']);
        $this->assign("options", $options);
        $this->assign("list", json_encode($list));
        $this->assign("title", "打卡活动素材");
        $this->assign('page', $this->page($url, $count, $listRows));
        $this->show();
    }

    // 添加平台素材页面
    public function add()
    {
        $this->action = "add";
        $this->action_name = "添加打卡活动素材";
        $this->assign("info", json_encode((object)array()));
        $this->assign("options", json_encode($this->config['punch_card'])); //素材类型  音频 视频 图文 纯文本
        $list = $this->data_list('punch_card_label', ' where id > 0') ? :[];
        $this->assign('punchCardLabel', json_encode($list));
        $this->show("punchCard/info");
    }

    //添加素材
    public function addSave()
    {
        $input = $this->post;
        // 检测字段
        $res = $this->punch_checkFile($input, $_FILES);
        if (!empty($res)) {
            $this->ajaxReturn($res[0], $res[1]);
        }


        $images = imageUpload($_FILES['picFile'], "source_".time().$_SESSION[$this->config['SPOT'].'_user'], 1);

        $data = array(
            "title" => $input['title'],
            "image" => $images[0],
            "created_at" => strtotime($input['created_at']),
            "type" => $input['type'],
            "description" => $input['description'],
            "position" => empty($input['position']) ? "" : "," . $input['position'] . ",",
            "path" => "",
            "source_path" => empty($input['source_path']) ? "" : $input['source_path'],
            "notice" => empty($input['notice']) ? "" : $input['notice'],
            "words" => empty($input['words']) ? "" : $input['words'],
            "answer" => empty($input['answer']) ? "" : $input['answer'],
            "view_count" => empty($input['view_count']) ? 0 : intval($input['view_count']),
            'html_text' => $input['htmltext'],
            'label_ids' => $input['label'],
        );

        $res = $this->punch_addSource($input, $addid, $data);
        $this->ajaxReturn($res[0], $res[1]);
    }

    // 编辑信息
    public function edit()
    {
        $siteurl = "http://".$this->config['siteurl'];
        $sid = intval($_GET['sid']);
        $info = $this->data_getinfo("punch_card", " id = '" . $sid . "'");

        // 组装info 为了展示信息
        if (!empty($info)) {
            $info['type'] = (int)$info['type'];
            $fileList = array(array("name"=> $info['image'], "url" => $siteurl.$info['image']));
            $info['fileList'] = empty($info['image']) ? (string)"" : $fileList;
            $subtitles = array(array("name" => $info['subtitles'], "url" => $siteurl.$info['subtitles']));

            $info['words'] = htmlspecialchars_decode($info['words']);
            $info['notice'] = htmlspecialchars_decode($info['notice']);
            $info['description'] = htmlspecialchars_decode($info['description']);
            $info['answer'] = htmlspecialchars_decode($info['answer']);
            $info['subtitles'] = empty($info['subtitles']) ? "" : $subtitles;
            if (!empty($info['position'])) {
                $position = substr($info['position'], 1, -1);
                $parr = explode(",", $position);
                for($i=0; $i<count($parr); $i++) {
                    $parr[$i] = (int)($parr[$i]);
                }
                $info['position'] = $parr;
            }else{
                $info['position'] = array();
            }

            $categorys = array((string)$info['category_one_id'], (string)$info['category_two_id'], (string)$info['category_three_id']);
            $info['category'] = $categorys;
            // pathList
            $sourceInfo = $this->data_list("punch_card_av", "where id > 0 and source_id = '" . $sid . "'");
            $pathList = array();
            if (!empty($sourceInfo)) {
                foreach ($sourceInfo as $k => $val) {
                    $pathList[] = array(
                        "id" => $val['id'],
                        "path" => $val['path'],
                        "subtitle" => $val['subtitle'],
                    );
                }
            }
            $info['pathList'] = $pathList;
            // textList
            $sourceText = $this->data_list("punch_card_text", " where id > 0 and source_id = '" . $sid . "'");
            $textList = array();
            if (! empty($sourceText)) {
                foreach ($sourceText as $k => $val) {
                    $textList[] = array(
                        'id' => $val['id'],
                        "content" => $val['content'],
                    );
                }
            }
            $info['textList'] = $textList;
        }
        //新增
        $info['created_at'] = date('Y-m-d', $info['created_at']);
        $info['html_text'] = htmlspecialchars_decode($info['html_text']);
        $info['description'] = $info['description'];
        $label_ids_arr = explode(',', $info['label_ids']);
        $info['label_ids'] = $label_ids_arr;

        $this->action = "edit";
        $this->action_name = "编辑打卡素材";

        $list = $this->data_list('punch_card_label', ' where id > 0') ? :[];
        $this->assign('punchCardLabel', json_encode($list));

        $this->assign("info", json_encode($info));
        $this->assign("options", json_encode($this->config['punch_card']));
        $this->assign("positions", json_encode($this->config['position']));
        $this->assign("categorys", json_encode($categorys));
        $this->show("punchCard/info");
    }

    // 编辑素材材料的时候
    public function editSave()
    {
        $input = $this->post;
//        print_r($input);die;
        $siteurl = "http://".$this->config['siteurl'];
        if (empty($input['id'])) {
            $this->ajaxReturn(202, "参数错误，请刷新重试");
        }
        // 检测字段
        $res = $this->punch_checkFile($input, $_FILES);
        if (!empty($res)) {
            $this->ajaxReturn($res[0], $res[1]);
        }
        // 图片
        if (! empty($_FILES['picFile'])) {
            $images = imageUpload($_FILES['picFile'], "source_".time().$_SESSION[$this->config['SPOT'].'_user'], 1);
            $image = $images[0];
        } else {
            $image = str_replace($siteurl,"",$input['fileList']);
        }


        $data = array(
            "title" => $input['title'],
            "image" => $image,
            "created_at" => strtotime($input['created_at']),
            "type" => $input['type'],
            "description" => $input['description'],
            "source_path" => empty($input['source_path']) ? "" : $input['source_path'],
            "notice" => empty($input['notice']) ? "" : $input['notice'],
            "words" => empty($input['words']) ? "" : $input['words'],
            "answer" => empty($input['answer']) ? "" : $input['answer'],
            "view_count" => empty($input['view_count']) ? 0 : intval($input['view_count']),
            'html_text' => $input['htmltext'],
            'label_ids' => $input['label'],
        );

        // 编辑信息
        $res = $this->punch_editSource($input, $data);
        $this->ajaxReturn($res[0], $res[1]);
    }

    public function editTime()
    {
        $input = in($_POST);

        if (empty($input['id'])) {
            $this->ajaxReturn(202, "参数错误，请刷新重试");
        }
        $data = array(
            'created_at' => strtotime($input['name']),
        );

        $edit = model('u')->data_edit("punch_card", $data, 'id = "' . $input['id'] . '"');
        $this->ajaxReturn(200, '时间修改成功');
    }


    // 辅助函数
    public function newCategorys()
    {
        $data = model("u")->data_list("source_category", "where id > 0");
        if (! empty($data)) {
            foreach ($data as $k => $val) {
                $data[$k]['label'] = $val['name'];
                $data[$k]['value'] = $val['id'];
            }
        }
        $list = $this->categorys($data);
        return $list;
    }

    // 分类列表
    public function categorys($data, $pid='pid', $id='id'){
        //第一步 构造数据
        $items = array();
        if (empty($data)){
            return array();
        }
        foreach($data as $value){
            $items[$value['id']] = $value;
        }
        //第二部 遍历数据 生成树状结构
        $tree = array();
        foreach($items as $key => $value){
            if(isset($items[$value['pid']])){
                $items[$value['pid']]['children'][] = &$items[$key];
            }else{
                $tree[] = &$items[$key];
            }
        }
        return $tree;
    }

}