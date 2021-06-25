<?php

/*
 * @平台素材分类列表
 * @Author  Jeff<jeff.chou@aliyun.com>    2016-9-21
 * @Version 1.0
 */

class sourceSortMod extends commonMod {
    
    protected $tablename = 'u';

    public function __construct() {
        
        parent::__construct();
    }
    // 辅助函数
    public function newCategorys()
    {
        $data = model("u")->data_list("source_category", "where id > 0");
        if (! empty($data)) {
            foreach ($data as $k => $val) {
                $data[$k]['label'] = $val['name'];
            }
        }
        $list = $this->categorys($data);
        return $list;
    }
    // 平台素材分类列表
    public function index() {
        $data = model("u")->data_list("source_category", "where id > 0");
        if (! empty($data)) {
            foreach ($data as $k => $val) {
                $data[$k]['label'] = $val['name'];
            }
        }
        $list = $this->categorys($data);
        $this->assign("list", $list);
        //分页处理
        $this->assign("title", "平台素材分类");
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
        return $tree;
    }

    /**
     * id 编辑的信息id
     * name 编辑的分类名称 name
     */
    public function editSave()
    {
        $input = $this->post;
        if (empty($input['id'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        if (empty($input['name'])) {
            $this->ajaxReturn(202, "分类名称不能为空");
        }

        $info = $this->data_getinfo("source_category", " id = '" . $input['id'] . "'");
        if (empty($info)) {
            $this->ajaxReturn(202, "此分类不存在或者已经被删除");
        }

        $categoryInfo = $this->data_getinfo("source_category", " id != '" . $input['id'] . "' and pid = '" . $info['pid'] . "' and name = '" . $input['name'] . "'");
        if (!empty($categoryInfo)) {
            $this->ajaxReturn(202, "此等级下分类已经存在");
        }

        $data = array(
            "name" => $input['name'],
        );

        $editCategory = $this->data_edit("source_category", $data, " id='" . $input['id'] . "'");
        $list = $this->newCategorys();
        if (empty($editCategory)) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        } else {
            $this->ajaxReturn(200, "分类编辑成功", $list);
        }
    }

    /**
     * 添加分类
     * id 添加分类上级的id
     * name 添加分类的名称
     */
    public function addSave()
    {
        $input = $this->post;
        $pid = intval($input['id']) > 0 ?  $input['id'] : 0;
        if (empty($input['name'])) {
            $this->ajaxReturn(202, "分类名称不能为空");
        }

        // 检查是否存在
        $info = $this->data_getinfo("source_category", " pid = '" . $pid . "' and  name = '" . $input['name'] . "'");
        if (!empty($info)) {
            $this->ajaxReturn(202, "次分类已经存在请勿重复添加");
        }

        $data = array(
            "pid"  => $pid,
            "name" => $input['name'],
            "created_at" => time(),
        );

        $addCategory = $this->data_add("source_category", $data);
        $list = $this->newCategorys();
        if (empty($addCategory)) {
            $this->ajaxReturn(202, "网络错误请刷新重试");
        } else {
            $this->ajaxReturn(200, "添加分类成功", $list);
        }
    }

    /**
     * 删除分类接口
     */
    public function delCategory()
    {
        $input = $this->post;
        if (intval($input['id']) <= 0) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        $where = "where id >0 and (category_one_id = '" . $input['id'] . "' or category_two_id = '" . $input['id'] . "' or category_three_id = '" . $input['id'] . "')";
        $sourceInfo = $this->data_list("source", $where);
        if (!empty($sourceInfo)) {
            $this->ajaxReturn(202, "此分类下面已经有素材 无法删除");
        }

        // 查看下面是否有分类
        $cateInfo = $this->data_getinfo("source_category", " pid = '" . $input['id'] . "'");
        if (! empty($cateInfo)) {
            $this->ajaxReturn(202, "此分类下面存在子分类 请先删除子分类");
        }

        $del = $this->data_del("source_category", " id = '" . $input['id'] . "'");
        $list = $this->newCategorys();
        if (empty($del)) {
            $this->ajaxReturn(202, "网络原因请刷新重试");
        } else {
            $this->ajaxReturn(200, "删除成功", $list);
        }
    }
}