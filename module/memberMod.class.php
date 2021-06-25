<?php

class memberMod extends commonMod {
	
	protected $tablename = 'member';

	public function __construct() {
        parent::__construct();
    }
    
    // 个人信息浏览页面
	public function index() {
		
		$info = model($this->tablename)->data_getinfo(1);
		echo $info['name'];exit;
		
        // 输出页面
		$this->display("member.html");
	}
}