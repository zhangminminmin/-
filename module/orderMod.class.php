<?php
/**
 * 订单模块
 * 
 */
class orderMod extends commonMod 
{
    protected $userinfo;
    public function __construct() 
    {
        parent::__construct();
    }

    /**
     * 检测登录
     */
    public function checkLogin()
    {
        if (empty($_SESSION['user_id'])) {
            $this->ajaxReturn(203, "未登录");
        }

        $userinfo = $this->data_getinfo("user", "id='" . $_SESSION['user_id'] . "'");
//        if (empty($userinfo['nickname']) || empty($userinfo['avatar'])) {
//            $this->ajaxReturn(401, "请先去个人中心完善资料");
//        }

        $this->userinfo = $userinfo;
    }

    /*
    *   生成订单号
    */
    public function getOrderSn($user_id,$type=1){
        $table = $type == 1 ? 'user_order' : 'order'; // 1购买会员  2购买课程
        $orderNo = date("YmdHis".mt_rand(100000,999999).'_'.$user_id);
        if($this->data_getinfo($table,array('order_sn'=>$orderNo))){
            $this->getOrderSn($user_id);
        }else{
            return $orderNo;
        }   
    }


    /**
     * 购买会员页面数据接口
     */
    public function userConfig()
    {
        $siteurl = "http://" . $this->config['siteurl'];
        $list = $this->data_list("form_data_user_config", "where id > 0");
        $userConfig = array();
        if (! empty($list)) {
            foreach($list as $k => $val) {
                $userConfig[] = array(
                    "id" => (int)$val['id'],
                    "userDemo" => $val['user_demo'],
                    "limitnum" => $val['limitnum'],
                    "price" => $val['price'],
                    'code' => $val['code']
                );
            }
        }
        // 会员权益
        $userVip = $this->data_list("form_data_user_vip", "where id > 0", "order by id desc", "limit 1");
        $content = empty($userVip) ? "" : getImgThumbUrl($userVip[0]['content'], $siteurl);
        $param = array(
            "userConfig" => $userConfig,
            "userVip" => $content,
        );

        $this->ajaxReturn(200, "获取会员购买配置成功", $param);
    }

    /**
     * 购买会员 
     * 生成订单
     * param - 购买套餐的id userConfigId
     */
    public function userOrder()
    {
        $this->checkLogin();
        $input = in($_POST);
        $userinfo = $this->userinfo;

        if ($userinfo['type'] == 3) {
            $this->ajaxReturn(202, '已是永久会员 无须购买！');
        }

        if (empty($input['userConfigId'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }

        $userConfigInfo = $this->data_getinfo("form_data_user_config", " id = '" . $input['userConfigId'] . "'");
        if (empty($userConfigInfo)) {
            $this->ajaxReturn(202, "购买的套餐不存在或者已下架");
        }

        $order_sn = $this->getOrderSn($_SESSION['user_id'], 1);
        $data = array(
            "order_sn" => $order_sn,
            "user_id" => $_SESSION['user_id'],
            "price" => $userConfigInfo['price'] * 100,
            "limitnum" => $userConfigInfo['limitnum'],
            "created_at" => time(),
            "status" => 1,
            "demo" => $userConfigInfo['user_demo'],
        );

        $addOrder = $this->data_add("user_order", $data);
        if (! $addOrder) {
            $this->ajaxReturn(202, "网络原因请刷新重试");
        } else {
            $param = array(
                "order_id" => $addOrder,
            );
            $this->ajaxReturn(200, "订单生成成功", $param);
        }
    }


    /**
     * 生成购买的课程的订单
     * course_id 课程的id
     */
    public function courseOrder()
    {
        $this->checkLogin();
        $input = in($_POST);
        if (empty($input['course_id'])) {
            $this->ajaxReturn(202, "参数错误请刷新重试");
        }
        $courseInfo = $this->data_getinfo("good_course", " id = '" . $input['course_id'] . "'");
        if (empty($courseInfo)) {
            $this->ajaxReturn(202, "视频课程不存在或已被下架");
        }

        if ($courseInfo['pid'] > 0) {
            $this->ajaxReturn(202, '非法操作！');
        }
        // 检查是否已经购买了课程
        $sql = "user_id = '" . $_SESSION['user_id'] . "' and good_course_id = '" . $input['course_id'] . "' and status = 2";
        $orderInfo = $this->data_getinfo("order", $sql);
        if (! empty($orderInfo)) {
            $param = array(
                "orderId" => $orderInfo['id'],
            );
            $this->ajaxReturn(200, "此课程已经购买 请勿重复购买", $param);
        }

        // 检查订单是否有此课程未支付的
        $sql = "user_id = '" . $_SESSION['user_id'] . "' and good_course_id = '" . $input['course_id'] . "' and status = 1";
        $orderInfo = $this->data_getinfo("order", $sql);
        if (! empty($orderInfo)) {
            $this->ajaxReturn(202, "此课程已经生成订单 请前去个人中心我的课程进行支付！");
        }

        $order_sn = $this->getOrderSn($_SESSION['user_id'], 1);
        $data = array(
            "order_sn" => $order_sn,
            "user_id" => $_SESSION['user_id'],
            "price" => $courseInfo['price'],
            "good_course_id" => $input['course_id'],
            "created_at" => time(),
            "status" => 1,
            "title" => $courseInfo['title'],
            "image" => $courseInfo['image'],
        );        

        $addCourseOrder = $this->data_add("order", $data);
        if(empty($addCourseOrder)) {
            $this->ajaxReturn(202, "网络原因请刷新重试");
        } else {
            $param = array(
                "addCourseOrder" => $addCourseOrder,
            );
            $this->ajaxReturn(200, "课程订单生成成功！", $param);
        }
    }

    /**
     * IOS 内购接口
     * trade_no 订单号 订单的id
     * receipt_data 核销凭证
     * is_sandbox 0 正式   1沙箱
     */
    public function iosPay()
    {
        $this->checkLogin();
        $trade_no = in($_POST['trade_no']);
        $receipt_data = in($_POST['receipt_data']);
        $is_sandbox = in($_POST['is_sandbox']);

        if (!$trade_no) {
            $this->ajaxReturn(202, '订单信息异常#1');
        }
        if (!$order_info = $this->data_getinfo('user_order', 'id = ' . $trade_no)) {
            $this->ajaxReturn(202, '订单信息异常！');
        }

        if ($order_info['status'] != 1) {
            $this->ajaxReturn(202, '订单状态已经改变！');
        }
        // 验证支付状态 修改订单状态！ 增加用户余额 ！ 生成流水！包括充送的金额！
        $info = $this->data_getinfo('user_order', " receipt_data='" . $receipt_data . "'");
        if ($info) {
            $this->ajaxReturn(202, '凭证已经使用 购买失败！');
        }

        $result = $this->validate_apple_pay($receipt_data,$is_sandbox);

        if ($result['status']) {
            $this->model->query('START TRANSACTION');
            try {
                $data = [
                    'type' => 3,
                    'status' => 2,
                    'receipt_data' => $receipt_data,
                ];
                if (! $this->data_edit('user_order',$data, ' id = ' . $order_info['id'])) {
                    throw new \Exception('订单状态更新失败！');
                }

                // 更新会员的时间  会员的状态
                $userinfo = $this->data_getinfo("user", " id = '" . $order_info['user_id'] . "'");
                if (!$userinfo) {
                    $this->fileLog("userOrder", date("Ymd"), "订单" . $trade_no . "会员信息不存在");
                    throw new \Exception('会员信息不存在');
                }

                // 如果等于1  代表是普通会员  2猩听译会员  3终身会员

                if ($order_info['limitnum'] < 0) {
                    $data = [
                        'type' => 3,
                    ];
                    $editUser = $this->data_edit("user", $data, " id = '" . $userinfo['id'] . "'");
                    if (empty($editUser)) {
                        $this->fileLog("userOrder", date("Ymd"), "订单号：" . $trade_no . "会员状态更新失败");
                        throw new \Exception('会员状态更新失败！');
                    }
                }else{
                    $endtime = empty($userinfo['endtime']) ? 0 : $userinfo['endtime'];
                    $editEndtime = 0;

                    $endtime = ($endtime > time()) ? $endtime : time();
                    $editEndtime = strtotime("+". $order_info['limitnum'] ." months", $endtime);

                    $editEndtime = strtotime(date("Y-m-d 23:59:59", $editEndtime));
                    $data = array("type" => 2, "endtime" => $editEndtime);
                    $editUser = $this->data_edit("user", $data, " id = '" . $userinfo['id'] . "'");
                    if (empty($editUser)) {
                        $this->fileLog("userOrder", date("Ymd"), "订单号：" . $trade_no . "会员状态更新失败");
                        throw new \Exception('会员状态更新失败！');
                    }
                }
                $this->model->query('COMMIT');
            }catch(\Exception $e){
                $this->model->query('ROLLBACK');
                $this->ajaxReturn(202, $e->getMessage());
            }
            $this->ajaxReturn(200, '支付成功');
        }else{
            $this->ajaxReturn(202, $result['message']);
        }
    }

    /**
     * 验证AppStore内付
     * @param  string $receipt_data 付款后凭证
     * @return array                验证是否成功
     */
    protected function validate_apple_pay($receipt_data,$is_sandbox){
        /**
         * 21000 App Store不能读取你提供的JSON对象
         * 21002 receipt-data域的数据有问题
         * 21003 receipt无法通过验证
         * 21004 提供的shared secret不匹配你账号中的shared secrte
         * 21005 receipt服务器当前不可用
         * 21006 receipt合法，但是订阅已过期。服务器接收到这个状态码时，receipt数据仍然会解码并一起发送
         * 21007 receipt是Sandbox receipt，但却发送至生产系统的验证服务
         * 21008 receipt是生产receipt，但却发送至Sandbox环境的验证服务
         */

        function acurl($receipt_data, $sandbox=0){
            //小票信息
            $POSTFIELDS = array("receipt-data" => $receipt_data);
            $POSTFIELDS = json_encode($POSTFIELDS);

            //正式购买地址 沙盒购买地址
            $url_buy     = "https://buy.itunes.apple.com/verifyReceipt";
            $url_sandbox = "https://sandbox.itunes.apple.com/verifyReceipt";
            $url = $sandbox ? $url_sandbox : $url_buy;

            //简单的curl
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $POSTFIELDS);
            $result = curl_exec($ch);
            curl_close($ch);
            return $result;
        }
        // 验证参数
        if (strlen($receipt_data)<20){
            $result=array(
                'status'=>false,
                'message'=>'非法参数'
            );
            return $result;
        }
        // 请求验证
        $base64_decode_info = base64_decode($receipt_data);
        $html = acurl($receipt_data, $is_sandbox);
        $data = json_decode($html,true);

        // 如果是沙盒数据 则验证沙盒模式
        if($data['status']=='21007'){
            // 请求验证
            $html = acurl($receipt_data, 0);
            $data = json_decode($html,true);
            $data['sandbox'] = '1';
        }

        if (isset($_GET['debug'])) {
            exit(json_encode($data));
        }

        // 判断是否购买成功
        if(intval($data['status'])===0){
            $result=array(
                'status'=>true,
                'message'=>'购买成功'
            );
        }else{
            $result=array(
                'status'=>false,
                'message'=>'购买失败 status:'.$data['status']
            );
        }
        return $result;
    }
}