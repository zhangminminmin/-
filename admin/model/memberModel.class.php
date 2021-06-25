<?php
// 会员列表
class memberModel extends commonModel {

	public function __construct() {
		
        parent::__construct('member');
    }

    // 关注会员列表
    public function pic_list($limit, $where = '') {
        $sql = "
        SELECT *
        FROM {$this->model->pre}member ".$where."
        ORDER BY id DESC
        LIMIT {$limit} 
        ";
        return $this->model->query($sql);
    }
    
    // 关注会员总数
    public function pic_count($where = '') {
        $sql = "
        SELECT count(1) as num
        FROM {$this->model->pre}member ".$where;
		
        $data = $this->model->query($sql);
        return $data[0]['num'];
    }
    
    // 根据条件更新数据
    public function up_member($wh, $data) {

        return $this->model->table('member')->data($data)->where($wh)->update();
    }
    
    // 返回会员数据
    public function get_member($id) {

        return $this->model->table('member')->where(array('id' => $id))->find();
    }
}