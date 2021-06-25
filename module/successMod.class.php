<?php

class successMod extends commonMod {

	public function __construct()
    {
        parent::__construct();
    }
    
	public function index() {
		
		$this->back_url = $_GET['url'];
		$this->success_msg = $_GET['msg'];
		
        // 输出页面
		$this->display("success.html");
	}
}