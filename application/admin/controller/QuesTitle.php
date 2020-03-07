<?php
namespace dashen\admin\controller;

use think\Controller;
use think\Db;
use think\Request;
use think\Session;
//需求名称
class QuesTitle extends Controller
{
    public function _initialize()
    {
        //过滤字符串
        Request::instance()->filter(['strip_tags', 'trim']);
    }

    public function index(){
        return $this->fetch('index');
    }
    //展示function
    public function quesTitleList()
    {
        $d = db("ques_title")->select();
        $this->assign('list',$d);
        return $this->fetch("list");
    }

    public function add(){
        return $this->fetch('add');
    }
    public function update($id){
        $d=db("ques_title")->where('id',$id)->find();
        //dump($d);
        $this->assign('d',$d);
        return $this->fetch('update');
    }

    public function doupdate(){
        $d=$_POST;
        $id=$d['id'];
        unset($d['id']);
        db('ques_title')->where('id',$id)->update($d);
        return $this->success('修改成功','QuesTitle/quesTitleList');

    }
    //增加
    public function doadd(){
        $d=$_POST;
        db('ques_title')->insert($d);
        $this->success('新增成功', 'QuesTitle/quesTitleList');
    }
    public function delete($id){
        $msg=db('ques_title')->delete($id);
        $this->success('删除成功','QuesTitle/quesTitleList');
    }
}
?>
