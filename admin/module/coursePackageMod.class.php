<?php

/*
 * @精品课程套餐
 * @Author  Jeff<jeff.chou@aliyun.com>    2016-9-21
 * @Version 1.0
 */

class coursePackageMod extends commonMod {

    protected $tablename = 'u';

    public function __construct() {

        parent::__construct();
    }
    // 辅助函数
    public function newCategorys()
    {
        $data = model("u")->data_list("good_course", "where id > 0 and pid = 0", " order by id desc");
        if (! empty($data)) {
            foreach ($data as $k => $val) {
                $data[$k]['label'] = $val['title'];
            }
        }
        $list = $this->categorys($data);
        return $list;
    }
    // 平台素材分类列表
    public function index() {
        $data = model("u")->data_list("good_course", "where id > 0 and pid = 0", " order by id desc");
        if (! empty($data)) {
            foreach ($data as $k => $val) {
                $data[$k]['label'] = $val['title'];
            }
        }
        $list = $this->categorys($data);

        $this->assign("list", $list);
        //分页处理
        $this->assign("title", "精品课程套餐");
        $this->show();
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
        $list = array(
            array(
                "id" => 0,
                "label" => "分类",
                "name" => "分类",
                "children" => $tree,
            )
        );
        return $list;
    }

    // 辅助函数
    public function _newCategorys()
    {
        $data = model("u")->data_list("good_course_category", "where id > 0");
        if (! empty($data)) {
            foreach ($data as $k => $val) {
                $data[$k]['label'] = $val['name'];
                $data[$k]['value'] = $val['id'];
            }
        }
        $list = $this->_categorys($data);
        return $list;
    }

    // 分类列表
    public function _categorys($data, $pid='pid', $id='id'){
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
    // 添加平台素材页面
    public function add()
    {
        $categorys = $this->_newCategorys();
        $categorys = empty($categorys) ? array() : $categorys;
        $this->action = "add";
        $this->action_name = "添加精品课程套餐";
        $this->assign("info", json_encode((object)array()));
        $this->assign("options", json_encode($this->config['source']));
        $this->assign("positions", json_encode($this->config['position']));
        $this->assign("categorys", json_encode($categorys));
        $this->show("coursePackage/info");
    }


    public function addSave()
    {
        $input = $this->post;

        // 检测字段
        $res = $this->checkCourseFile($input, $_FILES,1);
        if (!empty($res)) {
            $this->ajaxReturn($res[0], $res[1]);
        }

        if (is_numeric($input['category'])) {
            $categorys_one_id = $input['category'];
        } else {
            $categorys = explode(",", $input['category']);
            $categorys_one_id = $categorys[0];
        }
        $images = imageUpload($_FILES['picFile'], "course_".time().$_SESSION[$this->config['SPOT'].'_user'], 1);
//        $subtitles = imageUpload($_FILES['subtitles'], "subtitles_".time().$_SESSION[$this->config['SPOT'].'_user']);
        $data = array(
            "title" => $input['title'],
            "image" => $images[0],
            "created_at" => time(),
            "updated_at" => time(),
//            "type" => $input['type'],
            "description" => $input['description'],
            "position" => empty($input['position']) ? "" : "," . $input['position'] . ",",
            "path" => "",
            "category_one_id" => $categorys_one_id,
            "category_two_id" => empty($categorys[1]) ? 0 : $categorys[1],
            "category_three_id" => empty($categorys[2]) ? 0 : $categorys[2],
//            "source_path" => empty($input['source_path']) ? "" : $input['source_path'],
//            "notice" => empty($input['notice']) ? "" : $input['notice'],
//            "words" => empty($input['words']) ? "" : $input['words'],
//            "answer" => empty($input['answer']) ? "" : $input['answer'],
//            "subtitles" => $subtitles[0],
            "view_count" => empty($input['view_count']) ? 0 : intval($input['view_count']),
            "buynum" => empty($input['buynum']) ? 0 : $input['buynum'],
            "price" => $input['price'] * 100,
        );

        // 保存到数据库
        $add = model("u")->data_add('good_course', $data);
        if ($add) {
            $this->ajaxReturn(200, '课程套餐添加成功！');
        }else{
            $this->ajaxReturn(202, '网络原因 请稍后重试！');
        }
    }

    // 编辑信息
    public function edit()
    {
        $siteurl = "http://".$this->config['siteurl'];
        $sid = intval($_GET['sid']);
        $info = $this->data_getinfo("good_course", " id = '" . $sid . "'");

        // 组装info 为了展示信息
        if (!empty($info)) {

            $info['words'] = htmlspecialchars_decode($info['words']);
            $info['notice'] = htmlspecialchars_decode($info['notice']);
            $info['description'] = htmlspecialchars_decode($info['description']);
            $info['answer'] = htmlspecialchars_decode($info['answer']);


            $info['type'] = (int)$info['type'];
            $fileList = array(array("name"=> $info['image'], "url" => $siteurl.$info['image']));
            $info['fileList'] = empty($info['image']) ? (string)"" : $fileList;
            $subtitles = array(array("name" => $info['subtitles'], "url" => $siteurl.$info['subtitles']));
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
            $sourceInfo = $this->data_list("good_course_info", "where id > 0 and source_id = '" . $sid . "'");
            $pathList = array();
            if (!empty($sourceInfo)) {
                foreach ($sourceInfo as $k => $val) {
                    $pathList[] = array(
                        "path" => $val['path'],
                        "subtitle" => $val['subtitle'],
                    );
                }
            }
            $info['pathList'] = $pathList;
            // textList
            $sourceText = $this->data_list("good_course_text", " where id > 0 and source_id = '" . $sid . "'");
            $textList = array();
            if (! empty($sourceText)) {
                foreach ($sourceText as $k => $val) {
                    $textList[] = array(
                        "content" => $val['content'],
                    );
                }
            }
            $info['textList'] = $textList;
            // 价格
            $info['price'] = sprintf("%01.2f", ($info['price'] / 100));
        }

        $categorys = $this->_newCategorys();
        $categorys = empty($categorys) ? array() : $categorys;
        $this->action = "edit";
        $this->action_name = "编辑课程素材";
        $this->assign("info", json_encode($info));
        $this->assign("options", json_encode($this->config['source']));
        $this->assign("positions", json_encode($this->config['position']));
        $this->assign("categorys", json_encode($categorys));
        $this->show("coursePackage/info");
    }

    // 编辑素材材料的时候
    public function editSave()
    {
        $input = $this->post;
        $siteurl = "http://".$this->config['siteurl'];
        if (empty($input['id'])) {
            $this->ajaxReturn(202, "参数错误，请刷新重试");
        }
        // 检测字段
        $res = $this->checkCourseFile($input, $_FILES,1);
        if (!empty($res)) {
            $this->ajaxReturn($res[0], $res[1]);
        }
        // 图片
        if (! empty($_FILES['picFile'])) {
            $images = imageUpload($_FILES['picFile'], "course_".time().$_SESSION[$this->config['SPOT'].'_user'], 1);
            $image = $images[0];
        } else {
            $image = str_replace($siteurl,"",$input['fileList']);
        }
        // 分类
        if (is_numeric($input['category'])) {
            $categorys_one_id = $input['category'];
        } else {
            $categorys = explode(",", $input['category']);
            $categorys_one_id = $categorys[0];
        }
        // 字幕文件
//        if (! empty($_FILES['subtitles'])) {
//            $subtitles = imageUpload($_FILES['subtitles'], "course_".time().$_SESSION[$this->config['SPOT'].'_user']);
//            $subtitles = $subtitles[0];
//        } else {
//            $subtitles = str_replace($siteurl,"",$input['subtitlesShow']);
//        }
        $data = array(
            "title" => $input['title'],
            "image" => $image,
            "updated_at" => time(),
//            "type" => $input['type'],
            "description" => $input['description'],
            "position" => empty($input['position']) ? "" : "," . $input['position'] . ",",
            "category_one_id" => $categorys_one_id,
            "category_two_id" => empty($categorys[1]) ? 0 : $categorys[1],
            "category_three_id" => empty($categorys[2]) ? 0 : $categorys[2],
//            "source_path" => empty($input['source_path']) ? "" : $input['source_path'],
//            "notice" => empty($input['notice']) ? "" : $input['notice'],
//            "words" => empty($input['words']) ? "" : $input['words'],
//            "answer" => empty($input['answer']) ? "" : $input['answer'],
//            "subtitles" => $subtitles,
            "view_count" => empty($input['view_count']) ? 0 : intval($input['view_count']),
            "buynum" => empty($input['buynum']) ? 0 : $input['buynum'],
            "price" => $input['price'] * 100,
        );

        // 编辑信息
        // 保存到数据库
        $edit = model("u")->data_edit('good_course', $data, ' id = ' . $input['id']);
        if ($edit) {
            $this->ajaxReturn(200, '课程套餐更新成功！');
        }else{
            $this->ajaxReturn(202, '网络原因 请稍后重试！');
        }
    }
}