<?php

//邮件发送类,基于PHPMailer类
class Email {

    public $config = array(); //存储配置的静态变量

    //设定邮件参数

	public function __construct() {
        $this->config['SMTP_HOST'] = 'smtp.mxhichina.com'; //smtp服务器地址
        $this->config['SMTP_PORT'] = 465; //smtp服务器端口
        $this->config['SMTP_SSL'] = true; //是否启用SSL安全连接	，gmail需要启用sll安全连接
        $this->config['SMTP_USERNAME'] = 'System@readingbay.com'; //smtp服务器帐号，如：你的qq邮箱
        $this->config['SMTP_PASSWORD'] = '(IJN0okm'; //smtp服务器帐号密码，如你的qq邮箱密码
        $this->config['SMTP_AUTH'] = true; //启用SMTP验证功能，一般需要开启
        $this->config['SMTP_CHARSET'] = 'utf-8'; //发送的邮件内容编码	
        $this->config['SMTP_FROM_TO'] = 'System@readingbay.com'; //发件人邮件地址
        $this->config['SMTP_FROM_NAME'] = '书湾商城'; //发件人姓名
        $this->config['SMTP_DEBUG'] = false; //是否显示调试信息	
    }

    //发送邮件
     public function send($mail_to, $mail_subject, $mail_body, $mail_attach = NULL) {
        @error_reporting(E_ERROR | E_WARNING | E_PARSE); //屏蔽出错信息
        require_once(dirname(__FILE__) . '/phpmailer/class.phpmailer.php');
        $mail = new PHPMailer();
        //没有调用配置方法，则调用一次config方法

        $mail->IsSMTP(); //// 使用SMTP方式发送
        $mail->Host = $this->config['SMTP_HOST']; //smtp服务器地址
        $mail->Port = $this->config['SMTP_PORT'];    //smtp服务器端口
        $mail->Username = $this->config['SMTP_USERNAME']; //smtp服务器帐号，
        $mail->Password = $this->config['SMTP_PASSWORD'];  // smtp服务器帐号密码
        $mail->SMTPAuth = $this->config['SMTP_AUTH']; //启用SMTP验证功能，一般需要开启
        $mail->CharSet = $this->config['SMTP_CHARSET']; //发送的邮件内容编码	
        $mail->SetFrom($this->config['SMTP_FROM_TO'], $this->config['SMTP_FROM_NAME']); // 发件人的邮箱和姓名
        $mail->AddReplyTo($this->config['SMTP_FROM_TO'], $this->config['SMTP_FROM_NAME']); // 回复时的邮箱和姓名，一般跟发件人一样
        //是否启用SSL安全连接	
        if ($this->config['SMTP_SSL']) {
            $mail->SMTPSecure = "ssl"; //gmail需要启用sll安全连接
        }
        //开启调试信息
        if ($this->config['SMTP_DEBUG']) {
            $mail->SMTPDebug = 1;
        }

        $mail->Subject = $mail_subject; //邮件标题
        $mail->MsgHTML($mail_body); //邮件内容，支持html代码
        //发送邮件
        if (is_array($mail_to)) {
            //同时发送给多个人
            foreach ($mail_to as $key => $value) {
                $mail->AddAddress($value, "");  // 收件人邮箱和姓名
            }
        } else {  //只发送给一个人
            $mail->AddAddress($mail_to, "");  // 收件人邮箱和姓名
        }

        //发送多个附件
        if (is_array($mail_attach)) {
            foreach ($mail_attach as $value) {
                if (file_exists($value)) {//附件必须存在，才会发送
                    $mail->AddAttachment($value); // attachment
                }
            }
        }
        //发送一个附件
        if (!empty($mail_attach) && is_string($mail_attach)) {

            if (file_exists($mail_attach)) {//附件必须存在，才会发送
                $mail->AddAttachment($mail_attach); //发送附件
            }
        }

        if (!$mail->Send()) {
            if ($this->config['SMTP_DEBUG']) {
                echo "Mailer Error: " . $mail->ErrorInfo;
            }
            return false;
        } else {
            return true;
        }
    }

}

?>