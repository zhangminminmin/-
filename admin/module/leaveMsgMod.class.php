<?php

/*
 * @用户联系我们 留言
 * @Version 1.0
 */

class leaveMsgMod extends commonMod {
    
    protected $tablename = 'u';
    public function __construct() {
        
        parent::__construct();
    }

    // 素材列表页面
    public function index() {
        $input = $this->get;
        $where = " where id > 0 ";
        $where_url = "";
        $order = " order by id desc";

        //分页处理
        $url = __URL__ . '/index/page-{page}'.$where_url.'.html'; //分页基准网址
        $listRows = 20;
        $limit = $this->pagelimit($url, $listRows);
        //内容列表
        $list = $this->plist("message", $limit, $where, $order);
        $message = array();
        if (!empty($list)) {
            foreach($list as $k => $val) {
                $message[] = array(
                    "id" => $val['id'],
                    "mobile" => $val['mobile'],
                    "username" => $val['username'],
                    "content" => $val['content'],
                    "reply" => $val['reply'] ? :"--",
                    "created_at" => date("Y-m-d H:i", $val['created_at']),
                );
            }
        }
        //统计总内容数量
        $count = $this->pcount("message", $where);
        //分页处理
        $this->assign("count", $count);
        $this->assign("list", json_encode($message));
        $this->assign("title", "用户留言列表");
        $this->assign('page', $this->page($url, $count, $listRows));
        $this->show();
    }
    
    // 删除信息
    public function del()
    {
        $input = $this->post;
        if (intval($input['id']) <= 0) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        $del = $this->data_del("message", " id = '" . $input['id'] . "'");
        if (empty($del)) {
            $this->ajaxReturn(202, "网络原因请刷新重试");
        } else {
            $this->ajaxReturn(200, "删除成功");
        }
    }

    // 回复信息
    public function hf()
    {
        $input = in($_POST);
        if (empty($input['id'])) {
            $this->ajaxReturn(202, '参数错误 请刷新重试！');
        }

        if (empty($input['name'])) {
            $this->ajaxReturn(202, '评价内容不能为空！');
        }

        $data = array(
            'reply' => $input['name'],
            'reply_at' => time(),
        );
        $del = $this->data_edit('message', $data, ' id =' . $input['id']);
        $this->ajaxReturn(200, '信息回复成功！');
    }

}