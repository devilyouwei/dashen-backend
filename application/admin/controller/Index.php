<?php
namespace dashen\admin\controller;

use think\Controller;
use think\Request;
use think\Session;


class Index extends Controller
{
    public function _initialize()
    {
        //过滤字符串
        Request::instance()->filter(['strip_tags', 'trim']);

    }
    public function index()
    {
        return view('login');
    }

    public function login(){
        $captcha= input('post.captcha/s');
        if(!captcha_check($captcha)){
            $this->error('验证码错误','index');
        };
        $username = input('post.username/s'); // 学号
        $password = input('post.password/s'); // 密码
        if($username!=""&&$password!=""){
            $res = db("Admin")->where("adm_name",$username)->where("adm_password",md5($password))->find();
            if($res == null){
                $this->error("登陆失败，账号或者密码错误",'index');
            }else{
                session('admin', $res);     //建立session
                $this->success("登陆成功，马上为您跳转",'QuesTitle/index');
            }
        }else{

        }
    }

    public function pass(){
        return view('pass');
    }


    public function dopass(){
       
        if($_POST['password_c']!=$_POST['password']){
            $this->error("两次输入密码不一致",'pass');
        }
        db("Admin")->where('adm_name', 'admin')->update(['adm_password' => md5($_POST['password'])]);
        $this->success("修改密码成功",'User/userList');
    }

}
?>
