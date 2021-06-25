<?php

/*
 * @微信模型
 * @Copyright  ahaiba.com
 * @Author  Jeff<jeffchou@qq.com>    2016-9-21
 * @Version 1.0
 */

class weixinModel extends commonMod {

    public function __construct() {
        parent::__construct();
    }
	
	// Jeff 添加用户关注
    public function add_member($data) {
        
        $data['status'] = 1;
        $info = $this->get_member($data['openid']);
        
        if (empty($info)) {
            
			// 如果是第一次关注，则插入用户数据
            $new_id = $this->model->table('member')->data($data)->insert();
			return $new_id;
            
        } else {
            
			// 重新关注后更新数据
			$this->model->table('member')->data($data)->where(array('openid' => $data['openid']))->update();
            return $info['id'];
            
        }
    }
	
	// Jeff 根据openid返回用户信息
    public function get_member($openid) {

        return $this->model->table('member')->where(array('openid' => $openid))->find();
    }
    
    // Jeff 更新关注者信息
    public function update_member($data, $openid) {
        return $this->model->table('member')->data($data)->where(array('openid' => $openid))->update(); //录入基本信息
    }
	
	// Jeff 返回关注后的欢迎消息
    public function areply($wh) {
        return $this->model->table('areply')->find();
    }
	
	// Jeff 根据关键字匹配回复信息
    public function keyword($wh) {
        return $this->model->table('keyword')->where($wh)->order('id desc')->find();
    }
    
    // Jeff 图文消息回复
    public function img($wh) {
        return $this->model->table('img')->where($wh)->field('id,text,pic,url,title')->limit(9)->order('sort desc')->select();
    }

    // Jeff 文本消息回复
    public function text($wh) {
        return $this->model->table('img')->where($wh)->order('sort desc')->find();
    }
}
?>