<?php

/*
 * @关注回复管理后台控制器
 * @Copyright  ahaiba.com
 * @Author  Jeff<jeffchou@qq.com>    2016-9-21
 * @Version 1.0
 */

class wx_textMod extends commonMod {

    public function __construct() {
        parent::__construct();
    }

    public function index() {
        if (!model('user_group')->model_power('wx_text', 'visit')) {
            $this->msg('没有权限！', 0);
        }
        //$id = intval($_GET['id']);
        //  $this->action_name = '提成比例修改';
        $this->action = 'edit';
        //$this->city = model('project_type')->project_type_list();
        $this->info = model('wx')->wx_text_getinfo();
        $this->show('wx_text/index');
    }

    public function edit_save() {
        if (!model('user_group')->model_power('wx_text', 'edit')) {
            $this->msg('没有权限！', 0);
        }
        $id = intval($_POST['id']);
        //$value = $_POST['value'];

        if (empty($id)) {
            $this->msg('参数出错！', 0);
        }
        model('wx')->wx_text_edit($id, $_POST);
        $this->msg('修改成功！', 1);
    }

}