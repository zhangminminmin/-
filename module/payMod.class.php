<?php

/*
    支付宝支付
    微信支付
*/

class payMod extends commonMod{
    
    public $uid;
    public $siteurl;
    public function __construct(){
        parent::__construct();
        //检测是否登录
        $this->siteurl = 'https://'.$this->config['siteurl'];
    }
    
    protected function checkLogin(){
        if(!$_SESSION['user_id']){
            ajaxReturn(203,'您尚未登录，请先登录');
        }
        $this->uid = $_SESSION['user_id'];
        
    }
    
    /**
     * [type] [description]
     * pay_id 支付类型  1支付宝 2微信
     * order_id 订单id
     * type  类型  1购买会员  2购买课程
     * pay_client 支付端  1 APP端  2 PC端
     */
    public function getPayCode(){
        $this->checkLogin();
        $input = in($_POST);
        file_put_contents("1.txt", json_encode($input));
        $pay_id = intval($input['pay_id']); //支付类型ID  支付宝 微信
        
        if ($pay_id == 1) {
            // $this->ajaxReturn(202, "支付宝功能开发中,敬请期待...");
        }

        $order_id = intval($input['order_id']); //订单ID 订单的id
        $type = intval($input['type']); // 1代表购买会员  2代表购买课程
        $tb_url = empty($input['url']) ? "" : $this->siteurl . "/" . $input['url']. "1";

        $pay_client = intval($input['pay_client']); //支付端  1 APP  2 PC
        $this->model->query('START TRANSACTION');
        switch($type){
            case 1: //代表的是购买会员
                $order_info = $this->data_getinfo('user_order',array('id'=>$order_id));
                $userinfo = $this->data_getinfo("user", " id = '" . $order_info['user_id'] . "'");
                if (empty($userinfo)) {
                    $this->model->query('ROLLBACK');
                    $this->ajaxReturn(202, '购买的会员信息出错');
                }

                if ($userinfo['type'] == 3) {
                    $this->model->query("ROLLBACK");
                    $this->ajaxReturn(202, "此会员已经是终身会员，请勿重复操作");
                }

                if (!$order_info) {
                    $this->model->query('ROLLBACK');
                    $this->ajaxReturn(202, '订单信息异常，暂时无法支付');
                }
                // 更新支付方式
                $edit = $this->data_edit("user_order", array("type" => $pay_id), " id = '" . $order_id . "'");
                if (empty($edit)) {
                    $this->model->query('ROLLBACK');
                    $this->ajaxReturn(202, '订单信息异常，暂时无法支付');
                }
                if($order_info['status'] != 1) {
                    $this->model->query('ROLLBACK');
                    $this->ajaxReturn(202, '该订单状态已被更改，暂时无法支付');
                }
                
                $this->orderPayCode($order_info, $pay_id, $pay_client,$tb_url);
                break;
            case 2: // 2代表的是购买课程  status  1未支付  2已支付  3已取消
                $order_info = $this->data_getinfo("order", " id = '" . $order_id . "'");
                if (!$order_info) {
                    $this->model->query('ROLLBACK');
                    $this->ajaxReturn(202, '订单信息异常，暂时无法支付');
                }

                // 更新支付方式
                $edit = $this->data_edit("order", array("type" => $pay_id), " `id` = " . $order_id);
                
                if (!$edit) {
                    $this->model->query('ROLLBACK');
                    $this->ajaxReturn(202, '订单信息异常，暂时无法支付'. $pay_id.$order_id);
                }

                if($order_info['status'] == 2) {
                    $this->model->query('ROLLBACK');
                    $this->ajaxReturn(202, '该订单状态已被更改，暂时无法支付' . $order_info['status']);
                }

                $this->agentPayCode($order_info, $pay_id, $pay_client,$tb_url);
                break;
            default:    //其他
                $this->ajaxReturn(202, '非法订单类型');
                break;
        }
    }
    
    /**
     * 购买会员的订单
     */
    private function orderPayCode($order_info, $pay_id, $pay_client,$tburl){
        $product_code = $pay_client == 1 ? "QUICK_MSECURITY_PAY" : "FAST_INSTANT_TRADE_PAY";
        switch ($pay_id){
            case 1:
                $price = sprintf("%.2f", ($order_info['price'] / 100));
                $content = array(
                    "subject" => "猩听译 - 会员购买",
                    "out_trade_no" => $order_info['order_sn'],
                    "total_amount" => $price,
                    "product_code" => $product_code, //"QUICK_MSECURITY_PAY",//FAST_INSTANT_TRADE_PAY PC端支付
                    "goods_type" => 1,
                    "passback_params"=>urlencode("order"),
                );
                
                if ($pay_client == 1) { //app端
                    $result = $this->getAlipayCode($content);
                } else {
                    // $tburl = $this->siteurl.'/personal/members?is_pay=1';
                    $result = $this->getAlipayCodePc($content, $tburl);
                }
                $this->model->query('COMMIT');
                $this->ajaxReturn(200,'获取支付宝支付成功',$result);
                
                break;
            case 2:
                $content = array(
                    "attach"=>"1",
                    "body"=>'猩听译 - 会员购买',
                    "out_trade_no"=>$order_info['order_sn'],
                    "total_fee"=>$order_info['price'],
                );
                if ($pay_client == 1) {//app端
                    $result = $this->getWxpayCode($content, $pay_client);
                } else {
                    $result = $this->getNativeCode($content, $pay_client);
                }
                $this->model->query('COMMIT');
                $this->ajaxReturn(200,'获取微信支付成功',$result);
                break;
            case 3:
                $this->getUnionpayCode();
                break;
            default:
                break;
        }
    }
    
    private function agentPayCode($order_info, $pay_id, $pay_client,$tburl){
        $product_code = ($pay_client == 1) ? "QUICK_MSECURITY_PAY" : "FAST_INSTANT_TRADE_PAY";
        
        switch($pay_id){
            case 1:
                $content = array(
                    "subject"=> "猩听译 - 购买课程",
                    "out_trade_no"=> $order_info['order_sn'],
                    "total_amount"=> sprintf("%.2f", ($order_info['price'] / 100)),
                    "product_code"=> $product_code,
                    "goods_type"=> 0,
                    "passback_params"=> urlencode("course"),
                );
                if ($pay_client == 1) { //app端
                    $result = $this->getAlipayCode($content);
                } else {
                    // $tburl = $this->siteurl.'/personal/members?is_pay=1';
                    $result = $this->getAlipayCodePc($content, $tburl);
                }
                $this->model->query('COMMIT');
                $this->ajaxReturn(200,'获取支付宝支付成功',$result);
                break;
            case 2:
                $content = array(
                    "attach" => "2",
                    "body" => '猩听译 - 购买课程',
                    "out_trade_no" => $order_info['order_sn'],
                    "total_fee" => $order_info['price'],
                );
                if ($pay_client == 1) {
                    $result = $this->getWxpayCode($content, $pay_client);
                } else {
                    $result = $this->getNativeCode($content);
                }

                $this->model->query('COMMIT');
                $this->ajaxReturn(200,'获取微信支付成功',$result);
                break;
            case 3:
                break;
            default:
                $this->model->query('ROLLBACK');
                $this->ajaxReturn(400, '非法请求');
                break;
        }
    }
    
    private function getAlipayCode($content){
        $param_sign_arr = array(
            "app_id"=>"2021001100643112",
            "method"=>"alipay.trade.app.pay",
            "charset"=>"utf-8",
            "sign_type"=>"RSA2",
            "timestamp"=>date("Y-m-d H:i:s"),
            "biz_content"=>json_encode($content),
            "version"=>"1.0",
            "notify_url"=>$this->siteurl.'/index.php/pay/notifyAlipay',
        );
        
        ksort($param_sign_arr);
        $param_sign_str = urldecode(http_build_query($param_sign_arr));
        //echo $param_sign_str;
        $sign = null;
        $private_key = "MIIEowIBAAKCAQEAyCDZZSAw/DVBWtlgOPi4cXF+n1hBEZ6ogNVrbTmGMj5s2kyCBMhS2F2ou0wdjQxkwft9DZg6XqMYOrqjkjS8u+uxNiUAVGP6Rt1gBeGRb2/13VgBAcdIH6IsH8GI/NUfYtqN48jclum5KZSWiJRw+yPOKXYBHLB4/6JWU+ER6Vlmas6f+4FnAkm5XjIaQv0lLE8JvxpgHTDYPYYItlxJBBx9p/ucuQT/TjYZSvjycuSx3Yg5N6ta6fUHdyY93/ywIPeOFjqmYY34lAiD/uiiT5V1f/Wv63bDI25xSAXG5BLKu2qdhAVXdI2EK1UMfR7ENGLajiIKHF4h2cevANQscwIDAQABAoIBAETSDxBYjp/cjHn6cL2Gwp64YcvYJKAziEytl8C63GwgzXwQfVG5tcuUAbdPCIZ9sZSHsExhggkTWvyvPBrGKfURqyIsfT2IGAQQkrnTBRlmTg1s+wOqjSHbugK9oicX/zAWal7frwPyoesrnsyfB29Fs9rMKru78BAwujEkH+23ZM03d71rnColxaVTg53+Xz61yeZFWW54VmYOpN3G2kUkkwxg/f0GpvvOaIFBl5ErslDGpUTv83zwIbQKJAPeotdYtpjWR6cEBK+8e6Bb5atrK4hdWHCdTUFZe5E9h6/U2sz/cK+vk/90dKdRXf1xlhggCwQY8I4H/o4zYRRMdckCgYEA/6dU2YNEPjpPLOLdaNh54dIC8CBJxJoSKadGQkitank5o2AqsH2YLNqWZH66BPMiN8BxgN1v/oN0vvb9cD4cZSWBJtfz/joDb/rOsUsoBqu/ep0xjY5AjRCK0uvGZwf0upQPOb06DvDBZwRWsaXu6lg3lExOq3i5O3n4+rOeaZ0CgYEAyGZChm2o3BCHrTHhjs974IFCMwqWUOKM3eG37rnKz+l8igGdzXubqrlDFKNrjFvW1p3j15socXYfyq9wDum3DSHWrwPhWdGaGVPdg9Ck23xjNU+ZW6vUvYMkiWrU31PY+Eo6CBtwZxfYvnQ/HvUiYmZRHCihti8jlM1mmFECWU8CgYBHh+k8xva0Nppqo7txl4hav+kkiNQ7FyTX1L7a0vz6mpqe9Mxc/3cxraOA0Nh5hBHh6Y2YkdzBKMTknbUBz88gGeNOHARf8Jl/nnxwrOexHiMASrVPtyqmjadaJrAnqdk2zzCHZkO1ZHWovlUHdbcoiLBcZRFp9uhVFOauNs2vHQKBgQC9W5Mkvt2+A4iQaP+/F+LvwnOxEyHyEAAXcL+CPeCcEP3y0ZAwp8nxydjO2ZQ4LleUt+CgzCWtAHcN6DQ0gzbKm9zlxv7bBilcIVjAwxfjpWeA6lP4wSbmY9LUXjQDSpFMG6c2HedaIrhVTjaOpAb3f8LInsQfB+/RHT0CVBmqFQKBgBxsccdb3A6AT7wKZfRBLzN52n21pTh5X4xd/lvWEcPJ15T5KNa3jYPvpcB0dOE8Xqfr26kGZLOzt2EF17CzXlKO3GIZH9by0BYP/YbWQ/+AiH9jfBnjVHbGkSSHwOpxgUy3bE7khUlQ2L3BnKytFJJJnK5MFXa75R1t4ZgekAMo";
        
        $private_key = chunk_split($private_key, 64, "\n");
    
        $private_key = "-----BEGIN RSA PRIVATE KEY-----\n$private_key-----END RSA PRIVATE KEY-----\n";
        $d = openssl_sign($param_sign_str, $sign, $private_key, SHA256); //OPENSSL_ALGO_SHA256
        $sign = base64_encode($sign);
        //echo $sign;
        $param_sign_arr['sign'] = $sign;
        
        $param_str = http_build_query($param_sign_arr);
        return array("param_str" => $param_str);
    }

    private function getAlipayCodePc($content, $return_url){
        require_once __ROOTDIR__ . '/plugins/alipay/AopSdk.php';
        $aop = new AopClient ();
        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = '2021001100643112';
        $aop->rsaPrivateKey = 'MIIEowIBAAKCAQEAyCDZZSAw/DVBWtlgOPi4cXF+n1hBEZ6ogNVrbTmGMj5s2kyCBMhS2F2ou0wdjQxkwft9DZg6XqMYOrqjkjS8u+uxNiUAVGP6Rt1gBeGRb2/13VgBAcdIH6IsH8GI/NUfYtqN48jclum5KZSWiJRw+yPOKXYBHLB4/6JWU+ER6Vlmas6f+4FnAkm5XjIaQv0lLE8JvxpgHTDYPYYItlxJBBx9p/ucuQT/TjYZSvjycuSx3Yg5N6ta6fUHdyY93/ywIPeOFjqmYY34lAiD/uiiT5V1f/Wv63bDI25xSAXG5BLKu2qdhAVXdI2EK1UMfR7ENGLajiIKHF4h2cevANQscwIDAQABAoIBAETSDxBYjp/cjHn6cL2Gwp64YcvYJKAziEytl8C63GwgzXwQfVG5tcuUAbdPCIZ9sZSHsExhggkTWvyvPBrGKfURqyIsfT2IGAQQkrnTBRlmTg1s+wOqjSHbugK9oicX/zAWal7frwPyoesrnsyfB29Fs9rMKru78BAwujEkH+23ZM03d71rnColxaVTg53+Xz61yeZFWW54VmYOpN3G2kUkkwxg/f0GpvvOaIFBl5ErslDGpUTv83zwIbQKJAPeotdYtpjWR6cEBK+8e6Bb5atrK4hdWHCdTUFZe5E9h6/U2sz/cK+vk/90dKdRXf1xlhggCwQY8I4H/o4zYRRMdckCgYEA/6dU2YNEPjpPLOLdaNh54dIC8CBJxJoSKadGQkitank5o2AqsH2YLNqWZH66BPMiN8BxgN1v/oN0vvb9cD4cZSWBJtfz/joDb/rOsUsoBqu/ep0xjY5AjRCK0uvGZwf0upQPOb06DvDBZwRWsaXu6lg3lExOq3i5O3n4+rOeaZ0CgYEAyGZChm2o3BCHrTHhjs974IFCMwqWUOKM3eG37rnKz+l8igGdzXubqrlDFKNrjFvW1p3j15socXYfyq9wDum3DSHWrwPhWdGaGVPdg9Ck23xjNU+ZW6vUvYMkiWrU31PY+Eo6CBtwZxfYvnQ/HvUiYmZRHCihti8jlM1mmFECWU8CgYBHh+k8xva0Nppqo7txl4hav+kkiNQ7FyTX1L7a0vz6mpqe9Mxc/3cxraOA0Nh5hBHh6Y2YkdzBKMTknbUBz88gGeNOHARf8Jl/nnxwrOexHiMASrVPtyqmjadaJrAnqdk2zzCHZkO1ZHWovlUHdbcoiLBcZRFp9uhVFOauNs2vHQKBgQC9W5Mkvt2+A4iQaP+/F+LvwnOxEyHyEAAXcL+CPeCcEP3y0ZAwp8nxydjO2ZQ4LleUt+CgzCWtAHcN6DQ0gzbKm9zlxv7bBilcIVjAwxfjpWeA6lP4wSbmY9LUXjQDSpFMG6c2HedaIrhVTjaOpAb3f8LInsQfB+/RHT0CVBmqFQKBgBxsccdb3A6AT7wKZfRBLzN52n21pTh5X4xd/lvWEcPJ15T5KNa3jYPvpcB0dOE8Xqfr26kGZLOzt2EF17CzXlKO3GIZH9by0BYP/YbWQ/+AiH9jfBnjVHbGkSSHwOpxgUy3bE7khUlQ2L3BnKytFJJJnK5MFXa75R1t4ZgekAMo';
        $aop->alipayrsaPublicKey='MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAl9d0kHefGzT38MGyWV1e34vR39Hw1wKxaqeSrynGaKc/Q/CZQI6cw6T85D4Oap7Jc/RCcSIHPPm9LCaoXD+MCqoM8UMoQbTIiZQyYb7JF+9t9fbdPR1iSiyehjrp5+r5m5HdqMDk85k8rcyPbmckrLEZlwrFV7iLoCNha54x9yvaCgJ5/RWVNMybLClKGBlJVbJEiTX74mitqMj/DlrPCwz0TDUlkj/Gi3ICR3uFLyljoYyPSdP4rS5SyaZnW4OQ88B8o1sHKQciLFufgxiA2AYbn+tge9OBGxPLkpSny/odJVXLduID/d9NaMsCn7qQEN8//KGOqKR8cih628SJUwIDAQAB';
        $aop->apiVersion = '1.0';
        $aop->postCharset='UTF-8';
        $aop->format='json';
        $aop->signType='RSA2';
        $request = new AlipayTradePagePayRequest();
        $request->setNotifyUrl($this->siteurl.'/index.php/pay/notifyAlipay');
        $request->setReturnUrl($return_url);
        $content = (json_encode($content));
        $request->setBizContent($content);
        $result = $aop->pageExecute ($request); 
        $this->model->query('COMMIT');
        ajaxReturn(200, '获取支付宝支付成功', $result);
    }
    
    private function getWxpayCode($content, $pay_client){
        $trade_type = $pay_client == 1 ? "APP" : "NATIVE";

        $nonce_str = getRandChar(32);
        $ip = get_client_ip();
        $key = "FECxkXddv9RNXlXTZf2R5ED5AteTSi4H";
        $param_sign_arr = array(
            "appid"=>"wx62eac9bacf5cd990",
            "mch_id"=>"1520905111",
            "nonce_str"=>$nonce_str,
            "attach"=>$content['attach'],
            "body"=>$content['body'],
            "out_trade_no"=>$content['out_trade_no'],
            "total_fee"=>$content['total_fee'],
            "spbill_create_ip"=>$ip,
            "notify_url"=>$this->siteurl.'/index.php/pay/notifyWxpay',
            "trade_type"=> $trade_type // APP  NATIVE pc端支付
        );
        ksort($param_sign_arr);
        $param_sign_str = urldecode(http_build_query($param_sign_arr)).'&key='.$key;
        $sign = strtoupper(md5($param_sign_str));
        $param_sign_arr['sign'] = $sign;
        $data = arrayToXml($param_sign_arr);
        $result = httpGet("https://api.mch.weixin.qq.com/pay/unifiedorder",'post',$data);
        $result = xmlToArray($result);
        if($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS'){
            $prepay_id = $result['prepay_id'];
            $nonce_str = $result['nonce_str'];
        }else{
            $this->model->query('ROLLBACK');
            $this->ajaxReturn(202,$result['err_code_des']);
        }
        $return = array(
            "appid"=>"wx62eac9bacf5cd990",
            "partnerid"=>"1520905111",
            "prepayid"=>$prepay_id,
            "noncestr"=>$nonce_str,
            "timestamp"=>time(),
            "package"=>"Sign=WXPay",
        );
        ksort($return);
        //echo urldecode(http_build_query($return)).'&key='.$key;
        $sign = strtoupper(md5(urldecode(http_build_query($return)).'&key='.$key));
        $return['sign'] = $sign;
        return $return;
    }

    private function getNativeCode($content)
    {
        $siteurl = "http://".$this->config['siteurl'];
        include __ROOTDIR__.'/plugins/WxpayAPI_php_v3/WxPay.NativePay.php';
        $input = new WxPayUnifiedOrder();
        $notify = new NativePay();
        $input->SetBody($content['body']);
        $input->SetAttach($content['attach']);
        $input->SetOut_trade_no($content['out_trade_no']);
        $input->SetTotal_fee($content['total_fee']);
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetGoods_tag($content['body']);
        $input->SetNotify_url($siteurl.'/index.php/pay/notifyWxpay');
        $input->SetTrade_type("NATIVE");
        $input->SetProduct_id($content['out_trade_no']);
        $result = $notify->GetPayUrl($input);
        //var_dump($result);
        if($result['result_code'] == 'SUCCESS' && $result['return_code'] == 'SUCCESS'){
            $url2 = $result["code_url"];
        }else{
            $this->ajaxReturn(202, $result['err_code_des'], $result);
        }
        //echo $url2;
        $return = array(
            'type'=>'url',
            'html'=> $url2,
        );
        
        $this->model->query('COMMIT');
        $this->ajaxReturn(200, '调取支付成功', $return);
    }

    public function notifyAlipay() {
        $input = in($_POST);        //获取异步参数
        file_put_contents("6666666666.txt", $input);
        if(empty($input)) {
            return false;
            exit;
        }
        if(!(in_array($input['trade_status'], array('TRADE_SUCCESS', 'TRADE_FINISHED')))) {
            return false;
            exit('failure');
        }
        /* $input = '{"gmt_create":"2018-05-16 16:28:05","charset":"utf-8","seller_email":"mzsc0807@163.com","subject":"\u660e\u73e0\u5546\u57ce - \u5546\u54c1\u8d2d\u4e70","sign":"MCRYeSWDvWVu4dvfY5eaxDmDfkxrbbSiHGkypdf8wxvDafMlgjCAykUFtJ8+fpvzo1GwoSwwUuWfb7z\/aX9NeTiYEAgOWC57dFo+sbvpigM78T1vYovSvVVQ6gJhMt6U08IyPwC4tvuhV63d0j7kdnkTlfQjsLZJl9gG15Q0orhz7d99ru9Qzw3gpy1lkDuIKthvKNYnnkX5o\/GOSwL8xTXUYyPUOzIzrnpCY2MnqImBPT\/NCWglPulk+oB\/dm4Y\/shGuB7j5smXKmwVzP2BPquRfK\/UToCgzHarcOndEgpqXyCB3GPtLx\/z4ODEX8CsyZ1WylTBYL1HCV74ht6Nxw==","buyer_id":"2088612936838512","invoice_amount":"0.01","notify_id":"723508e5e97f8c6c8698ec7a204ea26jxt","fund_bill_list":"[{&quot;amount&quot;:&quot;0.01&quot;,&quot;fundChannel&quot;:&quot;ALIPAYACCOUNT&quot;}]","notify_type":"trade_status_sync","trade_status":"TRADE_SUCCESS","receipt_amount":"0.01","app_id":"2018040202490191","buyer_pay_amount":"0.01","sign_type":"RSA2","seller_id":"2088031654948772","gmt_payment":"2018-05-16 16:28:05","notify_time":"2018-05-16 16:28:06","version":"1.0","out_trade_no":"2018051614539662_4","total_amount":"0.01","trade_no":"2018051621001004510573489468","auth_app_id":"2018040202490191","buyer_logon_id":"112***@qq.com","point_amount":"0.00"}'; */
        // $this->data_add('test_log', array('content'=>json_encode($input)));

        //记录微信回调日志
        $sign = base64_decode($input['sign']);
        $param = array();
        foreach($input as $key => $val){
            if(in_array($key, array('sign', 'sign_type'))) {
                continue;
            }
            $param[$key] = urldecode($val);
        }
        ksort($param);
        $param_str = htmlspecialchars_decode(urldecode(http_build_query($param)));
        $alipay_public_key = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAl9d0kHefGzT38MGyWV1e34vR39Hw1wKxaqeSrynGaKc/Q/CZQI6cw6T85D4Oap7Jc/RCcSIHPPm9LCaoXD+MCqoM8UMoQbTIiZQyYb7JF+9t9fbdPR1iSiyehjrp5+r5m5HdqMDk85k8rcyPbmckrLEZlwrFV7iLoCNha54x9yvaCgJ5/RWVNMybLClKGBlJVbJEiTX74mitqMj/DlrPCwz0TDUlkj/Gi3ICR3uFLyljoYyPSdP4rS5SyaZnW4OQ88B8o1sHKQciLFufgxiA2AYbn+tge9OBGxPLkpSny/odJVXLduID/d9NaMsCn7qQEN8//KGOqKR8cih628SJUwIDAQAB';
        $alipay_public_key = chunk_split($alipay_public_key, 64, "\n");
        $alipay_public_key = "-----BEGIN PUBLIC KEY-----\n$alipay_public_key-----END PUBLIC KEY-----\n";
        $alipay_public_key = openssl_get_publickey($alipay_public_key);
        $result = openssl_verify($param_str, $sign, $alipay_public_key, OPENSSL_ALGO_SHA256);
        if($result == false){
            exit('failure');
        }
        //验签通过，执行逻辑操作
        $type = $input['passback_params'];
        $order_sn = $input['out_trade_no'];
        if($type == 'order') {  //如果是购买会员
            $result = $this->notifyOrder($order_sn, 1);
        } else {    //如果是购买课程
            $result = $this->notifyCourse($order_sn, 1);
        }
        if($result[0] == 200){
            echo 'success';
        }else{
            echo 'failure';
        }
    }
    
    public function notifyWxpay(){
        require_once __ROOTDIR__.'/plugins/WxpayAPI_php_v3/lib/WxPay.Api.php';
        require_once __ROOTDIR__.'/plugins/WxpayAPI_php_v3/lib/WxPay.Notify.php';
        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
        file_put_contents("10.txt", $xml);
        if(!$xml){
            return false;
        }
        //         $xml = '<xml><appid><![CDATA[wx51f6a0a79e10997b]]></appid>
        // <attach><![CDATA[1]]></attach>
        // <bank_type><![CDATA[OTHERS]]></bank_type>
        // <cash_fee><![CDATA[1]]></cash_fee>
        // <fee_type><![CDATA[CNY]]></fee_type>
        // <is_subscribe><![CDATA[N]]></is_subscribe>
        // <mch_id><![CDATA[1503515941]]></mch_id>
        // <nonce_str><![CDATA[5oadz2qv3wv8yyb3fygczmmj7bb9usa0]]></nonce_str>
        // <openid><![CDATA[o5goM1OOUV0FXNgz4CHvNAhS287o]]></openid>
        // <out_trade_no><![CDATA[20191212144954440747_15]]></out_trade_no>
        // <result_code><![CDATA[SUCCESS]]></result_code>
        // <return_code><![CDATA[SUCCESS]]></return_code>
        // <sign><![CDATA[DB858BA600AF65C3181600303E3FA592]]></sign>
        // <time_end><![CDATA[20191212145007]]></time_end>
        // <total_fee>1</total_fee>
        // <trade_type><![CDATA[NATIVE]]></trade_type>
        // <transaction_id><![CDATA[4200000442201912120080177844]]></transaction_id>
        // </xml>'; 
        // $this->data_add('test_log',array('content'=>$xml));     //记录微信回调日志
        //如果返回成功则验证签名
        try {
            $result = WxPayResults::Init($xml);
        } catch (WxPayException $e){
            $msg = $e->errorMessage();
            return false;
        }
        if($result['result_code'] != 'SUCCESS' || $result['return_code'] != 'SUCCESS') {
            $this->fileLog('payLog',date("Ymd"),'用户支付失败，订单号：'.$result['out_trade_no'].'，微信交易号：'.$result['transaction_id']."，时间：".date("Y-m-d H:i:s"));
            echo 'fail';
            exit;
        }
        
        if($result['attach'] == 1) {
            $return = $this->notifyOrder($result['out_trade_no'], 2);
        } else {
            $return = $this->notifyCourse($result['out_trade_no'], 2);
        }
        if($return[0] == 200){
            echo 'success';
        }else{
            echo 'fail';
        }
    }


    // 购买会员的回调
    // order_sn 
    // $order_sn, $type
    public function notifyOrder($order_sn, $type)
    {
        $this->model->query("START TRANSACTION");
        $sql = "SELECT * FROM {$this->model->pre}user_order WHERE order_sn = '{$order_sn}' FOR UPDATE";
        $result = $this->model->query($sql);
        if (empty($result)) {
            $this->model->query("ROLLBACK");
            $this->fileLog("userOrder", date('Ymd'), "订单号" . $order_sn .  "不存在");
            return array(202, "订单号不存在");
        }

        if ($result[0]['status'] != 1) {
            $this->model->query("ROLLBACK");
            $this->fileLog("userOrder", date("Ymd"), "订单" . $order_sn . "状态已改变");
            return array(202, "订单状态已经被更改 无法支付");
        }

        // 更改订单支付状态
        $editOrder = $this->data_edit("user_order", array("status" => 2, "type" => $type), " id = '" . $result[0]['id'] . "'");
        if (empty($editOrder)) {
            $this->model->query("ROLLBACK");
            $this->fileLog("userOrder", date("Ymd"), "订单" . $order_sn . "状态更新失败");
            return array(202, "订单状态已经被更改 无法支付");
        }

        // 更新会员的时间  会员的状态
        $userinfo = $this->data_getinfo("user", " id = '" . $result[0]['user_id'] . "'");
        if (empty($userinfo)) {
            $this->model->query("ROLLBACK");
            $this->fileLog("userOrder", date("Ymd"), "订单" . $order_sn . "会员信息不存在");
            return array(202, "会员信息不存在");
        }

        // 如果等于1  代表是普通会员  2猩听译会员  3终身会员

        if ($result[0]['limitnum'] < 0) {
            $data = [
                'type' => 3,
            ];
            $editUser = $this->data_edit("user", $data, " id = '" . $userinfo['id'] . "'");
            if (empty($editUser)) {
                $this->fileLog("userOrder", date("Ymd"), "订单号：" . $order_sn . "会员状态更新失败");
                throw new \Exception('会员状态更新失败！');
            }
        }else{
            $endtime = empty($userinfo['endtime']) ? 0 : $userinfo['endtime'];
            $editEndtime = 0;

            $endtime = ($endtime > time()) ? $endtime : time();
            $editEndtime = strtotime("+". $result[0]['limitnum'] ." months", $endtime);

            $editEndtime = strtotime(date("Y-m-d 23:59:59", $editEndtime));
            $data = array("type" => 2, "endtime" => $editEndtime);
            $editUser = $this->data_edit("user", $data, " id = '" . $userinfo['id'] . "'");
            if (empty($editUser)) {
                $this->model->query("ROLLBACK");
                $this->fileLog("userOrder", date("Ymd"), "订单号：" . $order_sn . "会员状态更新失败");
                return array(202, "会员状态更新失败");
            }
        }

        $this->model->query("COMMIT");
        return array(200, "支付成功");
    }
    

    /**
     * 购买课程回调
     * order_sn
     * type 1 支付宝支付  2 微信支付
     */
    public function notifyCourse($order_sn, $type)
    {
        $this->model->query("START TRANSACTION");
        $sql = "SELECT * FROM {$this->model->pre}order WHERE order_sn = '{$order_sn}' FOR UPDATE";
        $result = $this->model->query($sql);
        $result = $result[0];
        // 订单是否存在
        if (empty($result)) {
            $this->model->query("ROLLBACK");
            $this->fileLog("order", date("Ymd"), "订单" . $order_sn . "不存在");
            return array(202, "订单不存在");
        }
        // 状态
        if($result['status'] != 1 && $result['status'] != 3) {
            $this->model->query("ROLLBACK");
            $this->fileLog("order", date("Ymd"), "订单" . $order_sn . "状态已经被更改");
            return array(202, "订单状态已经被更改");
        }  

        //  更改支付状态
        $data = array("status" => 2, "type" => $type);
        $editOrder = $this->data_edit("order", $data, " id = '" . $result['id'] . "'");
        if (empty($editOrder)) {
            $this->model->query("ROLLBACK");
            $this->fileLog("order", date("Ymd"), "订单" . $order_sn . "状态更新失败");
        }

        // 更改购买人数
        $editGoodsCourse = $this->data_self_add("good_course", "buynum", 1, " where  id = '" . $result['good_course_id'] . "'");
        $this->model->query("COMMIT");
        return array(200, "支付成功");
    }


    /**
     * 用于pc端轮循查询订单状态
     * order_id 订单号
     * type 1购买会员   2购买课程
     */
    public function checkOrder()
    {
        $input = $this->post;
        $this->checkLogin();
        if (empty($input['order_id'])) {
            $this->ajaxReturn(202, "参数错误，刷新重试");
        }

        $type = intval($input['type']) > 0 ? $input['type'] : 0;
        $order_info = array();

        $sql =  " id = '" . $input['order_id'] . "' and user_id = '". $_SESSION['user_id'] ."'";
        switch($type) {
            case 1:
                $order_info = $this->data_getinfo("user_order", $sql);
                break; 
            case 2:
                $order_info = $this->data_getinfo("order" , $sql);
                break;
            default:
                $this->ajaxReturn(202, "非法操作");
                break;
        }

        if (empty($order_info)) {
            $this->ajaxReturn(202, "订单信息异常");
        }

        if ($order_info['status'] == 2) {
            $this->ajaxReturn(200, "支付成功");
        } else {
            $this->ajaxReturn(200, "支付回调中..", 1);
        }
    }
    // 测试回调是否正确
    public function test()
    {
        $s = $this->notifyCourse("154814578485_9", 1);
        print_r($s);
    }
}