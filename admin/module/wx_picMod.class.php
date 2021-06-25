<?php

/*
 * @项目管理后台控制器
 * @Copyright  ahaiba.com
 * @Author  Jeff<jeffchou@qq.com>    2016-9-21
 * @Version 1.0
 */

class wx_picMod extends commonMod {

    public function __construct() {
        parent::__construct();
    }

    public function index() {
        if (!model('user_group')->model_power('wx_pic', 'visit')) {
            $this->msg('没有权限！', 0);
        }
        if (model('user_group')->model_power('wx_pic', 'edit')) {
            $this->edit_power = true;
        }
        if (model('user_group')->model_power('wx_pic', 'del')) {
            $this->del_power = true;
        }
        if (model('user_group')->model_power('wx_pic', 'add')) {
            $this->add_power = true;
        }
        //分页处理
        $url = __URL__ . '/index/page-{page}.html'; //分页基准网址
        $listRows = 50;
        $limit = $this->pagelimit($url, $listRows);
        //内容列表
        $this->list = model('wx')->pic_list($limit);
        //统计总内容数量
        $count = model('wx')->pic_count();
        //分页处理
        $this->assign('page', $this->page($url, $count, $listRows));
        $this->show();
    }

    public function add() {//修改属性值
        $type = intval($_GET['type']);
        if ($type == 1) {
            $this->action_name = '新增图文自定义回复';
        } else {
            $this->action_name = '新增文本自定义回复';
        }
        $info['type'] = $type;
        $this->info = $info;
        $this->action = 'add';
        $this->show('wx_pic/info');
    }

    public function add_save() {
        if (!model('user_group')->model_power('wx_pic', 'add')) {
              $this->msg('没有权限！', 0);
        }

        $type = intval($_POST['type']);
        if (empty($_POST) || empty($type)) {
            $this->msg('参数出错！', 0);
        }

        model('wx')->wx_picadd($_POST);
        $this->msg('添加成功！', 1);
    }

    public function edit() {//修改属性值
        $id = intval($_GET['id']);
        $type = intval($_GET['type']);
        if ($type == 1) {
            $this->action_name = '新增图文自定义回复';
        } else {
            $this->action_name = '新增文本自定义回复';
        }
        $this->action = 'edit';

        $this->info = model('wx')->wx_pic_getinfo($id);
        $this->show('wx_pic/info');
    }

    public function edit_save() {
        if (!model('user_group')->model_power('wx_pic', 'edit')) {
            $this->msg('没有权限！', 0);
        }
        $id = intval($_POST['id']);
        //$value = $_POST['value'];
        $type = intval($_POST['type']);
        if (empty($id) || empty($type)) {
            $this->msg('参数出错！', 0);
        }

        model('wx')->wx_pic_edit($id, $_POST);
        $this->msg('修改成功！', 1);
    }

    public function del() {
        if (!model('user_group')->model_power('wx_pic', 'del')) {
             $this->msg('没有权限！', 0);
        }
        $id = intval($_POST['id']);
        $this->alert_str($id, 'int', true);


        model('wx')->pic_del($id);
        $this->msg('删除成功！', 1);
    }

}