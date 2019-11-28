<?php
namespace dashen\admin\controller;

use think\Controller;
use think\Db;
use think\Request;
use think\Session;
//需求
class User extends Controller
{
    public function _initialize()
    {
        //过滤字符串
        Request::instance()->filter(['strip_tags', 'trim']);
    }

    //展示function
    public function userList()
    {
        //每页展示10条
        $d = db("user")->paginate(10);
        $this->assign('list',$d);
        return $this->fetch("userlist");
    }

    public function update($id){
        $d=db("user")->where('id',$id)->find();
        //dump($d);
        $this->assign('d',$d);
        return $this->fetch('update');
    }

    public function doupdate(){
        $d=$_POST;
        $id=$d['id'];
        unset($d['id']);
        db('user')->where('id',$id)->update($d);
        return $this->success('修改成功','User/userList');

    }

    public function search(){
        $d=$_REQUEST;
        $name='%'.$d['name'].'%';
        $d = db("user")
            ->where('user_name like :xx OR real_name LIKE :yy OR phone LIKE :zz',['xx'=>$name, 'yy'=>$name,'zz'=>$name])
            ->paginate(10, false, [
                'query' => ['name'=>$d['name']],
            ]);
        $this->assign('list',$d);
        return $this->fetch("userlist");
    }

    public function delete($id){
        $msg=db('user')->delete($id);
        $this->success('删除成功','User/userList');
    }

}
?>
