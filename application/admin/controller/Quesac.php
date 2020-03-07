<?php
namespace dashen\admin\controller;

use think\Controller;
use think\Db;
use think\Request;
use think\Session;
//需求
class Quesac extends Controller
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
    public function quesList()
    {
        //每页展示10条
        $d = db("view_ques_picker")->where('state','>',0)->paginate(10);
        $this->assign('list',$d);
        $this->assign('state',1);
        return $this->fetch("list");
    }

    public function update($id){
        $d=db("view_ques")->where('id',$id)->find();
        //dump($d);
        $this->assign('d',$d);
        return $this->fetch('update');
    }

    public function doupdate(){
        $d=$_POST;
        $id=$d['id'];
        unset($d['id']);
        db('ques')->where('id',$id)->update($d);
        return $this->success('修改成功','Quesac/quesList');
    }

    public function finish($id){
        db('ques')->where('id',$id)->update(['state' => 2]);
        db('ques_order')->where('qid',$id)->update(['state' => 1]);
        return $this->success('强制结束成功','Quesac/quesList');
    }

    public function delete($id){
        $msg=db('ques')->delete($id);
        $this->success('删除成功','Queses/quesList');
    }
    public function search(){
        $d=$_REQUEST;
        $name='%'.$d['keyword'].'%';
        $state=$d['state'];
        $d = db("view_ques_picker")->where('state',$state)->where('content LIKE :yy OR poster_user_name LIKE :zz OR user_name LIKE :ww OR id like :xx',['xx'=>$name,'yy'=>$name,'zz'=>$name,'ww'=>$name])
            ->paginate(10, false, [
                'query' => ['keyword'=>$d['keyword'],'state'=>$d['state']]
            ]);
        $this->assign('list',$d);
        $this->assign('state',$state);
        return $this->fetch("list");
    }
}
?>
