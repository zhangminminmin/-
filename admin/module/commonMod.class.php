<?php
// 公共类
class commonMod
{
    protected $model = NULL;	// 数据库模型
    protected $layout = NULL;	// 布局视图
    protected $config = array();
    private $_data = array();
    public $post = array();
    public $get = array();

    protected function init(){}
    
    public function __construct(){
        global $config;
         $session_name = session_name();
        if(!isset($_COOKIE[$session_name])) {
            foreach($_COOKIE as $key=>$val)
            {
                $key = strtoupper($key);
                if(strpos($key,$session_name))
                {
				  session_id($_COOKIE[$key]);
                }
            }
        }
        @session_start();
        $config['PLUGIN_PATH']=__ROOTDIR__.'/plugins/';
        $this->config = $config;
        $this->model = self::initModel( $this->config);
        $this->init();
        $this->check_login();
        Plugin::init('Admin',$config); 
        $langCon=Lang::langCon();
        $this->config = array_merge((array)$config,(array)$langCon);   
        $this->post = in($_POST);
        $this->get = in($_GET);
    }


    // 初始化模型
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


    // 获取模板对象
    public function view(){
        static $view = NULL;
        if( empty($view) ){
            $view = new cpTemplate( $this->config );
        }
        return $view;
    }
    
    // 模板赋值
    protected function assign($name, $value){
        return $this->view()->assign($name, $value);
    }

    // 模板显示
    protected function display($tpl = '', $return = false, $is_tpl = true ,$diy_tpl=false){
        if( $is_tpl ){
            $tpl = empty($tpl) ? $_GET['_module'] . '/'. $_GET['_action'] : $tpl;
            if( $is_tpl && $this->layout ){
                $this->__template_file = $tpl;
                $tpl = $this->layout;
            }
        }

        $this->assign("model", $this->model);
        $this->assign('sys', $this->config);
        $this->assign('config', $this->config);
        $this->assign('js', $this->load_js());
        $this->assign('css', $this->load_css());
        $this->assign('user', model('user')->current_user());
        $this->view()->assign( $this->_data );
        return $this->view()->display($tpl, $return, $is_tpl,$diy_tpl);
    }

    // 包含内模板显示
    protected function show($tpl = ''){
        $content=$this->display($tpl,true);
        $body=$this->display('index/common',true);
        $html=str_replace('<!--body-->', $content, $body);
        echo $html;
    }

    // 脚本运行时间
    public function runtime(){
        $GLOBALS['_endTime'] = microtime(true);
        $runTime = number_format($GLOBALS['_endTime'] - $GLOBALS['_startTime'], 4);
        echo $runTime;
    }


    // 判断是否是数据提交 
    protected function isPost(){
        return $_SERVER['REQUEST_METHOD'] == 'POST';
    }

    // 登录检测
    protected function check_login() {

        if($_GET['_module']=='login'||substr($_GET['_action'],-6)=='ignore'){
            return true;
        }
        if(!empty($_GET['key'])){
            $key=urldecode($_GET['key']);
            $syskey=$this->config['SPOT'].$this->config['DB_NAME'];
            if($key==$syskey){
                return true;
            }
        }
        $uid=$_SESSION[$this->config['SPOT'].'_user'];
        // 读取登录信息
        if(empty($uid)){
            $this->redirect(__APP__ . '/login');
        }
        $user=model('login')->user_info_id($uid);
        if(empty($user)){
            $this->redirect(__APP__ . '/login');
        }
        $this->check_pw($user);
        return true;
    }

    // 检测模块权限
    protected function check_pw($user){
        if($user['keep']==1){
            return true;
        }
        
        if(empty($user['model_power'])){
            return true;
        }
        $module=in($_GET['_module']);
        // 处理栏目权限
        if(substr($module,-8)=='category'){
            $module='category';
        }
        $info=model('menu')->module_menu($module);
        if(!in_array($info['id'], $user['model_power'])){
            $this->msg('您没有权限进行操作！');
        }
    }

    // 直接跳转
    protected function redirect($url)
    {
        header('location:' . $url, false, 301);
        exit;
    }

    // 操作成功之后的提示
    protected function success($msg, $url = null)
    {
        if ($url == null)
            $url = 'javascript:history.go(-1);';
        $this->assign('msg', $msg);
        $this->assign('url', $url);
        $this->display('index/msg');
        exit;
    }

    // 弹出信息
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

    // 判断空值
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

    // 提示
    public function msg($message,$status=1) {
        if (is_ajax()){
            @header("Content-type:text/html");
            echo json_encode(array('status' => $status, 'message' => $message));
            exit;
        }else{
            $this->success($message);
        } 
    }

    // JSUI库
    public function load_js() {
        $js='';
        $js .= '<script type=text/javascript src="' . __PUBLICURL__ . '/js/jquery.js"></script>' . PHP_EOL;
        $js .= '<script type=text/javascript src="' . __PUBLICURL__ . '/js/duxui.js"></script>' . PHP_EOL;
        $js .= '<script type=text/javascript src="' . __PUBLICURL__ . '/dialog/jquery.artDialog.js?skin=default"></script>' . PHP_EOL;
        $js .= '<script type=text/javascript src="' . __PUBLICURL__ . '/dialog/plugins/iframeTools.js"></script>' . PHP_EOL;
        $js .= '<script type=text/javascript src="' . __PUBLICURL__ . '/kindeditor/kindeditor-min.js"></script>' . PHP_EOL;
        $js .= '<script type=text/javascript src="' . __PUBLICURL__ . '/kindeditor/lang/zh_CN.js"></script>' . PHP_EOL;
        $js .= '<script type=text/javascript src="' . __PUBLICURL__ . '/js/common.js"></script>' . PHP_EOL;
        $js .= '<script src="' . __PUBLICURL__ . '/elementui/vue.js"></script>' . PHP_EOL;
        $js .= '<script src="' . __PUBLICURL__ . '/elementui/index.js"></script>' . PHP_EOL;
        $js .= '<script src="' . __PUBLICURL__ . '/js/vue/vue-resource.min.js"></script>' . PHP_EOL;
        return $js;
    }
	
	
    // CSSUI库
    public function load_css()
    {
        $css='';
        $css .= '<link href="' . __PUBLICURL__ . '/css/base.css" rel="stylesheet" type="text/css" />' . PHP_EOL;
        $css .= '<link href="' . __PUBLICURL__ . '/css/style.css" rel="stylesheet" type="text/css" />' . PHP_EOL;
        $css .= '<link href="' . __PUBLICURL__ . '/elementui/index.css" rel="stylesheet" type="text/css">' . PHP_EOL;
        $css .= '<link href="' . __PUBLICURL__ . '/kindeditor/themes/default/default.css" rel="stylesheet" type="text/css" />' . PHP_EOL;
        return $css;
    }

    // 分页 $url:基准网址，$totalRows: $listRows列表每页显示行数$rollPage 分页栏每页显示的页数
    protected function page($url, $totalRows, $listRows = 20, $rollPage = 5)
    {
        $page = new Page();
        return $page->show($url, $totalRows, $listRows, $rollPage);
    }

    // 获取分页条数
    protected function pagelimit($url, $listRows)
    {
        $page = new Page();
        $cur_page = $page->getCurPage($url);
        $limit_start = ($cur_page - 1) * $listRows;
        return  $limit_start . ',' . $listRows;
    }

    // 插件接口
    public function plus_hook($module,$action,$data=NULL,$return=false)
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

    public function ajaxReturn($val, $msg='',$list = "") {
        $res['ret'] = $val;
        $res['msg'] = $msg;
        $res['content'] = $list;
        echo json_encode($res);
        exit;
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
        return $this->model->table($table)->data($data)->where($where)->update();
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

    // 列表
    public function plist($table,$limit, $where = '',$order=" order by id desc ") {
        $sql = "
        SELECT *
        FROM {$this->model->pre}{$table} ".$where."
        ".$order."
        LIMIT {$limit} 
        ";
        return $this->model->query($sql);
    }
    // 列表数量
    public function pcount($table, $where = '') {
        $sql = "
        SELECT COUNT(*) AS num
        FROM {$this->model->pre}{$table} ".$where."
        ORDER BY id DESC
        ";
        $data = $this->model->query($sql);
        return $data[0]['num'];
    }


    // 验证
    public function checkFile($input,$files) 
    {
        if (empty($input['title'])) {
            return array(202, "资源名称不能为空");
        }  

        if (intval($input['type']) <= 0) {
            return array(202, "请选择资源类型");
        }

        if (empty($input['description'])) {
            return array(202, "资源描述不能为空");
        }

        if (! empty($input['view_count'])) {
            if (! is_numeric($input['view_count'])) {
                return array(202, "资源浏览数必须为数字");
            }
        }
        if (empty($input['fileList']) && empty($files['picFile'])) {
            return array(202, "请上传素材缩略图");
        }

        if (empty($input['category'])) {
            return array(202, "请选择素材分类");
        }

        if ($input['type'] != 3) {


            if (empty($input['source_path'])) {
                return array(202, "请上传资源链接");
            }

            // if (empty($input['notice'])) {
            //     return array(202, "提示词不能为空");
            // }

            // if (empty($input['words'])) {
            //     return array(202, "生词汇总不能为空");
            // }

            if (empty($input['answer'])) {
                return array(202, "标准答案不能为空");
            }

            if (!empty($input['pathCount'])) {
                for ($i=0; $i<$input['pathCount']; $i++) {
                    if (empty($input['pathList_'.$i])) {
                        return array(202, "资源链接不能有空选项");
                    }

                    if (empty($input['subtitle_'.$i])) {
                        return array(202, "分段资源的标题不能有空选项");
                    }

                }
            }
        }

        if ($input['type'] == 3 || $input['type'] == 4 || $input['type'] == 5) {
            if (empty($input['textCount'])) {
                return array(202, "文本内容不能为空");
            }

            for ($i=0; $i<$input['textCount']; $i++) {
                if (empty($input['textList_'.$i])) {
                    return array(202, "文本内容不能存在空选项");
                }
            }
        }

        if ($input['type'] == 4 || $input['type'] == 5) {
            if (empty($input['subtitlesShow']) && empty($files['subtitles'])) {
                return array(202, "请上传字幕文件");
            }
        }

        return array();
    }


    // 
    public function addSource($input, $addid, $data)
    {
        $this->model->query("START TRANSACTION");
        // 添加主资源
        $addid = $this->data_add("source", $data);
        if (empty($addid)) {
            $this->model->query("ROLLBACK");
            return array(202, "网络出错资源上传失败");
        }

        if ($input['type'] == 1 || $input['type'] == 2) {
            $this->avInfo($input, $addid);
        }

        if ($input['type'] == 3) {
            $this->textInfo($input, $addid);
        }

        if ($input['type'] == 4 || $input['type'] == 5) {
            $this->avInfo($input, $addid);
            $this->textInfo($input, $addid);
        }

        $this->model->query("COMMIT");
        return array(200, "资源保存成功");
    }


    // 音视频的
    public function avInfo($input ,$addid)
    {
        if (empty($input['pathCount'])) {
            $param = array(
                "source_id" => $addid,
                "path" => $input['source_path'],
                "subtitle" => $input['subtitle'],
            );
            $addInfo = $this->data_add("source_info", $param);
            if (empty($addInfo)) {
                $this->model->query("ROLLBACK");
                return array(202, "网络出错资源上传失败");
            }
        } else {
            for ($i=0; $i<$input['pathCount']; $i++) {
                $param = array(
                    "source_id" => $addid,
                    "path" => $input['pathList_'.$i],
                    "subtitle" => $input['subtitle_'.$i],
                );
                $addInfo = $this->data_add("source_info", $param);
                if (empty($addInfo)) {
                    $this->model->query("ROLLBACK");
                    return array(202, "网络出错资源上传失败");
                }
            }

        }
    }

    // 文本的
    public function textInfo($input, $addid)
    {
        for ($i=0; $i<$input['textCount']; $i++) {
            $param = array(
                "source_id" => $addid,
                "content" => $input['textList_'.$i],
                "created_at" => time(),
            );
            $addText = $this->data_add("source_text", $param);
            if (empty($addText)) {
                $this->model->query("ROLLBACK");
                return array(202, "网络出错资源上传失败");
            }
        }
    }


    // 编辑资源
    public function editSource($input, $data)
    {
        $this->model->query("START TRANSACTION");
        $editSource = $this->data_edit("source", $data, " id = '" . $input['id'] . "' ");
        // 添加主资源
        if (empty($editSource)) {
            $this->model->query("ROLLBACK");
            return array(202, "网络出错资源上传失败");
        }
        // 删除音频或者视频或者文本
        $delText = $this->data_del("source_info", " source_id = '" . $input['id'] . "'");
        $delInfo = $this->data_del("source_text", " source_id = '" . $input['id'] . "'");
        if (empty($delText) || empty($delInfo)) {
            $this->model->query("ROLLBACK");
            return array(202, "网络出错资源编辑失败");
        }
        // 编辑文本或者音视频信息
        if ($input['type'] == 1 || $input['type'] == 2) {
            $this->editAvinfo($input);
        }

        if ($input['type'] == 3) {
            $this->editTextInfo($input);
        }

        if ($input['type'] == 4 || $input['type'] == 5) {
            $this->editAvinfo($input);
            $this->editTextInfo($input);
        }

        $this->model->query("COMMIT");
        return array(200, "资源编辑成功");
    }

    // type为音视频
    public function editAvinfo($input)
    {

        if (empty($input['pathCount'])) {
            $param = array(
                "source_id" => $input['id'],
                "path" => $input['source_path'],
                "subtitle" => $input['title'],
            );
            $addInfo = $this->data_add("source_info", $param);
            if (empty($addInfo)) {
                $this->model->query("ROLLBACK");
                return array(202, "网络出错资源上传失败");
            }
        } else {
            for ($i=0; $i<$input['pathCount']; $i++) {
                $param = array(
                    "source_id" => $input['id'],
                    "path" => $input['pathList_'.$i],
                    "subtitle" => $input['subtitle_'.$i],
                );
                if ($input['source_ids_'.$i] && is_numeric($input['source_ids_'.$i])) {
                    $param['id'] = $input['source_ids_'.$i];
                }
                $addInfo = $this->data_add("source_info", $param);
                if (empty($addInfo)) {
                    $this->model->query("ROLLBACK");
                    return array(202, "网络出错资源上传失败");
                }
            }

        }
    }


    // 编辑文本
    public function editTextInfo($input)
    {
        for ($i=0; $i<$input['textCount']; $i++) {
            $param = array(
                "source_id" => $input['id'],
                "content" => $input['textList_'.$i],
                "created_at" => time(),
            );
            if ($input['text_ids_'.$i] && is_numeric($input['text_ids_'.$i])) {
                $param['id'] = $input['text_ids_'.$i];
            }

            $addText = $this->data_add("source_text", $param);
            if (empty($addText)) {
                $this->model->query("ROLLBACK");
                return array(202, "网络出错资源上传失败");
            }
        }
    }




    /**
     * 精品课程函数
     * 精品课程辅助函数
     * description
     */
    
    public function checkCourseFile($input,$files,$type=0)
    {
        if (empty($input['title'])) {
            return array(202, "资源名称不能为空");
        }  

        if ($type != 1) {
            if (intval($input['type']) <= 0) {
                return array(202, "请选择资源类型");
            }
        }


        if (empty($input['description'])) {
            return array(202, "资源描述不能为空");
        }

        if (! empty($input['view_count'])) {
            if (! is_numeric($input['view_count']) || $input['view_count'] < 0) {
                return array(202, "资源浏览数必须为数字且大于零");
            }
        }

        if (empty($input['price']) || !is_numeric($input['price']) || $input['price'] < 0) {
            return array(202, "价格不能为空且必须为数字且大于零");
        }

        if (! empty($input['buynum'])) {
            if (! is_numeric($input['buynum']) || $input['buynum'] < 0) {
                return array(202, "购买人数必须为数字且大于零");
            }
        }

        if (empty($input['fileList']) && empty($files['picFile'])) {
            return array(202, "请上传素材缩略图");
        }

        if (empty($input['category'])) {
            return array(202, "请选择素材分类");
        }

        if ($type != 1) {
            if ($input['type'] != 3) {


                if (empty($input['source_path'])) {
                    return array(202, "请上传资源链接");
                }

                // if (empty($input['notice'])) {
                //     return array(202, "提示词不能为空");
                // }

                // if (empty($input['words'])) {
                //     return array(202, "生词汇总不能为空");
                // }

                if (empty($input['answer'])) {
                    return array(202, "标准答案不能为空");
                }

                if (!empty($input['pathCount'])) {
                    for ($i=0; $i<$input['pathCount']; $i++) {
                        if (empty($input['pathList_'.$i])) {
                            return array(202, "资源链接不能有空选项");
                        }

                        if (empty($input['subtitle_'.$i])) {
                            return array(202, "分段资源的标题不能有空选项");
                        }

                    }
                }
            }

            if ($input['type'] == 3 || $input['type'] == 4 || $input['type'] == 5) {
                if (empty($input['textCount'])) {
                    return array(202, "文本内容不能为空");
                }

                for ($i=0; $i<$input['textCount']; $i++) {
                    if (empty($input['textList_'.$i])) {
                        return array(202, "文本内容不能存在空选项");
                    }
                }
            }

            if ($input['type'] == 4 || $input['type'] == 5) {
                if (empty($input['subtitlesShow']) && empty($files['subtitles'])) {
                    return array(202, "请上传字幕文件");
                }
            }

        }

        return array();
    }



    // 编辑资源
    public function editCourse($input, $data)
    {
        $this->model->query("START TRANSACTION");
        $editSource = $this->data_edit("good_course", $data, " id = '" . $input['id'] . "' ");
        // 添加主资源
        if (empty($editSource)) {
            $this->model->query("ROLLBACK");
            return array(202, "网络出错资源上传失败");
        }
        // 删除音频或者视频或者文本
        $delText = $this->data_del("good_course_info", " source_id = '" . $input['id'] . "'");
        $delInfo = $this->data_del("good_course_text", " source_id = '" . $input['id'] . "'");
        if (empty($delText) || empty($delInfo)) {
            $this->model->query("ROLLBACK");
            return array(202, "网络出错资源编辑失败");
        }
        // 编辑文本或者音视频信息
        if ($input['type'] == 1 || $input['type'] == 2) {
            $this->editCourseAvinfo($input);
        }

        if ($input['type'] == 3) {
            $this->editCourseTextInfo($input);
        }

        if ($input['type'] == 4 || $input['type'] == 5) {
            $this->editCourseAvinfo($input);
            $this->editCourseTextInfo($input);
        }

        $this->model->query("COMMIT");
        return array(200, "资源编辑成功");
    }

    // type为音视频
    public function editCourseAvinfo($input)
    {

        if (empty($input['pathCount'])) {
            $param = array(
                "source_id" => $input['id'],
                "path" => $input['source_path'],
                "subtitle" => $input['title'],
            );
            $addInfo = $this->data_add("good_course_info", $param);
            if (empty($addInfo)) {
                $this->model->query("ROLLBACK");
                return array(202, "网络出错资源上传失败");
            }
        } else {
            for ($i=0; $i<$input['pathCount']; $i++) {
                $param = array(
                    "source_id" => $input['id'],
                    "path" => $input['pathList_'.$i],
                    "subtitle" => $input['subtitle_'.$i],
                );
                $addInfo = $this->data_add("good_course_info", $param);
                if (empty($addInfo)) {
                    $this->model->query("ROLLBACK");
                    return array(202, "网络出错资源上传失败");
                }
            }
            
        }
    }


    // 编辑文本
    public function editCourseTextInfo($input)
    {
        for ($i=0; $i<$input['textCount']; $i++) {
            $param = array(
                "source_id" => $input['id'],
                "content" => $input['textList_'.$i],
                "created_at" => time(),
            );
            $addText = $this->data_add("good_course_text", $param);
            if (empty($addText)) {
                $this->model->query("ROLLBACK");
                return array(202, "网络出错资源上传失败");
            }
        }
    }



    // 
    public function addCourse($input, $addid, $data)
    {
        $this->model->query("START TRANSACTION");
        // 添加主资源
        $addid = $this->data_add("good_course", $data);
        if (empty($addid)) {
            $this->model->query("ROLLBACK");
            return array(202, "网络出错资源上传失败");
        }

        if ($input['type'] == 1 || $input['type'] == 2) {
            $this->courseAvInfo($input, $addid);
        }

        if ($input['type'] == 3) {
            $this->courseTextInfo($input, $addid);
        }

        if ($input['type'] == 4 || $input['type'] == 5) {
            $this->courseAvInfo($input, $addid);
            $this->courseTextInfo($input, $addid);
        }

        $this->model->query("COMMIT");
        return array(200, "资源保存成功");
    }


    // 音视频的
    public function courseAvInfo($input ,$addid)
    {
        if (empty($input['pathCount'])) {
            $param = array(
                "source_id" => $addid,
                "path" => $input['source_path'],
                "subtitle" => $input['title'],
            );
            $addInfo = $this->data_add("good_course_info", $param);
            if (empty($addInfo)) {
                $this->model->query("ROLLBACK");
                return array(202, "网络出错资源上传失败");
            }
        } else {
            for ($i=0; $i<$input['pathCount']; $i++) {
                $param = array(
                    "source_id" => $addid,
                    "path" => $input['pathList_'.$i],
                    "subtitle" => $input['subtitle_'.$i],
                );
                $addInfo = $this->data_add("good_course_info", $param);
                if (empty($addInfo)) {
                    $this->model->query("ROLLBACK");
                    return array(202, "网络出错资源上传失败");
                }
            }
            
        }
    }

    // 文本的
    public function courseTextInfo($input, $addid)
    {
        for ($i=0; $i<$input['textCount']; $i++) {
            $param = array(
                "source_id" => $addid,
                "content" => $input['textList_'.$i],
                "created_at" => time(),
            );
            $addText = $this->data_add("good_course_text", $param);
            if (empty($addText)) {
                $this->model->query("ROLLBACK");
                return array(202, "网络出错资源上传失败");
            }
        }
    }


    /**
     * @param $input
     * @param $files
     * @return array
     * 打卡资源的验证
     */
    // 验证
    public function punch_checkFile($input,$files)
    {
        if (empty($input['title'])) {
            return array(202, "打卡素材名称不能为空");
        }

        if (intval($input['type']) <= 0) {
            return array(202, "请选择打卡素材类型");
        }

        if (empty($input['description'])) {
            return array(202, "打卡素材描述不能为空");
        }

        if (! empty($input['view_count'])) {
            if (! is_numeric($input['view_count'])) {
                return array(202, "资源浏览数必须为数字");
            }
        }
        if (empty($input['fileList']) && empty($files['picFile'])) {
            return array(202, "请上传打卡素材缩略图");
        }

        if (empty($input['label'])) {
            return array(202, '请选择素材的标签');
        }

        if (empty($input['created_at'])) {
            return array(202, '请选择素材上架的时间');
        }
        if ($input['type'] == 1) {
            if (empty($input['htmltext'])) {
                return array(202, '请上传图文信息');
            }
        }

        if ($input['type'] != 1 && $input['type'] != 4) {

            if (empty($input['source_path'])) {
                return array(202, "请上传打卡素材链接");
            }

            // if (empty($input['notice'])) {
            //     return array(202, "提示词不能为空");
            // }

            // if (empty($input['words'])) {
            //     return array(202, "生词汇总不能为空");
            // }

            if (empty($input['answer'])) {
                return array(202, "标准答案不能为空");
            }

            if (!empty($input['pathCount'])) {
                for ($i=0; $i<$input['pathCount']; $i++) {
                    if (empty($input['pathList_'.$i])) {
                        return array(202, "资源链接不能有空选项");
                    }

                    if (empty($input['subtitle_'.$i])) {
                        return array(202, "分段资源的标题不能有空选项");
                    }

                }
            }
        }

        if ($input['type'] == 4) {
            if (empty($input['textCount'])) {
                return array(202, "文本内容不能为空");
            }

            for ($i=0; $i<$input['textCount']; $i++) {
                if (empty($input['textList_'.$i])) {
                    return array(202, "文本内容不能存在空选项");
                }
            }
        }

        return array();
    }

    public function punch_addSource($input, $addid, $data)
    {
        $this->model->query("START TRANSACTION");
        // 添加主资源
        $addid = $this->data_add("punch_card", $data);
        if (empty($addid)) {
            $this->model->query("ROLLBACK");
            return array(202, "网络出错资源上传失败");
        }

        if ($input['type'] == 2 || $input['type'] == 3) {
            $this->punch_avInfo($input, $addid);
        }

        if ($input['type'] == 4) {
            $this->punch_textInfo($input, $addid);
        }

        $this->model->query("COMMIT");
        return array(200, "打卡资源保存成功");
    }

    // 音视频的
    public function punch_avInfo($input ,$addid)
    {
        if (empty($input['pathCount'])) {
            $param = array(
                "source_id" => $addid,
                "path" => $input['source_path'],
                "subtitle" => $input['subtitle'],
            );
            $addInfo = $this->data_add("punch_card_av", $param);
            if (empty($addInfo)) {
                $this->model->query("ROLLBACK");
                return array(202, "网络出错资源上传失败");
            }
        } else {
            for ($i=0; $i<$input['pathCount']; $i++) {
                $param = array(
                    "source_id" => $addid,
                    "path" => $input['pathList_'.$i],
                    "subtitle" => $input['subtitle_'.$i],
                );
                $addInfo = $this->data_add("punch_card_av", $param);
                if (empty($addInfo)) {
                    $this->model->query("ROLLBACK");
                    return array(202, "网络出错资源上传失败");
                }
            }

        }
    }

    // 文本的
    public function punch_textInfo($input, $addid)
    {
        for ($i=0; $i<$input['textCount']; $i++) {
            $param = array(
                "source_id" => $addid,
                "content" => $input['textList_'.$i],
            );
            $addText = $this->data_add("punch_card_text", $param);
            if (empty($addText)) {
                $this->model->query("ROLLBACK");
                return array(202, "网络出错资源上传失败");
            }
        }
    }



    // 编辑资源
    public function punch_editSource($input, $data)
    {
        $this->model->query("START TRANSACTION");
        $editSource = $this->data_edit("punch_card", $data, " id = '" . $input['id'] . "' ");
        // 添加主资源
        if (empty($editSource)) {
            $this->model->query("ROLLBACK");
            return array(202, "网络出错资源上传失败");
        }
        // 删除音频或者视频或者文本
        $delText = $this->data_del("punch_card_av", " source_id = '" . $input['id'] . "'");
        $delInfo = $this->data_del("punch_card_text", " source_id = '" . $input['id'] . "'");
        if (empty($delText) || empty($delInfo)) {
            $this->model->query("ROLLBACK");
            return array(202, "网络出错资源编辑失败");
        }
        // 编辑文本或者音视频信息
        if ($input['type'] == 2 || $input['type'] == 3) {
            $this->punch_editAvinfo($input);
        }

        if ($input['type'] == 4) {
            $this->punch_editTextInfo($input);
        }

        $this->model->query("COMMIT");
        return array(200, "资源编辑成功");
    }

    // type为音视频
    public function punch_editAvinfo($input)
    {

        if (empty($input['pathCount'])) {
            $param = array(
                "source_id" => $input['id'],
                "path" => $input['source_path'],
                "subtitle" => $input['title'],
            );
            $addInfo = $this->data_add("punch_card_av", $param);
            if (empty($addInfo)) {
                $this->model->query("ROLLBACK");
                return array(202, "网络出错资源上传失败");
            }
        } else {
            for ($i=0; $i<$input['pathCount']; $i++) {
                $param = array(
                    "source_id" => $input['id'],
                    "path" => $input['pathList_'.$i],
                    "subtitle" => $input['subtitle_'.$i],
                );
                if ($input['source_ids_'.$i] && is_numeric($input['source_ids_'.$i])) {
                    $param['id'] = $input['source_ids_'.$i];
                }
                $addInfo = $this->data_add("punch_card_av", $param);
                if (empty($addInfo)) {
                    $this->model->query("ROLLBACK");
                    return array(202, "网络出错资源上传失败");
                }
            }

        }
    }

    // 编辑文本
    public function punch_editTextInfo($input)
    {
        for ($i=0; $i<$input['textCount']; $i++) {
            $param = array(
                "source_id" => $input['id'],
                "content" => $input['textList_'.$i],
                "created_at" => time(),
            );
            if ($input['text_ids_'.$i] && is_numeric($input['text_ids_'.$i])) {
                $param['id'] = $input['text_ids_'.$i];
            }

            $addText = $this->data_add("punch_card_text", $param);
            if (empty($addText)) {
                $this->model->query("ROLLBACK");
                return array(202, "网络出错资源上传失败");
            }
        }
    }
}