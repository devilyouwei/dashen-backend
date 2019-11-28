<?php
namespace dashen\admin\controller;

use think\Controller;
use think\Db;
use think\Request;
use think\Session;
//需求
class Withdraw extends Controller
{
    public function _initialize()
    {
        //过滤字符串
        Request::instance()->filter(['strip_tags', 'trim']);
    }

    //展示function
    public function withdrawlist()
    {
        //每页展示10条
        $d = db("view_withdrawapply")->paginate(10);
        $this->assign('list',$d);
        return $this->fetch("list");
    }

    public function pass($id){
        $d=db("withdraw")->where('id',$id)->update(['state' => 1]);
        return $this->success('操作成功','Withdraw/withdrawlist');
    }
    public function refuse($id){
        $d=db("withdraw")->where('id',$id)->update(['state' => -1]);
        return $this->success('操作成功','Withdraw/withdrawlist');
    }


    public function search(){
        $d=$_REQUEST;
        $name='%'.$d['name'].'%';
        $d = db("view_withdrawapply")
            ->where('state',$d['state'])
            ->where('real_name like :xx OR phone LIKE :yy ',['xx'=>$name, 'yy'=>$name])
            ->paginate(10, false, [
                'query' => ['name'=>$d['name'],'state'=>$d['state']]
            ]);
        $this->assign('list',$d);
        return $this->fetch("list");
    }


    public function delete($id){
        $msg=db('user')->delete($id);
        $this->success('删除成功','User/userList');
    }

}
?>
