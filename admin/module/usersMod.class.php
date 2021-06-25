<?php

/*
 * @会员管理页面
 * @Version 1.0
 */

class usersMod extends commonMod {
    
    protected $tablename = 'u';
    public function __construct() {
        
        parent::__construct();
    }

    // 素材列表页面
    public function index() {
        $input = $this->get;
        $where = " where id > 0 ";
        $where_url1 = "?1-1";
        $order = " order by id desc";

        if (!empty($input['mobile'])) {
            $where .= ' and `mobile` like "%' . $input['mobile'] . '%" ';
            $where_url1 .= '&mobile=' . $input['mobile'];
        }

        if (!empty($input['nickname'])) {
            $where .= ' and `nickname` like "%' . $input['nickname'] . '%" ';
            $where_url1 .= '&nickname=' . $input['nickname'];
        }

        if (!empty($input['value'])) {
            if ($input['value'] == 1) {
                $where .= ' and `type`  > 1';
            }else{
                $where .= ' and `type` < 2 ';
            }
            $where_url1 .= '&value=' . $input['value'];
        }

        //分页处理
        $url = __URL__ . '/index/page-{page}'.$where_url.'.html' . $where_url1; //分页基准网址
        $listRows = 20;
        $limit = $this->pagelimit($url, $listRows);
        //内容列表
        $list = $this->plist("user", $limit, $where, $order);
        if (!empty($list)) {
            foreach ($list as $k => $val) {
                if ($val['type'] == 1) {
                    $typeName = "普通会员";
                } else if ($val['type'] == 2) {
                    $typeName = "猩听译会员";
                }else {
                    $typeName = "终身会员";
                }
                $list[$k]['typeName'] = $typeName;
            }
        }
        $input['mobile'] = empty($input['mobile']) ? "" : $input['mobile'];
        //统计总内容数量
        $count = $this->pcount("user", $where);
        //分页处理
        $this->assign("count", $count);
        $this->assign("list", json_encode($list));
        $this->assign("mobile", $input['mobile']);
        $this->assign("nickname", $input['nickname']);
        $this->assign("title", "会员管理");
        $this->assign('page', $this->page($url, $count, $listRows));
        $this->show();
    }


    // 设置猩听译会员
    public function setUserVip()
    {
        $input = in($_POST);
        if (intval($input['id']) <= 0) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        if (intval($input['yf']) <= 0) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        if ($input['yf'] == 1) {
            if (! is_numeric($input['months']) || empty($input['months'])) {
                $this->ajaxReturn(202, "月份必须为数字且不为空");
            }

            $res = $this->setUserLevel($input['id'], $input['months']);
            if ($res[0] == 202) {
                $this->ajaxReturn(202, $res[1]);
            }else {
                $this->ajaxReturn(200, $res[1]);
            }

        } else {
            $data = array("type" => 3);
            $edit = model("u")->data_edit("user", $data, " id = '" . $input['id'] . "'");
            if (empty($edit)) {
                $this->ajaxReturn(202, "参数错误请刷新重试");
            } else {
                $this->ajaxReturn(200, "会员设置成功");
            }
        }

    }


    public function  setUserLevel($id, $months)
    {
        $userinfo = model("u")->data_getinfo("user", " id = '" . $id . "'");
        if ($userinfo['type'] == 3) {
            return array(202, "已经是终身会员，无需在操作");
        }

        if ($userinfo['endtime'] > time()) {
            $endtime = strtotime("+" . $months . " months", $userinfo['endtime']);
        } else {
            $endtime = strtotime("+" . $months . " months", time());
        }
        $data = array("type" => 2, "endtime" => $endtime);
        $edit = model("u")->data_edit("user", $data, " id = '" . $id . "'");

        if (empty($edit)) {
            return array(202, "参数错误请刷新重试");
        } else {
            return array(200, "会员设置成功");
        }
    }

    // 取消永久会员设置
    public function unsetUserVip() {
        $id = in($_POST['id']);
        if (empty($id)) {
            $this->ajaxReturn(202, '参数错误 请刷新重试！');
        }

        $info = model('u')->data_getinfo('user', ' id = ' . $id);
        if (empty($info)) {
            $this->ajaxReturn(202, '会员参数出错！');
        }

        if ($info['type'] != 3) {
            $this->ajaxReturn(202, '此会员不是终身会员 暂时无法操作');
        }

        $data = array(
            'type' => 1,
            'endtime' => 0,
        );

        $edit = model('u')->data_edit('user', $data, ' id = ' . $id);
        $this->ajaxReturn(200, '终身会员取消成功！');
    }

}