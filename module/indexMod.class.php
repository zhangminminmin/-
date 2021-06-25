<?php
class indexMod extends commonMod {

	public function __construct() {
		
        parent::__construct();
		
		// 判断是否登陆
		//$this->is_login();
    }

	public function index() {
		
		/*hook*/
        $this->plus_hook('index','index');
        /*hook end*/
		
		$this->display($this->config['TPL_INDEX']);
	}

	public function test()
	{
		print_r("<pre>");
		$type = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
		
		print_r($_FILES['image']);die;
	}
	
	public function to_excel() {
		
		$header = array('姓名', '手机');
		
		$data[0] = array('陈莉', '18156031003');
		$data[1] = array('李磊', '15395084069');
		$data[2] = array('王璟璟', '15212417428');
		$data[3] = array('周倩倩', '15856980395');
		$data[4] = array('柳杨', '18919687850');
		
 		data_to_excel($header, $data, '技术部通讯录');
	}
	
	public function emailSend() {
		include_once($_SERVER['DOCUMENT_ROOT'] . '/system/ext/Email.class.php');
		$E = new Email();
		$content = '测试邮件';
		$E->send('zhoujf@ahaiba.com', '邮件测试标题', $content);
	}
}