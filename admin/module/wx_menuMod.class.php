<?php

/*
 * @自定义菜单管理后台控制器
 * @Copyright  ahaiba.com
 * @Author  Jeff<jeffchou@qq.com>    2016-9-21
 * @Version 1.0
 */

class wx_menuMod extends commonMod {

    public function __construct() {
        parent::__construct();
    }

    public function index() {
        if (!model('user_group')->model_power('wx_menu', 'visit')) {
            $this->msg('没有权限！', 0);
        }
        if (model('user_group')->model_power('wx_menu', 'edit')) {
            $this->edit_power = true;
        }
        if (model('user_group')->model_power('wx_menu', 'del')) {
            $this->del_power = true;
        }
        if (model('user_group')->model_power('wx_menu', 'add')) {
            $this->add_power = true;
        }

        $this->action = 'edit';

        $this->list = model('wx')->menu_list(array('pid' => 0));
        $this->show('wx_menu/index');
    }

    public function add() {
        $this->action_name = '新增自定义菜单';

        $this->pidlist = model('wx')->menu_list_all(array('pid' => 0));
        $this->action = 'add';
        $this->show('wx_menu/info');
    }

    public function add_save() {
        if (!model('user_group')->model_power('wx_menu', 'add')) {
            $this->msg('没有权限！', 0);
        }


        if (empty($_POST)) {
            $this->msg('参数出错！', 0);
        }

        model('wx')->wx_menuadd($_POST);
        $this->msg('添加成功！', 1);
    }

    public function edit() {
        $id = intval($_GET['id']);
        $this->action_name = '修改自定义菜单';

        $this->action = 'edit';
        $this->pidlist = model('wx')->menu_list_all(array('pid' => 0));
        $this->info = model('wx')->wx_menu_getinfo($id);
        $this->show('wx_menu/info');
    }

    public function edit_save() {
        if (!model('user_group')->model_power('wx_menu', 'edit')) {
            $this->msg('没有权限！', 0);
        }
        $id = intval($_POST['id']);
        if (empty($id)) {
            $this->msg('参数出错！', 0);
        }

        model('wx')->wx_menu_edit($id, $_POST);
        $this->msg('修改成功！', 1);
    }

    public function del() {
        if (!model('user_group')->model_power('wx_menu', 'del')) {
            $this->msg('没有权限！', 0);
        }
        $id = intval($_POST['id']);
        $this->alert_str($id, 'int', true);
        //判断是否有下级分类

        $pid = model('wx')->menu_list_all(array('pid' => $id));
        if (!empty($pid)) {
            $this->msg('请先删除子分类！', 0);
        } else {
            model('wx')->menu_del($id);
            $this->msg('删除成功！', 1);
        }
    }

    public function class_send() {
        if (!model('user_group')->model_power('wx_menu', 'send')) {
            $this->msg('没有权限！', 0);
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $access_token = getAccessToken($this->config['WX_appid'], $this->config['WX_appsecret']);
            if (!$access_token) {
                $this->msg('获取access_token发生错误', 0);
            }
           
            $data = '{"button":[';

            $class = model('wx')->menu_list_get(array('pid' => 0, 'is_show' => 1), 3);

            $kcount = model('wx')->menu_list_get_count(0, 3);
            $k = 1;
            foreach ($class as $key => $vo) {
                //主菜单
                $data.='{"name":"' . $vo['title'] . '",';
                $c = model('wx')->menu_list_get(array('pid' => $vo['id'], 'is_show' => 1), 5);
                $count = model('wx')->menu_list_get_count($vo['id'], 5);


                //子菜单
                $vo['url'] = str_replace(array('&amp;'), array('&'), $vo['url']);
                if (!empty($c)) {
                    $data.='"sub_button":[';
                } else {
                    if (!$vo['url']) {
                        $data.='"type":"click","key":"' . $vo['keyword'] . '"';
                    } else {
                        $data.='"type":"view","url":"' . $vo['url'] . '"';
                    }
                }
                $i = 1;
                if (!empty($c)) {
                    foreach ($c as $voo) {
                        $voo['url'] = str_replace(array('&amp;'), array('&'), $voo['url']);
                        if ($i == $count) {
                            if ($voo['url']) {
                                $data.='{"type":"view","name":"' . $voo['title'] . '","url":"' . $voo['url'] . '"}';
                            } else {
                                $data.='{"type":"click","name":"' . $voo['title'] . '","key":"' . $voo['keyword'] . '"}';
                            }
                        } else {
                            if ($voo['url']) {
                                $data.='{"type":"view","name":"' . $voo['title'] . '","url":"' . $voo['url'] . '"},';
                            } else {
                                $data.='{"type":"click","name":"' . $voo['title'] . '","key":"' . $voo['keyword'] . '"},';
                            }
                        }
                        $i++;
                    }
                }
                if (!empty($c)) {
                    $data.=']';
                }

                if ($k == $kcount) {
                    $data.='}';
                } else {
                    $data.='},';
                }
                $k++;
            }
            $data.=']}';

            file_get_contents('https://api.weixin.qq.com/cgi-bin/menu/delete?access_token=' . $access_token);

            $url = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token=' . $access_token;
            $rt = $this->api_notice_increment($url, $data);

            if ($rt['rt'] == false) {
                $this->msg('操作失败,curl_error:' . $rt['errorno'], 0);
            } else {
                $this->msg('生成菜单成功！', 0);
            }
            exit;
        } else {
            $this->msg('非法操作！', 0);
        }
    }

    function api_notice_increment($url, $data) {
        $ch = curl_init();
        $headers = array('Accept-Charset: utf-8');

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible;MSIE 5.01;Windows NT 5.0)');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $tmpInfo = curl_exec($ch);
        $errorno = curl_errno($ch);
        if ($errorno) {
            return array('rt' => false, 'errorno' => $errorno);
        } else {
            $js = json_decode($tmpInfo, 1);
            if ($js['errcode'] == '0') {
                return array('rt' => true, 'errorno' => 0);
            } else {
                $this->msg('发生错误：错误代码' . $js['errcode'] . ',微信返回错误信息：' . $js['errmsg'], 0);
            }
        }
    }

    function curlGet($url) {
        $ch = curl_init();
        $headers = array('Accept-Charset: utf-8');
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible;MSIE 5.01;Windows NT 5.0)');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $temp = curl_exec($ch);
        return $temp;
    }

}