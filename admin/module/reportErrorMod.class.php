<?php

/*
 * @文章纠错 
 * @Version 1.0
 */

class reportErrorMod extends commonMod {
    
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
        $list = $this->plist("report_error", $limit, $where, $order);
        $message = array();
        if (!empty($list)) {
            foreach($list as $k => $val) {
                if ($val['type'] == 1) {
                    $sourceInfo = $this->data_getinfo("source", " id = '" . $val['source_id'] . "'");
                } else {
                    $sourceInfo = $this->data_getinfo("good_course", " id = '" . $val['source_id'] . "'");
                }
                $message[] = array(
                    "id" => $val['id'],
                    "title" => empty($sourceInfo['title']) ? "/" : $sourceInfo['title'],
                    "linkstyle" => $val['linkstyle'],
                    "source_id" => $val['source_id'],
                    "content" => $val['content'],
                    "reply" => $val['reply'] ? :'--',
                    "type_name" => $val['type'] == 1 ? "平台素材" : "课程素材",
                    "created_at" => date("Y-m-d H:i", $val['created_at']),
                );
            }
        }
        //统计总内容数量
        $count = $this->pcount("report_error", $where);
        //分页处理
        $this->assign("count", $count);
        $this->assign("list", json_encode($message));
        $this->assign("title", "文章纠错列表");
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

        $del = $this->data_del("report_error", " id = '" . $input['id'] . "'");
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
        $del = $this->data_edit('report_error', $data, ' id =' . $input['id']);
        $this->ajaxReturn(200, '信息回复成功！');
    }
}