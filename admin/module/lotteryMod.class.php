<?php

/*
 * @游戏管理后台控制器
 * @Copyright  ahaiba.com
 * @Author  Jeff<jeffchou@qq.com>    2016-9-21
 * @Version 1.0
 */

class lotteryMod extends commonMod {

    public function __construct() {
        parent::__construct();
    }

    public function index($type = 1) {

        $where = array('type' => $type);
        $this->type = $type;
        $this->list = model('lottery')->list_select($where);
        $this->count = model('lottery')->count($where);
        $this->show('/lottery/index');
    }

    public function add($type = 1) {
        if ($type == 1) {
            $activeType = 'lottery';
        } else {
            $activeType = 'guajiang';
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = model('lottery');
            $_POST['statdate'] = strtotime($_POST['statdate']);
            $_POST['enddate'] = strtotime($_POST['enddate']);
            $_POST['type'] = $_POST['type'];
            $_POST['createtime'] = strtotime(time());
            $_POST['status'] = 0;
            if ($_POST['enddate'] < $_POST['statdate']) {
                $this->success('结束时间不能小于开始时间', '', 0, 0);
            } else {
                if (empty($_POST['title']) || empty($_POST['sttxt']) || empty($_POST['statdate']) || empty($_POST['enddate']) || empty($_POST['info']) || empty($_POST['endtite']) || empty($_POST['endinfo']) || empty($_POST['fist']) || empty($_POST['fistnums']) || empty($_POST['allpeople'])) {
                    $this->success('信息不完整', '', 0, 0);
                }
                if ($data->add($_POST)) {
                    $this->success('活动创建成功，请在列表中让活动“开始”', '/admin/index.php/' . $activeType . '/');
                } else {
                    $this->success('服务器繁忙,请稍候再试', '', 0, 0);
                }
            }
        } else {
            $now = time();
            $this->type = $type;
            $lottery['statdate'] = $now;
            $lottery['enddate'] = $now + 30 * 24 * 3600;
            $this->assign('vo', $lottery);
            if ($type == 1) {
                $this->show('lottery/add');
            } else {
                $this->show('lottery/guajiang_add');
            }
        }
    }

    public function edit($type = 1) {//大转盘编辑
        if ($type == 1) {
            $activeType = 'lottery';
        } else {
            $activeType = 'guajiang';
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = model('lottery');
            $_POST['id'] = intval($_POST['id']);
            $_POST['statdate'] = strtotime($_POST['statdate']);
            $_POST['enddate'] = strtotime($_POST['enddate']);
            $_POST['createtime'] = strtotime(time());
            //$_POST['status'] = 0;
            if ($_POST['enddate'] < $_POST ['statdate']) {
                $this->success('结束时间不能小于开始时间', '', 0, 0);
            } else {
                $where = array('id' => $_POST['id'], 'type' => $type);
                $check = $data->getinfo_arr($where);
                if ($check == false)
                    $this->success('非法操作', '', 0, 0);

                if (empty($_POST['title']) || empty($_POST['sttxt']) || empty($_POST['statdate']) || empty($_POST['enddate']) || empty($_POST['info']) || empty($_POST['endtite']) || empty($_POST['endinfo']) || empty($_POST['fist']) || empty($_POST['fistnums']) || empty($_POST['allpeople'])) {
                    $this->success('信息不完整', '', 0, 0);
                }



                if ($data->edit_lottery($where, $_POST)) {
                    $this->success('修改成功', '/admin/index.php/' . $activeType . '/', 1);
                } else {
                    $this->success('操作失败', '', 0, 0);
                }
            }
        } else {
            $id = $_GET['id'];
            $where = array('id' => $id);
            $data = model('lottery');
            $check = $data->getinfo_arr($where);
            if ($check == false)
                $this->success('非法操作', '', 0, 0);

            $this->assign('vo', $check);
            $this->type = $type;
            if ($type == 1) {
                $this->show('lottery/add');
            } else {
                $this->show('lottery/guajiang_add');
            }
        }
    }

    public function endLottery() {
        $id = $_GET['id'];
        $where = array('id' => $id);
        $data = model('lottery')->edit_lottery($where, array('status' => 0));
        if ($data != false) {
            $this->success('活动已结束');
        } else {
            $this->success('服务器繁忙,请稍候再试', '', 0, 0);
        }
    }

    public function startLottery() {
        $id = $_GET['id'];
        $rt = $this->_start($id);
        if ($rt > 0) {
            $this->success('活动已经开始');
        } else {
            switch ($rt) {
                case -2:
                    $this->success('服务器繁忙,请稍候再试', '', 0, 0);
                    break;
            }
        }
    }

    public function _start($id) {
        $error = 0;
        $where = array('id' => $id);

        $data = model('lottery')->edit_lottery($where, array('status' => 1));

        if ($data != false) {
            return 1;
        } else {
            $error = -2;
        }
        return $error;
    }

    public function del() {
        $id = intval($_GET['id']);
        $where = array('id' => $id);
        $data = model('lottery');
        $check = $data->getinfo_arr($where);

        if ($check == false)
            $this->success('非法操作', '', 0, 0);
        $back = $data->del($id);
        if ($back == true) {
            $this->success('删除成功');
        } else {
            $this->success('操作失败', '', 0, 0);
        }
    }

    public function sn($type = 1) {//获奖SN码
        $Lottery_record_db = model('Lottery');
        $id = intval($_GET['id']);
        $this->type = $type;
        $data = model('lottery')->getinfo($id);
        $this->assign('thisLottery', $data);
        $recordWhere = 'islottery=1 and lid=' . $id;
        $record_list = $Lottery_record_db->list_select_record($recordWhere);
        $record_jilu = array();
        $phone = array();
        if ($record_list) {
            foreach ($record_list as $k => $v) {
                if (!in_array($v['wecha_id'], $record_jilu)) {
                    $record[$k] = $v;
                }
                $record_jilu[] = $v['wecha_id'];
            }
        }
        $recordcount = $data['fistlucknums'] + $data ['secondlucknums'] + $data ['thirdlucknums'] + $data ['fourlucknums'] + $data ['fivelucknums'] + $data ['sixlucknums'];
        $datacount = $data['fistnums'] + $data ['secondnums'] + $data ['thirdnums'] + $data ['fournums'] + $data ['fivenums'] + $data ['sixnums'];
//
        $sendCount = $Lottery_record_db->lottery_record_count(array('lid' => $id, 'sendstutas' => 1, 'islottery' => 1));
        $this->assign('sendCount', $sendCount);
        $this->assign('datacount', $datacount);
        $this->assign('recordcount', $recordcount);

        if ($record) {
            $i = 0;
            foreach ($record as $r) {
                switch (intval($r['prizetype'])) {

                    default:
                        $record[$i]['prizeName'] = $r['prize'];
                        break;
                    case 1:
                        $record[$i]['prizeName'] = $data['fist'];
                        break;
                    case 2:
                        $record[$i]['prizeName'] = $data['second'];
                        break;
                    case 3:
                        $record[$i]['prizeName'] = $data['third'];
                        break;
                    case 4:
                        $record[$i]['prizeName'] = $data['four'];
                        break;
                    case 5:
                        $record[$i]['prizeName'] = $data['five'];
                        break;
                    case 6:
                        $record[$i]['prizeName'] = $data['six'];
                        break;
                    case 7:
                        $activeType = 'AppleGame';
                        break;
                    case 8:
                        $activeType = 'Lovers';
                        break;
                    case 9:
                        $activeType = 'Autumn';
                        break;
                    case 10:
                        $activeType = 'Jiugong';
                        break;
                }
                $i++;
            }
        }
        $this->assign('list', $record);
        if ($type == 1) {
            $this->show('lottery/sn');
        } else {
            $this->show('lottery/guajiang_sn');
        }
    }

    public function sendprize() {//发奖
        $id = intval($_GET['id']);
        $where = array('id' => $id);
        $data['sendtime'] = time();
        $data['sendstutas'] = 1;
        $back = model('lottery')->edit($where, $data);
        if ($back == true) {
            $this->success('操作成功');
        } else {
            $this->success('操作失败', '', 0, 0);
        }
    }

    public function sendnull() {//取消发奖
        $id = intval($_GET['id']);
        $where = array('id' => $id);
        $data['sendtime'] = '';
        $data['sendstutas'] = 0;
        $back = model('lottery')->edit($where, $data);
        if ($back == true) {
            $this->success('已经取消');
        } else {
            $this->success('操作失败', '', 0, 0);
        }
    }

    public function snDelete() {
        $db = model('lottery');
        $rt = $db->getinfo_record(intval($_GET['id']));
        switch ($rt['prize']) {
            case 1:
                model('lottery')->edit_lottery(array('id' => $rt['lid']), array('fistlucknums' => ($rt['fistlucknums'] - 1)));
                break;
            case 2:
                model('lottery')->edit_lottery(array('id' => $rt['lid']), array('secondlucknums' => ($rt['secondlucknums'] - 1)));
                break;
            case 3:
                model('lottery')->edit_lottery(array('id' => $rt['lid']), array('thirdlucknums' => ($rt['thirdlucknums'] - 1)));
                break;
            case 4:
                model('lottery')->edit_lottery(array('id' => $rt['lid']), array('fourlucknums' => ($rt['fourlucknums'] - 1)));
                break;
            case 5:
                model('lottery')->edit_lottery(array('id' => $rt['lid']), array('fivelucknums' => ($rt['fivelucknums'] - 1)));
                break;
            case 6:
                model('lottery')->edit_lottery(array('id' => $rt['lid']), array('sixlucknums' => ($rt['sixlucknums'] - 1)));
                break;
            case 7:
                $activeType = 'AppleGame';
                break;
            case 8:
                $activeType = 'Lovers';
                break;
            case 9:
                $activeType = 'Autumn';
                break;
            default :
                $this->success('操作失败', '', 0, 0);
        }
        $db->del_record(intval($_GET['id']));
        $this->success('操作成功');
    }

    public function exportSN() {//导出
        header("Content-Type: text/html; charset=utf-8");
        header("Content-type:application/vnd.ms-execl");
        header("Content-Disposition:filename=huojiang.xls");
//   以下\t代表横向跨越一格，\n 代表跳到下一行，可以根据自己的要求，增加相应的输出相，要和循环中的对应哈
//字段
        $letterArr = explode(',', strtoupper('a,b,c,d,e,f,g'));
        $arr = array(
            array('en' => 'sn', 'cn' => 'SN码(中奖号)'),
            array('en' => 'prize', 'cn' => '奖项'),
            array('en' => 'sendstutas', 'cn' => '是否已发奖品'),
            array('en' => 'sendtime', 'cn' => '奖品发送时间'),
            array('en' => 'phone', 'cn' => '中奖者手机号'),
            array('en' => 'wecha_name', 'cn' => '中奖者微信码'),
            array('en' => 'time', 'cn' => '中奖时间'),
        );
        $chengItem = array('piaomianjia', 'shuifei', 'yingshoujine', 'yingfupiaomianjia', 'yingfushuifei', 'yingfujine', 'dailishouru', 'fandian', 'jichangjianshefei', 'ranyoufei');

        $i = 0;
        $fieldCount = count($arr);
        $s = 0;
// thead
        foreach ($arr as $f) {
            if ($s < $fieldCount - 1) {
                echo iconv('utf-8', 'gbk', $f['cn']) . "\t";
            } else {
                echo iconv('utf-8', 'gbk', $f['cn']) . "\n";
            }
            $s++;
        }
//
        $db = model('lottery');
        $sns = $db->list_select_record(array('lid' => intval($_GET['id']), 'islottery' => 1));
        if ($sns) {

            foreach ($sns as $sn) {
                $j = 0;
                foreach ($arr as $field) {
                    $fieldValue = $sn[$field['en']];
                    switch ($field['en']) {
                        default:
                            break;
                        case 'time':
                        case 'sendtime':
                            if ($fieldValue) {
                                $fieldValue = date('Y-m-d H:i:s', $fieldValue);
                            } else {
                                $fieldValue = '';
                            }
                            break;
                        case 'wecha_name':
                        case 'prize':
                            $fieldValue = iconv('utf-8', 'gbk', $fieldValue);
                            break;
                    }
                    if ($j < $fieldCount - 1) {
                        echo $fieldValue . "\t";
                    } else {
                        echo $fieldValue . "\n";
                    }
                    $j++;
                }
                $i++;
            }
        }
        exit();
    }

}