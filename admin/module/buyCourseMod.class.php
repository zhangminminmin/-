<?php

/*
 * @购买会员财务统计
 * @Version 1.0
 */

class buyCourseMod extends commonMod {
    
    protected $tablename = 'u';
    public function __construct() {
        
        parent::__construct();
    }

    // 
    public function index() {
        $input = in($_GET);
        $where = " where o.id > 0 and o.status = 2 ";
        $where_url1 = "?1-1";
        $order = " order by o.id desc";

        if (!empty($input['stime'])) {
            $stime = strtotime($input['stime']);
            $where .= ' and o.created_at >= "' . $stime . '"';
            $where_url1 .= '&stime=' . $input['stime'];
        }

        if (!empty($input['etime'])) {
            $etime = strtotime($input['etime']);
            $where .= ' and o.created_at <= "' . $etime . '"';
            $where_url1 .= '&etime=' . $input['etime'];
        }

        //分页处理
        $url = __URL__ . '/index/page-{page}'.$where_url.'.html'; //分页基准网址
        $listRows = 20;
        $limit = " limit " . $this->pagelimit($url, $listRows);
        //内容列表
        $list = $this->buyVipList($where, $order, $limit);
        $money = 0;
        if (!empty($list)) {
            foreach ($list as $k => $val) {
                $list[$k]['price'] = sprintf("%.2f", $val['price'] / 100);
                $list[$k]['created_at'] = date("Y-m-d H:i", $val['created_at']);
            }
        }
        //统计总内容数量
        $count = $this->buyVipCount($where);
        //分页处理
        $input['stime'] = empty($input['stime']) ? "" : $input['stime'];
        $input['etime'] = empty($input['etime']) ? "" : $input['etime'];

        $moneyAll = $this->buyVipList($where, $order,'');
        if (!empty($moneyAll)) {
            foreach ($moneyAll as $k => $val) {
                $money = $money + $val['price'];
            }
        }
        $money = sprintf("%.2f", $money / 100);
        $this->assign("stime", $input['stime']);
        $this->assign("etime", $input['etime']);
        $this->assign("count", $count);
        $this->assign("list", json_encode($list));
        $this->assign("title", "购买课程财务统计");
        $this->assign("money", $money);
        $this->assign('page', $this->page($url, $count, $listRows));
        $this->show();
    }


    // 
    public function  buyVipList($where, $order, $limit)
    {
        $sql = "SELECT o.*, u.mobile FROM {$this->model->pre}order AS o ".
               "LEFT JOIN {$this->model->pre}user AS u ON o.user_id = u.id ".
               "{$where} {$order} {$limit}";

        $data = $this->model->query($sql);
        return empty($data) ? array() : $data;
    }


    public function  buyVipCount($where)
    {
        $sql = "SELECT COUNT(1) AS num FROM {$this->model->pre}order AS o ".
               "LEFT JOIN {$this->model->pre}user AS u ON o.user_id = u.id ".
               "{$where}";

        $data = $this->model->query($sql);
        return empty($data) ? 0 : $data[0]['num'];
    }



}