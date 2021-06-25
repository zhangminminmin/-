<?php

/*
 * @项目比例管理后台模型
 * @Copyright  ahaiba.com
 * @Author  Jeff<jeffchou@qq.com>    2016-9-21
 * @Version 1.0
 */

class wxModel extends commonModel {

    public function __construct() {
        parent::__construct();
    }

    public function pic_list($limit) {
        $sql = "
        SELECT *
        FROM {$this->model->pre}img     
        ORDER BY id DESC,sort asc
        LIMIT {$limit} 
        ";
        return $this->model->query($sql);
    }

    public function pic_count() {
        $sql = "
        SELECT count(*) as num
        FROM {$this->model->pre}img
      
        ";
        $data = $this->model->query($sql);
        return $data[0]['num'];
    }

    public function menu_list_all($w) {
        return $this->model->table('diymen_class')->where($w)->select();
    }

    public function menu_list_get($w, $li) {
        return $this->model->table('diymen_class')->where($w)->order('sort')->select($li);
    }

    public function menu_list_get_count($pid, $li) {
        $sql = "
        SELECT count(*) as num
        FROM {$this->model->pre}diymen_class
        where pid=" . $pid . " AND is_show=1
        limit " . $li;
        $data = $this->model->query($sql);
        return $data[0]['num'];
    }

    public function menu_list($w) {
        $list = $this->model->table('diymen_class')->where($w)->order('sort')->select();
        $arr = array();
        if (empty($list)) {
            return $arr;
        }
        foreach ($list as $vo) {
            $arr[$vo['id']]['id'] = $vo['id'];
            $arr[$vo['id']]['pid'] = $vo['pid'];
            $arr[$vo['id']]['title'] = $vo['title'];
            $arr[$vo['id']]['keyword'] = $vo['keyword'];
            $arr[$vo['id']]['url'] = $vo['url'];
            $arr[$vo['id']]['is_show'] = $vo['is_show'];
            $arr[$vo['id']]['sort'] = $vo['sort'];
            $arr[$vo['id']]['pidlist'] = $this->menu_list_c(array('pid' => $vo['id']));
        }
        return $arr;
    }

    public function menu_list_c($w) {
        return $this->model->table('diymen_class')->where($w)->order('sort')->select();
    }

    public function getc($value, $cid, $id = 0) {
        if ($id > 0) {
            $cc = ' and id<>' . $id;
        }
        return $this->model->table('project')->where("pid=" . $cid . " and `range`='" . $value['range'] . "'" . $cc)->find();
    }

    //获取详细内容
    public function wx_text_getinfo() {
        return $this->model->table('areply')->find();
    }

    public function wx_pic_getinfo($id) {
        return $this->model->table('img')->where(array('id' => $id))->find();
    }

    public function wx_menu_getinfo($id) {
        return $this->model->table('diymen_class')->where(array('id' => $id))->find();
    }

    //修改内容
    public function wx_text_edit($id, $value) {
        $d = array('keyword' => $value['keyword'], 'content' => $value['content'], 'home' => $value['home'], 'updatetime' => time());
        return $this->model->table('areply')->data($d)->where('id=' . $id)->update();
    }

    public function wx_pic_edit($id, $value) {
        $d = array('keyword' => in($value['keyword']), 'text' => in($value['text']), 'pic' => $value['pic'], 'info' => html_in($value['info']), 'title' => in($value['title']), 'url' => in($value['url']), 'sort' => $value['sort'], 'uptatetime' => time());
        $this->model->table('img')->data($d)->where('id=' . $id)->update();

        return $this->model->table('keyword')->data(array('keyword' => in($value['keyword'])))->where('pid=' . $id)->update();
    }

    //添加内容
    public function wx_picadd($value) {
        $d = array('keyword' => in($value['keyword']), 'type' => $value['type'], 'text' => in($value['text']), 'pic' => $value['pic'], 'info' => html_in($value['info']), 'title' => in($value['title']), 'url' => in($value['url']), 'sort' => $value['sort'], 'createtime' => time(), 'uptatetime' => time());
        $id = $this->model->table('img')->data($d)->insert();

        if (!empty($id)) {
            if ($value['type'] == 1) {
                $type = 'Img';
            } else {
                $type = 'Text';
            }
            $this->model->table('keyword')->data(array('keyword' => in($value['keyword']), 'pid' => $id, 'module' => $type))->insert();
        }
        return $id;
    }

    public function wx_menuadd($value) {
        $d = array('keyword' => in($value['keyword']), 'pid' => $value['pid'], 'title' => in($value['title']), 'url' => $value['url'], 'is_show' => intval($value['is_show']), 'sort' => intval($value['sort']));
        $id = $this->model->table('diymen_class')->data($d)->insert();
        return $id;
    }

    public function wx_menu_edit($id, $value) {
        $d = array('keyword' => in($value['keyword']), 'pid' => $value['pid'], 'title' => in($value['title']), 'url' => $value['url'], 'is_show' => intval($value['is_show']), 'sort' => intval($value['sort']));
        return $this->model->table('diymen_class')->data($d)->where('id=' . $id)->update();
    }

    public function pic_del($id) {

        $status = $this->model->table('img')->where('id=' . $id)->delete();
        $this->model->table('keyword')->where('pid=' . $id)->delete();
        return $status;
    }

    public function menu_del($id) {

        $status = $this->model->table('diymen_class')->where('id=' . $id)->delete();
        //
        return $status;
    }

    public function tdcode_list() {
        return $this->model->table('tdcode')->select();
    }

    public function tdcode_get($id) {
        return $this->model->table('tdcode')->where('id=' . $id)->find();
    }

    public function tdcode_up($d, $id) {
        return $this->model->table('tdcode')->where('id=' . $id)->data($d)->update();
    }

    public function tdcode_add($value, $scene_str, $cid) {
        $d = array('title' => in($value), 'scene_str' => in($scene_str), 'cid' => intval($cid));
        $id = $this->model->table('tdcode')->data($d)->insert();
        return $id;
    }

    public function tdcode_del($id) {

        $status = $this->model->table('tdcode')->where('id=' . $id)->delete();
        //
        return $status;
    }

}