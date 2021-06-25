<?php

/*
 * @心愿商城
 * @Version 1.0
 */

class newsMod extends commonMod {

    protected $tablename = 'u';
    public function __construct() {

        parent::__construct();
    }

    // 素材列表页面
    public function index() {
        $input = in($_GET);
        $where = " where id > 0 ";
        $where_url = "";
        $order = " order by id desc";

        if (!empty($input['title'])) {
            $where .= ' and `name` like "%' . $input['title'] . '%" ';
        }

        if (!empty($input['id'])) {
            $where .= ' and `sort_id` = '.$input['id'];
        }
        //分页处理
        $url = __URL__ . '/index/page-{page}'.$where_url.'.html'; //分页基准网址
        $listRows = 20;
        $limit = $this->pagelimit($url, $listRows);
        //内容列表
        $list = $this->plist("news", $limit, $where, $order);
        if (!empty($list)) {
            foreach($list as $k => $val) {
                $sortInfo = model("u")->data_getinfo("news_sort", " id = '" . $val['sort_id'] . "'");
                $list[$k]['created_at'] = date("Y-m-d H:i", $val['created_at']);
                $list[$k]['sortname'] = $sortInfo['name'];
            }
        }
        $options = model('u')->data_list("news_sort", " where id>0");
        //统计总内容数量
        $count = $this->pcount("news", $where);
        //分页处理
        $list = empty($list) ? array() : $list;
        $id = empty($input['id']) ? " " : (int)$input['id'];
        $this->assign("count", $count);
        $this->assign("id", $id);
        $this->assign("source", $input['title']);
        $this->assign("options", json_encode($options));
        $this->assign("list", json_encode($list));
        $this->assign("title", "平台资讯");
        $this->assign('page', $this->page($url, $count, $listRows));
        $this->show();
    }

    // 编辑信息
    public function edit()
    {
        $sid = in($_GET['sid']);
        // print_r($sid);
        $info = model("u")->data_getinfo("news", " id = '" . $sid . "'");
//                print_r('<pre>');
//        print_r($info);die;
        $info['hot'] = (int)$info['hot'];
        $info['content'] = htmlspecialchars_decode($info['content']);
        $arr2 = array();
        if (!empty($info['imgs'])) {
            $info['imgs'] = json_decode($info['imgs'], true);
            for($i=0; $i<count($info['imgs']); $i++) {
                $arr2[$i] = array(
                    "name" => $info['imgs'][$i],
                    "url" => "http://" . $this->config['siteurl'] . $info['imgs'][$i],
                );
            }
        }
        $info['imgs'] = $arr2;

        // 去掉首尾逗号
        $arr = array();
        if (!empty($info['position'])) {
            $info['position'] = ltrim($info['position'], ",");
            $info['position'] = rtrim($info['position'], ",");
            if (is_numeric($info['position'])){
                $arr[0] = (int)$info['position'];
            }else {
                $arr = explode(",", $info['position']);
            }
        }
        $hot = array(
            array("id" => 1, "name" => "非热门"),
            array("id" => 2, "name" => "热门"),
        );
        $typeList = array(
            array('id' => 1, 'name' => '视频'),
            array('id' => 2, 'name' => '音频')
        );
        $info['position'] = $arr;
        $info['type'] = (int)$info['type'];
        $info['path_title'] = $info['path_title'];
        $options = model('u')->data_list("news_sort", " where id>0");
        $this->assign("typeList", json_encode($typeList));
        $this->assign("positions", json_encode($hot));
        $this->assign("options", json_encode($options));
        $this->assign("info", json_encode($info));
        $this->assign("action_name", "编辑信息");
        $this->assign("action", "edit");
        $this->show("news/info");
    }

    public function editSave()
    {
        $input = in($_POST);
//        print_r($input['path_title']);die;
        if (empty($input['id'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        if (empty($input['title'])) {
            $this->ajaxReturn(202, "资讯标题不能为空");
        }

        if (empty($input['sort_id'])) {
            $this->ajaxReturn(202, "选择资讯分类");
        }

        if (empty($input['type'])) {
            $input['path'] = '';
        }

        if (empty($input['source_id'])) {
            $this->ajaxReturn(202, '请填写关联的素材ID');
        }

        if (!$info = $this->data_getinfo('source', ' id = "' . $input['source_id'] . '"')) {
            $this->ajaxReturn(202, '关联的素材不存在 请核对后重填！' . $input['source_id'] . $input['type'] . $input['path']);
        }

        if (empty($_FILES) && empty($input['arr'])) {
            $this->ajaxReturn(202, "请上传图片");
        }


        $count = count($_FILES);
        $arr = array();
        for($i = 0; $i < $count; $i++) {
            $f = time() . "_" . $i;
            $imgs = imageUpload($_FILES['imgs_'.$i], $f);
            $arr[$i] = $imgs[0];

        }

        $fileList = array();
        if (!empty($input['arr'])) {
            $fileList = explode(",", $input['arr']);
        }

        $totalFile = array_merge($fileList, $arr);

        if (! empty($input['hot'])) {
            $input['hot'] = $input['hot'] ;
        }

        if (empty($input['content'])) {
            $this->ajaxReturn(202, "请填写资讯详情");
        }

        $data = array(
            "title" => $input['title'],
            "imgs" => json_encode($totalFile),
            "content" => $input['content'],
            "created_at" => time(),
            "sort_id" => $input['sort_id'],
            "hot" => $input['hot'],
            "sort" => $input['sort'] ? :0,
            "description" => $input['description'],
            'type' => $input['type'],
            'path' => $input['path'],
            'path_title' => $input['path_title'] ? $input['path_title']:'',
            'source_id' => $input['source_id'],
        );

        $addInfo = $this->data_edit("news", $data, " id = '" . $input['id'] . "'");
        if (empty($addInfo)) {
            $this->ajaxReturn(202, "网络原因刷新重试");
        }else {
            $this->ajaxReturn(200, "新闻编辑成功");
        }
    }
    public function add()
    {
        $hot = array(
            array("id" => 1, "name" => "非热门"),
            array("id" => 2, "name" => "热门"),
        );
        $typeList = array(
            array('id' => 1, 'name' => '视频'),
            array('id' => 2, 'name' => '音频')
        );
        $options = model('u')->data_list("news_sort", " where id>0");
        $this->assign("typeList", json_encode($typeList));
        $this->assign("options", json_encode($options));
        $this->assign("positions", json_encode($hot));
        $this->assign("action_name", "添加信息");
        $this->assign("action", "add");
        $this->show("news/info");
    }
    public function addSave()
    {
        $input = in($_POST);

        if (empty($input['title'])) {
            $this->ajaxReturn(202, "资讯标题不能为空");
        }

        if (empty($input['sort_id'])) {
            $this->ajaxReturn(202, "选择资讯分类");
        }

        if (empty($input['type'])) {
            $input['path'] = '';
        }

        if (empty($input['source_id'])) {
            $this->ajaxReturn(202, '请填写关联的素材ID');
        }

        if (!$info = $this->data_getinfo('source', ' id = "' . $input['source_id'] . '"')) {
            $this->ajaxReturn(202, '关联的素材不存在 请核对后重填！' . $input['source_id'] . $input['type'] . $input['path']);
        }

        if (empty($_FILES)) {
            $this->ajaxReturn(202, "请上传图片");
        }


        $count = count($_FILES);
        $arr = array();
        for($i = 0; $i < $count; $i++) {
            $f = time() . "_" . $i;
            $imgs = imageUpload($_FILES['imgs_'.$i], $f);
            $arr[$i] = $imgs[0];

        }

        if (empty($input['description'])) {
            $this->ajaxReturn(202, '请填写资讯描述');
        }

        if (empty($input['content'])) {
            $this->ajaxReturn(202, "请填写资讯详情");
        }
        $data = array(
            "title" => $input['title'],
            "imgs" => json_encode($arr),
            "content" => $input['content'],
            "created_at" => time(),
            "sort_id" => $input['sort_id'],
            "hot" => $input['hot'],
            "sort" => $input['sort'] ? :0,
            "description" => $input['description'],
            "type" => $input['type'],
            "source_id" => $input['source_id'],
            "path" => $input['path'],
            'path_title' => $input['path_title'] ? :'',

        );

        $addInfo = $this->data_add("news", $data);
        if (empty($addInfo)) {
            $this->ajaxReturn(202, "网络原因刷新重试");
        }else {
            $this->ajaxReturn(200, "商品上传成功");
        }
    }
    public function uploadImage()
    {
        $url = "http://" . $this->config['siteurl'];
        $input = $_FILES['file'];
        if (empty($input)) {
            $this->ajaxReturn(202, "图片上传错误");
        }

        $imgs = imageUpload($input, $path);
        $image = $imgs[0];
        $param = array("image" => $url . $image);

        $this->ajaxReturn(200, "图片上传成功", $param);
    }
}