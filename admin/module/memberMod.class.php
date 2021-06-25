<?php

/*
 * @关注粉丝管理后台控制器
 * @Copyright  ahaiba.com
 * @Author  Jeff<jeff.chou@aliyun.com>    2016-9-21
 * @Version 1.0
 */

class memberMod extends commonMod {
	
	protected $tablename = 'member';

    public function __construct() {
		
        parent::__construct();
    }
    
    // Jeff 关注粉丝首页
    public function index() {
		
        if (!model('user_group')->model_power($this->tablename, 'visit')) {
            $this->msg('没有权限！', 0);
        }
        if (model('user_group')->model_power($this->tablename, 'edit')) {
            $this->edit_power = true;
        }
        $_GET['search'] = iconv("gbk","utf-8",$_GET['search']);  
        $search = in(urldecode($_GET['search']));
        if (!is_utf8($search)) {
            $search = auto_charset($search);
        }
        if (!empty($search)) {
            $where= ' where `name` like "%' . $search . '%" ';
            $where_url = '-search-' . urlencode($search);
        }

        //分页处理
        $url = __URL__ . '/index/page-{page}'.$where_url.'.html'; //分页基准网址
        $listRows = 20;
        $limit = $this->pagelimit($url, $listRows);
        //内容列表
        $this->list = model($this->tablename)->pic_list($limit, $where);
        //统计总内容数量
        $count = model($this->tablename)->pic_count($where);
        $this->count = $count;
        //分页处理
        $this->assign('page', $this->page($url, $count, $listRows));
        $this->show();
    }
    
    // 所有用户 同步信息
    public function tongbu() {

        $access_token = getAccessToken($this->config['WX_appid'], $this->config['WX_appsecret']);
        if (!$access_token) {
            $this->msg('获取access_token发生错误', 0);
        }
        
        $fansCount = model($this->tablename)->pic_count();
        $i = intval($_GET['i']);
        $step = 20;
        $limit = $i . ',' . $step;
        $fans = model($this->tablename)->pic_list($limit);
        if ($fans) {
            foreach ($fans as $data_all) {
                $wx_url = 'https://api.weixin.qq.com/cgi-bin/user/info?openid=' . $data_all['openid'] . '&access_token=' . $access_token;
                $classData = json_decode(httpGet($wx_url));
				
                if ($classData->subscribe == 1) {
                    $data['name'] = str_replace(array("'", "\\"), array(''), $classData->nickname);
                    $data['sex'] = $classData->sex == 1 ? "男" : "女";
                    $data['city'] = $classData->city;
                    $data['province'] = $classData->province;
                    $data['avatar'] = $classData->headimgurl;
                    $data['att_date'] = $classData->subscribe_time;
                    $data['status'] = 1;
                    
                    model($this->tablename)->up_member(array('id' => $data_all['id']), $data);
                } else {
                    model($this->tablename)->up_member(array('id' => $data_all['id']), array('status' => 0));
                }
            }
            $i = $step + $i;
            $this->success('更新中请勿关闭...进度：' . $i . '/' . $fansCount, '/admin/index.php/member/tongbu/i-' . $i, 1);
        } else {
            $this->success('更新完毕', '/admin/index.php/member/');
            exit();
        }
    }
    
    // 单个用户 同步信息
    public function dtongbu() {


        $access_token = getAccessToken($this->config['WX_appid'], $this->config['WX_appsecret']);
        if (!$access_token) {
            $this->msg('获取access_token发生错误', 0);
        }

        $id = intval($_GET['id']);
        $fans = model($this->tablename)->get_member($id);
        
        if ($fans) {
            
            // 根据openid和凭证获取用户信息
            $wx_url = 'https://api.weixin.qq.com/cgi-bin/user/info?openid=' . $fans['openid'] . '&access_token=' . $access_token;
            $classData = json_decode(httpGet($wx_url));
            
            if ($classData->subscribe == 1) {
                $data['name'] = str_replace(array("'", "\\"), array(''), $classData->nickname);
                $data['sex'] = $classData->sex == 1 ? "男" : "女";
                $data['city'] = $classData->city;
                $data['province'] = $classData->province;
                $data['avatar'] = $classData->headimgurl;
                $data['att_date'] = $classData->subscribe_time;
                $data['status'] = 1;
                
                //$url3 = 'https://api.weixin.qq.com/cgi-bin/groups/getid?access_token=' . $access_token;
                //$json2 = json_decode(httpGet($url3, 'post', '{"openid":"' . $fans['openid'] . '"}'));
                
                model($this->tablename)->up_member(array('id' => $fans['id']), $data);
            } else {
                model($this->tablename)->up_member(array('id' => $fans['id']), array('status' => 0));
            }


            $this->success('更新完毕');
            exit();
        }
    }

}