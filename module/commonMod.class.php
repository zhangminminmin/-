<?php
//公共类
class commonMod
{
    protected $model = NULL; //数据库模型
    protected $layout = NULL; //布局视图
    protected $config = array();
    private $_data = array();
	protected $tablename = NULL;
    
    protected $post = array();
    protected $get = array();
    protected $siteurl = "";

    protected function init(){}
    
    public function __construct($tablename = ''){
        global $config;
        @session_start();

        if(!file_exists('data/install.lock'))
        {
            $this->redirect(__ROOT__.'/install/');
        }

        $config['PLUGIN_PATH']=__ROOTDIR__.'/plugins/';
        $this->config = $config;
        $this->model = self::initModel( $this->config);
        $this->init();
        Plugin::init();
        $langCon=Lang::langCon();
        $this->config = array_merge((array)$config,(array)$langCon);
        if($config['LANG_OPEN']){
            define('__INDEX__', __APP__.'/'.__LANG__);
        }else{
            define('__INDEX__', __APP__);
        }
		
		if(!empty($tablename)) {
			$this->tablename = $tablename;
		}

        $this->post = in($_POST);
        $this->get = in($_GET);
        $this->siteurl = "https://" . $this->config['siteurl'];
        include_once __ROOTDIR__ . '/vendor/autoload.php';
    }

    public function _empty() {
        $this->error404();
    }

    //初始化模型
    static public function initModel($config){
        static $model = NULL;
        if( empty($model) ){
            $model = new cpModel($config);
        }
        return $model;
    }

    public function __get($name){
        return isset( $this->_data[$name] ) ? $this->_data[$name] : NULL;
    }

    public function __set($name, $value){
        $this->_data[$name] = $value;
    }

    //获取模板对象
    public function view(){
        static $view = NULL;
        if( empty($view) ){
            $view = new cpTemplate( $this->config );
        }
        return $view;
    }
    
    //模板赋值
    protected function assign($name, $value){
        return $this->view()->assign($name, $value);
    }

    public function return_tpl($content){
        return $this->display($content,true,false);
    }

    //模板显示
    protected function display($tpl = '', $return = false, $is_tpl = true,$is_dir=true){
        if($this->config['LANG_OPEN']){
            $lang=__LANG__.'/';
        }
        if(MOBILE){
            $mobile_tpl='mobile'.'/';
        }
        if( $is_tpl){
            $tpl=__ROOTDIR__.'/'.$this->config['TPL_TEMPLATE_PATH'].$lang.$mobile_tpl.$tpl;
            if( $is_tpl && $this->layout ){
                $this->__template_file = $tpl;
                $tpl = $this->layout;
            }
        }

        $this->assign('model', $this->model);
        $this->assign('sys', $this->config);
        $this->assign('config', $this->config);
        $this->view()->assign( $this->_data);
        return $this->view()->display($tpl, $return, $is_tpl,$is_dir);
    }

    //页面不存在
    protected function error404()
    {
        header('HTTP/1.1 404 Not Found');
        header('Status: 404 Not Found');
        $this->common=model('pageinfo')->media('您要查找的页面不存在');
        $this->display('404.html');
        exit;
    }

    //包含内模板显示
    protected function show($tpl = ''){
        $content=$this->display($tpl,true);
        $body=$this->display($this->config['TPL_COMMON'],true);
        $html=str_replace('<!--body-->', $content, $body);
        echo $html;
    }

    //脚本运行时间
    public function runtime(){
    $GLOBALS['_endTime'] = microtime(true);
        $runTime = number_format($GLOBALS['_endTime'] - $GLOBALS['_startTime'], 4);
        echo $runTime;
    }


    //判断是否是数据提交 
    protected function isPost(){
        return $_SERVER['REQUEST_METHOD'] == 'POST';
    }

    //直接跳转
    protected function redirect($url)
    {
        header('location:' . $url, false, 301);
        exit;
    }

    //操作成功之后跳转,默认三秒钟跳转
    protected function success($msg, $url = null, $waitSecond = 3)
    {
        if ($url == null)
            $url = __URL__;
        $this->assign('message', $this->getlang($msg));
        $this->assign('url', $url);
        $this->assign('waitSecond', $waitSecond);
        $this->display('success');
        exit;
    }

    //弹出信息
    protected function alert($msg, $url = NULL){
        header("Content-type: text/html; charset=utf-8"); 
        $alert_msg="alert('$msg');";
        if( empty($url) ) {
            $gourl = 'history.go(-1);';
        }else{
            $gourl = "window.location.href = '{$url}'";
        }
        echo "<script>$alert_msg $gourl</script>";
        exit;
    }

    //判断空值
    public function alert_str($srt,$type=0,$json=false)
    {
        switch ($type) {
            case 'int':
                $srt=intval($srt);
                break;
            default:
                $srt=in($srt);
                break;
        }
        if(empty($srt)){
            if($json){
                $this->msg('内部通讯错误！',0);
            }else{
                $this->alert('内部通讯错误！');
            }
        }
    }

    //提示
    public function msg($message,$status=1) {
        if (is_ajax()){
            @header("Content-type:text/html");
            echo json_encode(array('status' => $status, 'message' => $message));
            exit;
        }else{
            alert($message);
        } 
    }

    //分页 
    protected function page($url, $totalRows, $listRows =20, $rollPage = 5 ,$type=0)
    {
        $page = new Page();
        if($type==0){
            return $page->show($url, $totalRows, $listRows, $rollPage);
        }else if($type==1){
            $page->show($url, $totalRows, $listRows, $rollPage);
            return $page->prePage('',0);
        }else if($type==2){
            $page->show($url, $totalRows, $listRows, $rollPage);
            return $page->nextPage('',0);
        }else if($type==3){
            $page->show($url, $totalRows, $listRows, $rollPage);
            return $page->nowpage();
        }
    }

    //获取分页条数
    protected function pagelimit($url,$listRows)
    {
        $page = new Page();
        $cur_page = $page->getCurPage($url);
        $limit_start = ($cur_page - 1) * $listRows;
        return  $limit_start . ',' . $listRows;
    }

    //插件接口
    public function plus_hook($module,$action,$data=NULL)
    {
        $action_name='hook_'.$module.'_'.$action;
        $list=$this->model->table('plugin')->where('status=1')->select();
        $plugin_list=Plugin::get();
        if(!empty($list)){
            foreach ($list as $value) {
                $action_array=$plugin_list[$value['file']];
                if(!empty($action_array)){
                    if(in_array($action_name,$action_array))
                    {
                        if($return){
                            return Plugin::run($value['file'],$action_name,$data,$return);
                        }else{
                            Plugin::run($value['file'],$action_name,$data);
                        }
                    }
                }
            }
        }
    }

    // 替换插件接口
    public function plus_hook_replace($module,$action,$data=NULL)
    {
        $hook_replace=$this->plus_hook($module,$action,$data,true);
        if(!empty($hook_replace)){
            return $hook_replace;
        }else{
            return $data;
        }
    }

	
	/*!=======================================================================
	* Description: 获取微信对象
	* ======================================================================== */
    public function cpweixin($token, $wxuser) {
        static $weixin = NULL;
      
        if (empty($weixin)) {
            $weixin = new cpWechat($token, $wxuser);  
           
        }
        return $weixin;
    }
	
	/*!=======================================================================
	* Description: 微信JSSDK准备参数
	* $appid：	   微信公众号APPID
	* $appsecret： 微信公众号APPSECRET
	* ======================================================================== */
    public function wxjs($appid, $appsecret) {
        require_once(__ROOTDIR__ . '/system/ext/wx_jssdk.php'); //加载
        $jssdk = new JSSDK($appid, $appsecret);
        $signPackage = $jssdk->GetSignPackage();
        return $signPackage;
    }
	
	
	/*!=======================================================================
	* Description: 微信授权登陆 获取用户Session
	* $scope：	   两种参数为：snsapi_userinfo或snsapi_base(静默授权)
	* $state：	   自定义参数，微信会原路返回该参数到本地接口
	* ======================================================================== */
	public function weixin_oauth($scope = 'snsapi_userinfo', $state = 'index') {
		
		$url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=';
		$oauth_center = $this->config['WX_OAUTH_CENTER'];
		
		if($oauth_center) {
			
			// 主站授权子站
			$url .= $this->config['WX_OAUTH_CENTER_APPID'];	// 主站AppID
			$url .= "&redirect_uri=http%3A%2F%2F".$this->config['WX_OAUTH_CENTER_DOMAIN'];	// 主站域名
			// /index.php/weixin/oauth_center/?url=http://
			$url .= "%2Findex.php%2Fweixin%2foauth_center%2F%3Furl%3Dhttp%253A%252F%252F";
			$url .= $this->config['siteurl'];	// 子站域名
			$url .= "%2Findex.php%2Fweixin%252Fverify%252F&response_type=code&scope=".$scope;
			$url .= "&state=".$state."&connect_redirect=1#wechat_redirect";
			
		} else {
			
			// 本地授权
			$url .= $this->config['WX_appid'];	// 本地公众号AppID
			$url .= "&redirect_uri=http%3a%2f%2f".$this->config['siteurl'];	// 本站域名
			$url .= "%2findex.php%2fweixin%2foauth%2f&response_type=code&scope=".$scope;
			$url .= "&state=".$state."#wechat_redirect";
		}
		
		header('Location: '.$url);
		exit;
	}
	
	public function is_login($scope = 'snsapi_userinfo', $state = 'index') {
		
		$member_id = $_SESSION["userid"];
		
		if( empty($member_id) ) {
			$this->weixin_oauth($scope, $state);
		}
	}
	
    /*!=======================================================================
    * Description: 读取数据记录集合
    * $where_sql： 参数以' AND '开始拼接，默认为空则读取所有数据记录集合
    * $order_by：  默认以ID倒序进行排序
    * $limit：     默认不分页，分页参数格式为 limit 0,20
    * ======================================================================== */
    public function data_list($table='', $where='', $order='', $limit='',$field="*") {
        $sql = "
        SELECT {$field}
        FROM {$this->model->pre}{$table} 
        {$where} 
        {$order} 
        {$limit} 
        ";
        $data = $this->model->query($sql); 
        return  $data ? $data : array();
    }
    
    
    
    
    /*!=======================================================================
    * Description: 读取数据记录总数
    * $where：     参数以' AND '开始拼接，默认为空则返回所有记录总数
    * ======================================================================== */
    public function data_count($table, $where = '') {
        $sql="
        SELECT count(1) AS num 
        FROM {$this->model->pre}{$table}  {$where}";
        $data = $this->model->query($sql);
        return $data[0]['num'] ? $data[0]['num'] : 0;
    }

    public function data_sum($table, $where = '',$field='id') {
        $sql="
        SELECT sum({$field}) AS num 
        FROM {$this->model->pre}{$table}  {$where}";
        $data = $this->model->query($sql);
        return $data[0]['num'] ? $data[0]['num'] : 0;
    }
    
    
    
    
    public function data_add($table, $data) {
        //$data['date'] = time();
        $last_insert_id = $this->model->table($table)->data($data)->insert();
        return $last_insert_id;
    }
    
    
    
    
    /*!=======================================================================
    * Description: 修改一条数据
    * $table:      需要操作的表
    * $data：      数据记录模型，$data['id']必须赋值
    * ======================================================================== */
    public function data_edit($table, $data, $where) {

        $result = $this->model->table($table)->data($data)->where($where)->update();
        //echo $this->model->sql;
        return $result;

    }
    
    
    
    
    /*!=======================================================================
    * Description: 根据ID返回一条数据
    * $id：        记录ID
    * ======================================================================== */
    public function data_getinfo($table, $where, $field="*") {
        $data = $this->model->table($table)->field($field)->where($where)->find();
        return $data ? $data : array();
    }
    
    
    public function data_self_add($table, $field, $num, $where){
        $sql = "UPDATE {$this->model->pre}{$table} SET `{$field}` = {$field} + {$num} {$where}";
        return $this->model->query($sql);
    }

    public function data_self_jian($table, $field, $num, $where){
        $sql = "UPDATE {$this->model->pre}{$table} SET `{$field}` = {$field} - {$num} {$where}";
        return $this->model->query($sql);
    }
    
    // 减少库存
    public function dknum($table, $field, $num, $where){
        $sql = "UPDATE {$this->model->pre}{$table} SET `{$field}` = {$field} - {$num} {$where}";
        return $this->model->query($sql);
    }
    
    /*!=======================================================================
    * Description: 根据自定义条件，删除数据
    * $where：     参数不含where关键字，如`id`=1
    * ======================================================================== */
    public function data_del($table, $where) {
        return $this->model->table($table)->where($where)->delete();
    }
    
    
    /*!=======================================================================
    * Description: 查询数据是否已经存在
    * $where：     参数不含where关键字
    * ======================================================================== */
    public function data_exist($where, $id = '') {
        if(empty($id)) {
            return $this->model->table($this->tablename)->where($where)->find();
        } else {
            return $this->model->table($this->tablename)->where($where. ' AND `id`!='. $id)->find();
        }
    }

    // 人才展示的时候
    public function resume_list($where,$order,$limit){
        $sql = "SELECT r.*,u.image AS image,u.username AS username,u.mobile AS mobile, g.name AS name ,u.id AS uid,u.linkstyle AS linkstyle ".
               "FROM {$this->model->pre}xy_resume AS r ".
               "LEFT JOIN {$this->model->pre}xy_user AS u ON r.uid=u.id ".
               "LEFT JOIN {$this->model->pre}xy_gradesort AS g ON r.gid=g.id ".
               "{$where} {$order} {$limit} ";
        $data = $this->model->query($sql);
        return empty($data) ? array() : $data;
    }
    // 人才展示的时候
    public function resume_count($where){
        $sql = "SELECT COUNT(1) AS num ".
               "FROM {$this->model->pre}xy_resume AS r ".
               "LEFT JOIN {$this->model->pre}xy_user AS u ON r.uid=u.id ".
               "LEFT JOIN {$this->model->pre}xy_gradesort AS g ON r.gid=g.id ".
               "{$where}";
        $data = $this->model->query($sql);
        return $data[0]['num'] ? $data[0]['num'] : 0;
    }
    
    public function ajaxReturn($val, $msg='',$content="") {
        $res['ret'] = $val;
        $res['msg'] = $msg;
        $res['content'] = $content;
        echo json_encode($res);
        exit;
    }

    /**
     * 检测验证码是否正确
     * mobile 手机号
     * code 验证码
     * type 短信的类型
     */
    public function checkSmsCode($mobile, $code, $type)
    {
        $sendcode = $this->data_getinfo("sendcode"," mobile ='" . $mobile . "' AND style = '" . $type . "'");
        if(empty($sendcode)){
            return array(202, "请先发送短信验证");
        }
        if($sendcode['expiretime'] < time()){
            return array(202, "短信验证码已经过期");
        }

        if($sendcode['code'] != $code){
            return array(202, "短信验证码输入错误");
        }

        return array(200, "短信验证通过", $sendcode['id']);
    }

    /**
     * 删除已经使用或者过期的二维码
     */
    public function delSmsCode($id)
    {   
        $del = $this->data_del("sendcode", "id='" . $id . "'");
        $deltime = $this->data_del("sendcode", "expiretime < '" . time() . "'");
        return array(200, "短信验证码删除成功");
    }

    /**
     * 使用的是值传递的方法
     * data 数据
     * pid 上级id 的字段名称
     * id 主键的名称
     */
    public function categorys($data, $pid='pid', $id='id'){
        //第一步 构造数据
        $items = array();
        if (empty($data)){
            return array();
        }
        foreach($data as $value){
            $items[$value['id']] = $value;
        }
        //第二部 遍历数据 生成树状结构
        $tree = array();
        foreach($items as $key => $value){
            if(isset($items[$value['pid']])){
                $items[$value['pid']]['son'][] = &$items[$key];
            }else{
                $tree[] = &$items[$key];
            }
        }
        return $tree;
    }

    public function categorys_test($data, $pid='pid', $id='id'){
        //第一步 构造数据
        $items = array();
        if (empty($data)){
            return array();
        }
        foreach($data as $value){
            $items[$value['id']] = $value;
        }
        //第二部 遍历数据 生成树状结构
        $tree = array();
        foreach($items as $key => $value){
            if(isset($items[$value['pid']])){
                $items[$value['pid']]['son'][] = &$items[$key];
            }else{
                $tree[] = &$items[$key];
            }
        }
        return $tree;
    }

    public function checkField($input)
    {
        if (empty($input['source_id'])) {
            
        }

        if (empty($input['name'])) {
            return array(202, "生词不能为空");
        }

        if (empty($input['sort_id'])) {
            return array(202, "请选择分类");
        }

        if (empty($input['paraphrase'])) {
            return array(202, "释义不能为空");
        }

        // if (empty($input['pronunciation'])) {
        //     return array(202, "读音链接未上传");
        // }

        if (empty($input['pronunciation_words'])) {
            return array(202, "请填写读音的拼写");
        }

        if (empty($input['sentences'])) {
            return array(202, "例句不能为空");
        }

        if (empty($input['associate'])) {
            return array(202, "联想不能为空");
        }
    }

    /**
     * 加入听写朗读等操作表中 |||方便个人中心列表展示
     */
    public function sourceLog($source_id, $user_id, $source_type, $do_type, $dictation_tag = "")
    {
        $sql = "source_id = '" . $source_id . "' and user_id = '" . $user_id . "' and source_type = '" . $source_type . "' and do_type = '" . $do_type . "'";
        $info = $this->data_getinfo("source_log", $sql);
        $data = array(
            "user_id" => $user_id,
            "source_id" => $source_id,
            "created_at" => time(),
            "source_type" => $source_type,
            "do_type" => $do_type,
            "dictation_tag" => $dictation_tag,
        );
        if (empty($info)) {
            $add = $this->data_add("source_log", $data);
        } else {
            $edit = $this->data_edit("source_log", $data, " id = '" . $info['id'] . "'");
        }
    }

    /**
     * 某一个时间戳距离当前时间的天数
     * type 1普通会员  2猩听译会员
     */
    public function checkDays($userinfo)
    {
        $days = 0;
        $nowtime = strtotime(date("Y-m-d 00:00:00"));
        if ($userinfo['type'] == 2) {
            if ($userinfo['endtime'] > $nowtime) {
                $timer = $userinfo['endtime'] -  $nowtime;
                $days = ceil($timer / 3600 / 24);
            }
        }

        return $days;
    }

    // 格式化图片多图
    public function jsonImage($images, $siteurl)
    {
        $images = json_decode($images, true);
        if (! is_array($images)) {
            $images = array();
        }

        $res = array();
        $len = count($images);
        if ($len !== 0) {
            for($i=0; $i<$len; $i++) {
                $res[$i] = formatAppImageUrl($images[$i], $siteurl);
            }
        }

        return $res;
    }


    // 格式化时间
    public function formatTime($val)
    {
        $x = time();
        $a = $x - $val;

        if ($a < 60) {
            return $a . "秒前";
        } elseif ($a < (60 * 60)) {
            return round($a / 60) . "分钟前";
        } elseif ($a < (24 * 60 * 60)) {
            return round($a / 60 / 60) . "小时前";
        } elseif ($a < (30 * 24 * 60 * 60)){
            return round($a / 24 / 60 / 60) . "天前";
        } else {
            return round($a / 30 / 24 / 60 / 60) . "月前";
        }
    }


    // KE课程推荐位
    public function positionList()
    {
        $list = $this->data_list("good_course", "where id > 0 and position like '%". ',2,' ."%'", "order by id desc", "limit 10");
        $positionList = array();
        if (!empty($list)) {
            foreach ($list as $k => $val) {
                $positionList[] = array(
                    "id" => $val['id'],
                    "title" => $val['title'],
                    "created_at" => date("Y-m-d H:i", $val['created_at']),
                );  
            }
        }
        return $positionList;
    }
    

    /**
     * 生成日签的时候需要显示学习天数
     * 冗余到user表中
     * user 表中加上  days学习天数  end_do_time 最后学习时间
     */
    public function doDays($user_id)
    {
        $userinfo = $this->data_getinfo("user", " id = '" . $user_id . "'");
        $nowtime = strtotime(date("Y-m-d 00:00:00"));
        // 今天没有签到 学习时间加上一天 更新时间到现在
        if ($userinfo['end_do_time'] < $nowtime) {
            $edit = $this->data_edit("user", array("end_do_time" => time()), " id = '" . $user_id . "'");
            $addDays = $this->data_self_add("user", "days", 1, " where id = '" . $user_id . "'");
        }
    }



    // 记录日志
    public function fileLog($folder,$file,$content){
        $path = __ROOTDIR__.'/data/log/'.$folder.'/';
        if(!is_dir($path)){
            @mkdir($path, 0777, true);
        }
        return file_put_contents($path.$file.'.txt',$content.PHP_EOL,FILE_APPEND);
    }


    // 听写标签的更新
    public function updateTag1($dictation_tag, $type, $source_id)
    {      
        $table = ""; 
        switch($type){
            case 1:
                $table = "source";
                break;
            case 2:
                $table = "user_source";
                break;
            case 3:
                $table = "good_course";
            default:
                return array(202, "类型参数出错，刷新重试");
                break;
        }

        $data = array(
            "dictation_tag" => $dictation_tag,
        );

        $info = $this->data_edit($table, $data, " id = '" . $source_id . "'");
        if (empty($info)) {
            return array(202, "网络原因请刷新重试");
        } else {
            return array(200, "标签更新成功");
        }
    }

    // 更新听写标签
    // 跟着文章走  一篇文章一个标签
    // type 1平台 2个人 3精品课程
    public function updateTag($user_id, $source_id, $type, $dictation_tag)
    {
        $sql = "user_id = '" . $user_id . "' and source_id = '" . $source_id . "' and type = '" . $type . "'";
        $dictationTag = $this->data_getinfo("dictation_tag_log", $sql);

        $data = array(
            "user_id" => $user_id,
            "source_id" => $source_id,
            "type" => $type,
            "created_at" => time(),
            "dictation_tag" => $dictation_tag,
        );

        if (! empty($dictationTag)) {
            $d = $this->data_edit("dictation_tag_log", $data, " id = '" . $dictationTag['id'] . "'");
        } else {
            $d = $this->data_add("dictation_tag_log", $data);
        }

        if (empty($d)) {
            return array(202, "参数错误请刷新重试");
        } else {
            return array(200, "标签更新成功");
        }
    }


    // 获取听写标签信息
    public function getTagInfo($user_id, $source_id, $type)
    {
        // 获取文章的标签 一篇文章  一个标签
        $sql = "user_id = '" . $user_id . "' and source_id = '" . $source_id . "' and type = '" . $type . "'";
        $dictationTagLog = $this->data_getinfo("dictation_tag_log", $sql);
        $dictation_tag = "";
        if (! empty($dictationTagLog['dictation_tag'])) {
            $dictation_tag = $dictationTagLog['dictation_tag'];
        }

        return $dictation_tag;
    }


    // 获取详细信息
    public function getTagName($dictation_tag, $user_id)
    {
        $dictation_tag = empty($dictation_tag) ? "" : substr($dictation_tag, 1, strlen($dictation_tag) - 2);
        $dictationTag = array();
        if (! empty($dictation_tag)) {
            if (is_numeric($dictation_tag)) {
                $tag = $this->data_getinfo("dictation_tag", " id = '" . $dictation_tag . "' and user_id = '" . $user_id . "'");
                if (! empty($tag)) {
                    $dictationTag[0] = array(
                        "id" => $dictation_tag,
                        "name" => $tag['name']
                    ); 
                }
                
            } else {
                $dictation_tag = explode(",", $dictation_tag);
                for($i=0; $i < count($dictation_tag); $i++) {
                    $tag = $this->data_getinfo("dictation_tag", " id = '" . $dictation_tag[$i] . "' and user_id = '" . $user_id . "'");
                    if (! empty($tag)) {
                        $dictationTag[] = array(
                            "id" => $dictation_tag[$i],
                            "name" => $tag['name'],
                        ); 
                    }
                }
            }
        }

        return $dictationTag;
    } 


    // 选择表
    public function doTable($type)
    {
        $table = "";
        switch($type){
            case 1:
                $table = "source"; 
                break;
            case 2:
                $table = "user_source";
                break;
            case 3:
                $table = "good_course";
                break;
            default : 
                $table = "";  
                break;
        }
        return $table;
    }


    /**
     *  我的听写的翻译
     * type 1平台素材  2个人素材  3精品课程
     */
    public function dictationTranslation($source_id, $source_period_id, $user_id, $type)
    {
        $sql = "source_id = '" . $source_id . "' and source_period_id = '" . $source_period_id . "' and user_id = '" . $user_id . "' and type = 3 and pid = '" . $type . "'";
        $info = $this->data_getinfo("source_translation", $sql);

        return $info;
    } 


    /**
     * 生成日签打卡记录
     */
    public function signLearn($user_id)
    {
        $userinfo = $this->data_getinfo("user", " id = '" . $_user_id . "'");

        $where = " where id > 0 and user_id = '" . $user_id . "'";
        $dictationNum = $this->data_count("source_dictation", $where);
        $readNum = $this->data_count("source_read", $where);
        $translationNum = $this->data_count("source_translation", $where);
        $wordsNum = $this->data_count("source_translation", $where);
        $subtitlesNum = $this->data_count("source_subtitles", $where);
        // 展示日签的信息
        $info = array(
            "days" => (int)$userinfo['days'],
            "dictationNum" => (int)$dictationNum,
            "readNum" => (int)$readNum,
            "translationNum" => (int)$translationNum,
            "wordsNum" =>(int)$wordsNum,
            "subtitlesNum" =>(int)$subtitlesNum,
            "avatar" => empty($userinfo['avatar']) ? "" : formatAppImageUrl($userinfo['avatar'], $siteurl),
            "nickname" => empty($userinfo['nickname']) ? "---" : $userinfo['nickname'],
            "bg_img" => formatAppImageUrl($signDays['bg_img'], $siteurl),
            "created_at" => time(),
            "user_id" => $user_id,
        );
        $learnLog = $this->data_add("learn_log", $info);

    }



    // 精品课程排序 order
    public function courseSortTEST($orderTime, $orderNum)
    {
        $orderTime = intval($orderTime) > 0 ? intval($orderTime) : 0;
        $orderNum = intval($orderNum) > 0 ? intval($orderNum) : 0;

        $order = " order by id desc";
        if (!empty($orderTime) && !empty($orderNum)) {
            $s = ($orderTime == 1) ? "desc" : "asc";
            $n = ($orderNum == 1) ? "desc" : "asc";
            $order = " order by created_at " . $s . ", buynum " . $n;
        }

        if (empty($orderTime) && !empty($orderNum)) {
            $n = ($orderNum == 1) ? "desc" : "asc";
            $order = " order by buynum " . $n;
        }

        if (!empty($orderTime) && empty($orderNum)) {
            $s = ($orderTime == 1) ? "desc" : "asc";
            $order = " order by created_at " . $s;
        }

        return $order;
    }

    // 
    public function courseSort($orderTime, $orderNum)
    {
        $orderTime = intval($orderTime) > 0 ? intval($orderTime) : 0;
        $orderNum = intval($orderNum) > 0 ? intval($orderNum) : 0;

        $order = " order by id desc";
        if (! empty($orderTime)) {
            $s = ($orderTime == 1) ? "desc" : "asc";
            $order = " order by created_at " . $s; 
        }


        if (! empty($orderNum)) {
            $n = ($orderNum == 1) ? "desc" : "asc";
            $order = " order by buynum " . $n; 
        }

        return $order;
    }


    // 展示合成的音频
    // source_id 主表的资源id
    // user_id 会员的id
    // type 1平台素材  2个人素材 3精品课程
    public function mergeAudio($source_id, $user_id, $type)
    {
        $sql = "user_id = '" . $user_id . "' and source_id = '" . $source_id . "' and type = '" . $type . "' and status = 2";
        $info = $this->data_getinfo("merge_audio", $sql);
        $path = "";
        if (! empty($info['path'])) {
            $path = $info['path'];
        }
        return $path;
    }

    /**
     *  平台  个人  精品课程的朗读记录
     *  我的听写的朗读记录
     *  source_id
     *  source_period
     *  type  1个人  2 平台  3精品课程
     *  user_id
     */
    public function dictationReadLog($source_id, $source_period_id, $type, $user_id)
    {
        $sql = "source_id = '" . $source_id . "' and source_period_id = '" . $source_period_id . "' and user_id = '" . $user_id . "' and type = 3 and pid = '" . $type . "'";

        $info = $this->data_getinfo("source_read", $sql);
        $dictationRead = array();
        if (!empty($info)) {
            $dictationRead['path'] = empty($info['path']) ? "" : $this->config['qiniu'] . $info['path'];
            $readInfo = json_decode($info['read_info'], true);
            $len = count($readInfo);
            $read_info = array();
            if ($len > 0) {
                for($i = 0; $i < $len; $i++) {
                    $read_info[$i] = $this->config['qiniu'] . $readInfo[$i];
                }
            }

            $dictationRead['read_info'] = $read_info;
        }
        return $dictationRead;
    }


    // 三级分类 加上全部
    public function cation($arr,$num=0,$m=1)
    {
        $list = [];
        foreach($arr as $k=>$v){
            if($v['pid'] == $num){
               
                if ($m == 2) {
                    $ar = array(
                        "id" => 0,
                        "pid"=> (int)$v['pid'], 
                        "name" => "全部",
                        "son" => array(
                            array(
                                "id" => 0,
                                "pid" => 0,
                                "name" => "全部",
                            ),
                        ),
                    );
                } else {
                    $ar = array("id" => 0,"pid"=> (int)$v['pid'], "name" => "全部");  
                }
                // $v['level'] = $m;
                if ($m < 3) {
                    $v['son'] = $this->cation($arr, $v['id'], $m+1);
                }
                $list[] = $v;
            }
        }

        // 判断节点 加上全部
        array_unshift($list, $ar);
       
        return $list;
    }
}
?>