<?php

//内容显示
class showinfoMod extends commonMod {

    public function __construct() {
        parent::__construct();
    }

    public function index() {
        $id = intval($_GET['aid']);
        if (empty($id)) {
            $this->tname = '非法操作';
            $this->err = '参数错误#0！';
            $this->display('err.html');
            exit();
        }
        $this->info = model('content')->text_info($id);
        model('content')->text_views_content($id, $this->info['click']);
        $this->display('show.html');
    }

}

?>