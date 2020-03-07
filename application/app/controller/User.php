<?php

namespace dashen\app\controller;

use think\Controller;
use think\Request;
use think\Session;

/*
 * 关于json格式规范如下：
 *
 * @status 返回状态
 * 0:处理错误
 * 1:处理正确
 *
 * @login 用户服务器端的登陆状态
 * 1:登陆
 * 2:未登录
 *
 * @info 服务器返回到前段的消息
 *
 * @data 服务器返回的json数据集合
 *
 */

class User extends Controller
{
    public function _initialize()
    {
        define("F_PAGE",3);//第一页
        define("M_PAGE",2);//加载更多

        // 请求过滤不安全以及空格字符串
        Request::instance()->filter(['strip_tags', 'trim']);
    }

    //取得验证码
    public function getCode(){

        $phone = input('post.phone/s');//获取手机号
        $reset = input('post.reset/d');//是否重置
        //初等验证手机号
        if(!$phone || !preg_match("/^1[34578]\d{9}$/", $phone) || $phone==""){
            return ['status'=>0,'info'=>'手机号格式不正确'];
        }

        // 查询电话
        $res = db('user')->where('phone', $phone)->count();
        if($reset){//如果是重置密码
            if ($res == 0)
                return ['status' => 0, 'info' => '该号码尚未注册，请去注册'];
        }else{//如果是初次注册
            if ($res > 0)
                return ['status' => 0, 'info' => '该号码已经被注册，请去登陆'];
        }

        //频繁阻止
        $pCode = session("pCode");
        if($pCode){
            //取得相差秒数，必须大于60s才可以重新发送
            $last = time() - $pCode['time'];
            if($last<60)
                return['status'=>0,'info'=>'发送验证码过于频繁，你再等一分钟~，或许下一分钟，看到你的验证码~'];
        }

        //发送验证码
        $f = sendCode($phone);//成功返回验证码，失败返回false
        if(!$f){
            //发送失败
            return ['status'=>0,'info'=>'短信发送失败，请稍后重试！'];
        }
        //存入session等待用户注册时候验证
        $code['code']=$f;
        $code['phone']=$phone;
        $code['time']=time();
        session("pCode",$code);
        return ['status'=>1,'info'=>"验证码已发送，注意查收"];

    }

    //重置密码方法
    public function resetPwd(){
        $ajax['code'] = input('post.code/s'); // 验证码
        $ajax['user_pwd'] = input('post.user_pwd/s'); // 密码
        $ajax['encryption'] = input('post.encryption/s'); // 加密方式

        //密码，验证码不得为空
        if ($ajax['code'] == "" || $ajax['user_pwd'] == "")
            return ['status' => 0, 'info' => '不可以出现空字符串'];

        if (!isset($ajax['user_pwd']{5}))
            return ['status' => 0, 'info' => '密码需要大于6位'];

        //取得短信验证信息
        $pCode = session("pCode");
        if(!$pCode){//没有发送验证码
            return ['status' => 0, 'info' => '短信验证码失效，请重新获取短信验证码！'];
        }//验证码错误
        if($pCode['code']!=$ajax['code']){
            return ['status' => 0, 'info' => '短信验证码错误！'];
        }

        //查找用户id
        $id = db('user')->where('phone', $pCode['phone'])->value('id');
        if(!$id){
            return ['status' => 0, 'info' => '未找到重置用户，请先注册'];
        }
        $res = $this->savePwd($id, $ajax['user_pwd'], $ajax['encryption']);
        if($res){
            session("pCode",null);
            return ['status' => 1, 'info' => '已经重置成功，请去登陆吧！'];
        }

        return ['status' => 0, 'info' => '服务器错误：重置失败'];
    }

    // 用户提交注册
    public function register()
    {
        $ajax['code'] = input('post.code/s'); // 验证码
        $ajax['user_pwd'] = input('post.user_pwd/s'); // 密码
        $ajax['encryption'] = input('post.encryption/s'); // 加密方式
        $ajax['recommend'] = input('post.recommend/s'); // 加密方式

        //检查邀请码
        if($ajax['recommend']!=""){
            $res = db('user')->where('phone', $ajax['recommend'])->count();
            if($res==0)
                return ['status' => 0, 'info' => '无法获取此邀请码，请确认邀请码是否正确'];
        }
        //密码，验证码不得为空
        if ($ajax['code'] == "" || $ajax['user_pwd'] == "")
            return ['status' => 0, 'info' => '不可以出现空字符串'];

        if (!isset($ajax['user_pwd']{5})) {
            return ['status' => 0, 'info' => '密码需要大于6位'];
        }

        //取得短信验证信息
        $pCode = session("pCode");
        if(!$pCode){
            return ['status' => 0, 'info' => '短信验证码失效，请重新获取短信验证码！'];
        }
        if($pCode['code']!=$ajax['code']){
            return ['status' => 0, 'info' => '短信验证码错误！'];
        }

        // 再次查询重复电话
        $res = db('user')->where('phone', $pCode['phone'])->count();
        if ($res > 0)
            return ['status' => 0, 'info' => '该号码已经被注册，请去登陆'];

        // 开始注册
        $data['phone'] = $pCode['phone'];
        $data['create_time'] = time();
        $data['update_time'] = time();
        $data['create_ip'] = get_client_ip();
        $data['create_client'] = "app";
        $data['count_que'] = 0;
        $data['count_ans'] = 0;
        $data['count_focus'] = 0;
        $data['count_fans'] = 0;
        $data['count_fields'] = 0;
        $data['count_tags'] = 0;
        $data['count_school'] = 0;
        $data['count_certificate'] = 0;
        $data['is_dashen'] = 0;
        $data['is_find'] = 1;
        $data['is_service'] = 1;
        $data['is_authentic'] = 0;
        $data['is_effect'] = 1;
        $data['is_del'] = 0;

        $id = db('user')->insertGetId($data);
        $res = $this->savePwd($id, $ajax['user_pwd'], $ajax['encryption']);

        if ($res && $id){
            session("pCode",null);
            return ['status' => 1, 'info' => '注册成功'];
        }
        else
            return ['status' => 0, 'info' => '用户信息写入错误，稍后重试'];
    }

    public function login()
    {
        $ajax['phone'] = input('post.phone/s');
        $ajax['user_pwd'] = input('post.user_pwd/s');
        $ajax['os'] = input('post.os/s');
        $ajax['network'] = input('post.network/s');

        if ($ajax['phone'] == "" || $ajax['user_pwd'] == "")
            return ['status' => 0, 'info' => '手机号密码均不可为空'];

        $user_id = db('User')->where('phone', $ajax['phone'])->value('id');

        if (!$user_id)
            return ['status' => 0, 'info' => '用户不存在'];

        // 检查密码
        if ($this->checkPwd($user_id, $ajax['user_pwd'])) {
            $user = db('user')->find($user_id); // 主键寻找
            $user['token'] = md5(time() . $user['id']); // 建立token
            Session::set('user_info', $user); // 存入session

            $data['token'] = $user['token'];

            // 写入登陆日志
            $log['user_id'] = session("user_info")['id'];
            $log['time'] = time();
            $log['ip'] = get_client_ip();
            if ($ajax['os'] == "")
                $log['os'] = get_os();
            else
                $log['os'] = $ajax['os'];
            if ($ajax['network'] != "")
                $log['network'] = $ajax['network'];
            $log['browser'] = get_client_browser();
            $id = db("user_login_log")->insertGetId($log); // 插入数据库日志：ds_user_login_log
            if (!$id)
                return ['status' => 1, 'data' => $data, 'info' => '登录成功，日志写入失败'];

            return ['status' => 1, 'data' => $data, 'info' => '登录成功'];
        }
        return ['status' => 0, 'info' => '账号或密码错误'];
    }

    // 用户获取登录日志
    public function getLoginLog()
    {
        if (!session("?user_info"))
            return ['info' => '登录状态失效', 'login' => 0];

        $data = db("user_login_log")->where("user_id", session("user_info")['id'])->limit(10)->order("id desc")->select();
        if (empty($data))
            return ['status' => 0, 'info' => '没有任何登陆记录'];
        return ['status' => 1, 'info' => '获取登录记录成功', 'data' => $data];
    }

    // 删除用户的登陆日志
    public function delLoginLog()
    {
        if (!session("?user_info"))
            return ['info' => '登录状态失效', 'login' => 0];

        $ajax['id'] = input('post.id/d');

        $flag = db("user_login_log")->delete($ajax['id']);
        if ($flag == 0)
            return ['status' => 0, 'info' => '未删除任何记录'];
        return ['status' => 1, 'info' => '删除记录成功'];
    }

    // 检查用户登录状态
    public function checkLogin()
    {
        if (!session("?user_info"))
            return ['info' => '登录状态失效', 'login' => 0];

        //刷新session
        $this->updateSession();
        $data['id'] = session("user_info")['id'];
        $data['sid'] = session("user_info")['sid'];
        $data['user_name'] = session("user_info")['user_name'];
        $data['my_icon'] = session("user_info")['my_icon'];
        $data['my_icon_sm'] = session("user_info")['my_icon_sm'];
        $data['signature'] = session("user_info")['signature'];
        $data['actor'] = session("user_info")['actor'];
        $data['money'] = session("user_info")['money'];
        $data['is_service'] = session('user_info')['is_service'];
        $data['is_find'] = session('user_info')['is_find'];
        $data['is_authentic'] = session('user_info')['is_authentic'];
        $data['phone'] = session("user_info")['phone'];
        $data['token'] = session("user_info")['token'];
        return ['status' => 1, 'info' => '已登录', 'data' => $data];
    }

    // 退出登录
    public function logout()
    {
        Session::clear(); // 清空session即可
        return ['status' => 1, 'info' => '已退出'];
    }

    // 修改密码
    public function changePwd()
    {
        if (!session("?user_info"))
            return ['info' => '登录状态失效', 'login' => 0];

        $ajax['old_pwd'] = input('post.old_pwd/s'); // 密码
        $ajax['new_pwd'] = input('post.new_pwd/s'); // 密码
        $ajax['encryption'] = input('post.encryption/s'); // 加密方式

        if ($ajax['old_pwd'] == "" || $ajax['new_pwd'] == "" || $ajax["encryption"] == "")
            return ['status' => 0, 'info' => '空字符串错误'];
        if (!isset($ajax['new_pwd']{5}))
            return ['status' => 0, 'info' => '密码需要大于9位'];
        if (session("user_info") == null)
            return ['status' => 0, 'info' => '登录已经失效'];
        if (!$this->checkPwd(session("user_info")['id'], $ajax['old_pwd']))
            return ['status' => 0, 'info' => '原密码错误'];

        $res = $this->savePwd(session("user_info")['id'], $ajax['new_pwd'], $ajax['encryption']);
        if (!$res)
            return ['status' => 0, 'info' => '写入错误，密码修改失败'];

        Session::clear(); // 清空当前作用域下的session

        return ['status' => 1, 'info' => '密码修改成功，请重新登录'];
    }

    // 修改保存配置
    public function saveSecurity()
    {
        if (!session("?user_info"))
            return ['info' => '登录状态失效', 'login' => 0];

        $ajax['is_find'] = input('post.is_find/d'); // 可挖掘
        $ajax['is_service'] = input('post.is_service/d'); // 提供服务

        $flag = db("User")->where('id', session("user_info")['id'])->update(["is_service" => $ajax["is_service"], "is_find" => $ajax["is_find"]]);
        if ($flag == 0)
            return ['status' => 0, 'info' => '数据没有更新'];
        $this->updateSession(); // 刷新session
        return ['status' => 1, 'info' => '修改成功'];
    }

    // 获取个人信息
    public function getPersonalInfo()
    {
        if (!session("?user_info"))
            return ['info' => '登录状态失效', 'login' => 0];

        $data = db("User")->where('id', session("user_info")['id'])->field('user_name,birth,sex,love_stat,actor,education,university,faculty,real_name,email,phone,qq,weixin,province,city,county')->find();
        if (count($data) == 0)
            return ['status' => 0, 'info' => '查询不到个人信息'];
        return ['status' => 1, 'data' => $data];
    }

    // 保存更新个人信息
    public function savePersonalInfo()
    {
        if (!session("?user_info"))
            return ['info' => '登录状态失效', 'login' => 0];

        $ajax['user_name'] = input('post.user_name/s'); // 性别
        $ajax['sex'] = input('post.sex/s'); // 性别
        $ajax['birth'] = input('post.birth/s'); // 生日
        $ajax['love_stat'] = input('post.love_stat/s'); // 感情状况
        $ajax['education'] = input('post.education/s'); // 学历
        $ajax['university'] = input('post.university/s'); // 大学
        $ajax['faculty'] = input('post.faculty/s'); // 院系
        $ajax['province'] = input('post.province/s'); // 省份
        $ajax['city'] = input('post.city/s'); // 市
        $ajax['county'] = input('post.county/s'); // 县区
        $ajax['actor'] = input('post.actor/s'); // 身份
        $ajax['real_name'] = input('post.real_name/s'); // 真实名字
        $ajax['email'] = input('post.email/s'); // email
        //$ajax['phone'] = input('post.phone/s'); // 电话不得修改
        $ajax['qq'] = input('post.qq/s'); // qq
        $ajax['weixin'] = input('post.weixin/s'); // weixin
        $ajax['update_time'] = time();

        if (!$ajax['user_name'] || $ajax['user_name'] =="")
            return ['status' => 0, 'info' => '用户昵称不得空着'];
        if (!$ajax['real_name'] || $ajax['real_name'] =="")
            return ['status' => 0, 'info' => '请填写真实用户姓名'];
        if (!$ajax['sex'] || $ajax['sex'] =="")
            return ['status' => 0, 'info' => '请填写性别'];
        if ($ajax['email'] != "" && !check_email($ajax['email']))
            return ['status' => 0, 'info' => '邮箱格式不正确'];

        $ajax['is_authentic'] = 1;//置为已完善资料
        $flag = db("User")->where('id', session("user_info")['id'])->update($ajax);
        if ($flag == 0)
            return ['status' => 0, 'info' => '数据没有更新'];
        $this->updateSession();
        return ['status' => 1, 'info' => '修改成功'];
    }

    // 保存用户修改图像
    // 基于base64的json传输
    public function saveHeadImg()
    {
        if (!session("?user_info"))
            return ['info' => '登录状态失效', 'login' => 0];

        $ajax['my_icon'] = input('my_icon/s'); // 大图
        $ajax['my_icon_sm'] = input('my_icon_sm/s'); // 小图
        $flag = db("user")->where('id', session("user_info")['id'])->update(['my_icon' => $ajax['my_icon'], 'my_icon_sm' => $ajax['my_icon_sm']]);
        if ($flag == 0)
            return ['info' => '错误，头像保存失败', 'status' => 0];
        $this->updateSession();
        return ['info' => '头像已保存好了', 'status' => 1];
    }

    // 修改签名
    public function updateSignature()
    {
        if (!session("?user_info"))
            return ['info' => '登录状态失效', 'login' => 0];

        $ajax['signature'] = input('signature/s');
        $flag = db("user")->where('id', session("user_info")['id'])->setField('signature', $ajax['signature']);
        if ($flag == 0)
            return ['info' => '错误，签名未保存', 'status' => 0];

        $this->updateSession();
        return ['info' => '签名已修改', 'status' => 1];
    }

    // 请求大学省份
    public function getUnivsProvince()
    {
        if (!session("?user_info"))
            return ['info' => '登录状态失效', 'login' => 0];

        $data = db("UnisProvince")->select();
        if (empty($data))
            return ['status' => 0, 'info' => '该省份查询不到'];

        return ['status' => 1, 'data' => $data];
    }

    // 请求省份下大学
    public function getUnivs()
    {
        if (!session("?user_info"))
            return ['info' => '登录状态失效', 'login' => 0];

        $ajax['pid'] = input('post.pid/d');
        if ($ajax['pid'] == 0 || $ajax['pid'] == null)
            return ['info' => '瞎几把选，根本没有0', 'status' => 0];

        $data = db("Unis")->where($ajax)->select();
        if (empty($data))
            return ['status' => 0, 'info' => '大学查询不到'];

        return ['status' => 1, 'data' => $data];
    }

    // 请求学院、系、专业
    public function getUnivsFaculties()
    {
        if (!session("?user_info"))
            return ['info' => '登录状态失效', 'login' => 0];

        $ajax['uid'] = input('post.uid/d');
        if ($ajax['uid'] == 0 || $ajax['uid'] == null)
            return ['info' => '不许乱选！', 'status' => 0];

        $data = db("UnisFaculties")->where($ajax)->select();
        if (empty($data))
            return ['status' => 0, 'info' => '该大学院系查不到'];

        return ['status' => 1, 'data' => $data];
    }

    public function getUserQues()
    {
        if (!session("?user_info"))
            return ['info' => '登录状态失效', 'login' => 0];

        $field = "id,title,content,thumbnail,create_time,ip,state";
        // 取得前端最小的id，即最早的一条消息，寻找更早的消息,以此来实现瀑布流
        $min_id = input('post.min_id/d');
        if ($min_id == 0)
            $data = db("view_ques")->where('uid', session("user_info")['id'])->field($field)->order('id desc')->limit(F_PAGE)->select();
        else
            $data = db("view_ques")->where('id', '<', $min_id)->where('uid', session("user_info")['id'])->field($field)->order('id desc')->limit(M_PAGE)->select();

        if (!empty($data)) {
            for ($i = 0; $i < count($data); $i++) {
                $data[$i]['content'] = mb_substr($data[$i]['content'], 0, 50, 'utf-8') . " ......";
            }
        }

        return ['status' => 1, 'data' => $data];
    }

    //查看用户的需求详情
    public function showQues() {
        if (!session("?user_info"))
            return ['info'=>'登录状态失效', 'login'=>0];

        $id = input("post.id/d");

        $where['is_del']=0;
        $where['is_effect']=1;
        $where['id']=$id;
        $where['uid'] = session("user_info")['id'];//只能查看自己id的需求单

        if ($id <= 0 || $id == null)
            return ['status'=>0, 'info'=>'无效的需求'];


        $data = db("view_ques")->where($where)->find();

        if ($data == null)
            return ['status'=>0, 'info'=>'您的查看内容已被删除或者没有权限'];

        return ['status'=>1, 'info'=>'查询成功', 'data'=>$data];
    }


    /*
     * savePwd 专门用于保存密码的功能
     * @pwd密码
     * @encrypt加密方式
     * @id用户主键
     */
    protected function savePwd($id, $pwd, $encrypt)
    {
        $salt = get_rand_salt(); // 获取随机盐值
        $user_pwd = encrypt_pwd($pwd, $salt, $encrypt); // 实行密码加密
        // 需要更新加密后的密码，盐值，还有加密方式
        $res = db("user")->where('id', $id)->update(['user_pwd' => $user_pwd, 'salt' => $salt, 'encryption' => $encrypt]);
        if ($res == 0)
            return false;
        else
            return true;
    }

    /*
     * savePwd检查密码正确性
     * @id 用户主键
     * @pwd 输入密码
     */
    protected function checkPwd($id, $pwd)
    {
        $data = db("User")->where('id', $id)->field('user_pwd,salt,encryption')->find();
        if ($data['user_pwd'] == encrypt_pwd($pwd, $data['salt'], $data['encryption']))
            return true;
        else
            return false;
    }

    /*
     * 刷新session，session内容应该与数据库数据同步，每次对数据库User表操作相应的session必须刷新
     */
    protected function updateSession()
    {
        if (!session("?user_info"))
            return false; // 本身没有session，刷新失败

        $data = db("User")->where('id', session("user_info")['id'])->find();
        $data['token'] = session("user_info")['token']; // token不能刷新的
        Session::set("user_info", $data);
        return true;
    }
}
