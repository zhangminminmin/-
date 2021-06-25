<?php
/*!===================================
* Create: 2016.10.28
* Author: Jeff (jeff.chou@aliyun.com)
* Description： 公共类 数据操作Helper
* ==================================== */
class commonModel
{
    protected $model = NULL; //数据库模型
    protected $config = array();
    private $_data = array();
	protected $tablename = NULL;
	
    protected function init(){}
    
    public function __construct($tablename = ''){
        global $config;
        session_start();
        $config['PLUGIN_PATH']=__ROOTDIR__.'/plugins/';
        $this->config = $config;
        $this->model = self::initModel( $this->config);
        $this->cache = self::initCache( $this->config);
        $this->init();
        Plugin::init('Admin',$config);
        $langCon=Lang::langCon();
        $this->config = array_merge((array)$config,(array)$langCon);
		
		if(!empty($tablename)) {
			$this->tablename = $tablename;
		}
    }


    //初始化缓存
    static public function initCache($config){
        static $cache = NULL;
        if( empty($cache) ){
            $cache = new cpCache($config);
        }
        return $cache;
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

    //插件接口
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

    //替换插件接口
    public function plus_hook_replace($module,$action,$data=NULL)
    {
        $hook_replace=$this->plus_hook($module,$action,$data,true);
        if(!empty($hook_replace)){
            return $hook_replace;
        }else{
            return $data;
        }
    }

    //提示
    public function msg($message,$status=1) {
        echo json_encode(array('status' => $status, 'message' => $message));
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
}