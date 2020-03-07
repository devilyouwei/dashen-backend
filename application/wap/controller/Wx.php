<?php
/*
 * 作者：黄有为
 * 联系：devilyouwei@gmail.com
 * 日期：2017-8-19
 * 描述：微信登陆发单专用控制器，属于wap页面，但前端有微信js接口
 */
namespace dashen\wap\controller;

use think\Request;

class Wx
{
    public function _initialize(){
        Request::instance()->filter(['strip_tags', 'trim']);
    }
    public function index()
    {
        if(session("?user_info")){
            return redirect('ques');
        }
        return view("index");
    }

    //会员中心
    public function user(){
        if(!session("?user_info"))
            return redirect('login');

        $user = db("user")->where("id",session("user_info")['id'])->find();
    		return view("user",['user'=>$user]);
    }

    //用户个人信息
    public function personal(){
        if(!session("?user_info"))
            return redirect('login');
    	
        $user = db("user")->where("id",session("user_info")['id'])->find();
    		return view("personal",['user'=>$user]);
    }

    //验证码验证
    public function codePhone(){
        $ajax['code'] = input("post.code/s");

        //未输入
        if(!$ajax['code'] || $ajax['code']==""){
            return json(["status"=>0,"info"=>"请输入验证码！"]);
        }
        //未发送验证码
        if(!session("pCode")){
            return json(["status"=>0,"info"=>"验证码未发送，请点击获取验证码！"]);
        }
        //验证码错误
        if(session('pCode')['code'].""!=$ajax['code']){
            return json(["status"=>0,"info"=>"验证码错误！"]);
        }

        return json(["status"=>1,"ajax"=>$ajax,"info"=>"验证通过"]);
    }

    public function intro(){
        //没有验证手机号码
        if(!session("?pCode")){
            return redirect('index');
        }
        return view("intro");
    }

    //注册新用户
    public function reg(){
        //检查是否已经注册过手机号
        if(session("?pCode")){
            $ajax['phone'] = session("pCode")['phone']; // 加密方式，微信端一律使用md5
        }else{
            return json(['status'=>0,'info'=>"未检查到手机号，请先返回第一步验证手机号"]);
        }

        //基本注册信息，微信端与app有所区别，但共用一张表
        $ajax['user_name'] = input("post.user_name/s");
        $ajax['sex'] = input("post.sex/s");
        //$ajax['actor'] = input("post.actor/s");
        //$ajax['real_name'] = input("post.real_name/s");
        //$ajax['email'] = input("post.email/s");
        //$ajax['qq'] = input("post.qq/s");
        $ajax['user_pwd'] = input("post.password/s");
        $ajax['encryption'] = "md5"; // 加密方式，微信端一律使用md5


        //不允许空的字符串
        if($ajax['user_name']=="" || $ajax['user_pwd']=="" || $ajax['phone']==""){
            return json(['status'=>0,'info'=>'空字符错误']);
        }

        //写入数据库
        $ajax['create_time'] = time();
        $ajax['update_time'] = time();
        $ajax['create_ip'] = get_client_ip();
        $ajax['create_client'] = "weixin";
        $ajax['count_que'] = 0;
        $ajax['count_ans'] = 0;
        $ajax['count_focus'] = 0;
        $ajax['count_fans'] = 0;
        $ajax['count_fields'] = 0;
        $ajax['count_tags'] = 0;
        $ajax['count_school'] = 0;
        $ajax['count_certificate'] = 0;
        $ajax['is_dashen'] = 0;
        $ajax['is_find'] = 1;
        $ajax['is_service'] = 1;
        $ajax['is_authentic'] = 0;
        $ajax['is_effect'] = 1;
        $ajax['is_del'] = 0;

        //写入前再验证一次手机号存在性
        $count = db('user')->where('phone', $ajax['phone'])->count();
        if($count!=0){
            return json(["status"=>0,"info"=>"该手机号已经被注册，您可以选择直接登陆或者注册其他手机号"]);
        }

        //写入用户
        $id = db('user')->insertGetId($ajax);
        //设置密码
        $res = savePwd($id, $ajax['user_pwd'], $ajax['encryption']);

        if ($res && $id){
            //清除手机号session
            session("pCode",null);
            //建立登陆session
            $user = db("user")->find($id);
            session("user_info",$user);//建立用户session，表示用户处于登陆状态
            return ['status' => 1, 'info' => '注册成功，可以发单了'];
        }
        else
            return ['status' => 0, 'info' => '用户信息写入错误，稍后重试'];

    }

    //微信登陆
    public function login(){
        return view("login");
    }

    public function listQues(){
        if(!session("?user_info")){
            return redirect("login");
        }
        $data = db("view_ques")->where("uid",session("user_info")['id'])->order("create_time","desc")->select();
        if (!empty($data)) {
            for ($i = 0; $i < count($data); $i++) {
                $data[$i]['content'] = mb_substr($data[$i]['content'], 0, 30, 'utf-8') . " ......";
                $data[$i]['create_time'] = date("Y-m-d H:i",$data[$i]['create_time']);
                $data[$i]['format_id'] = str_pad($data[$i]['id'],11,"0",STR_PAD_LEFT);
            }
        }
        return view("list",["data"=>$data]);
    }

	public function detail($id){
		$data = db("view_ques")->where("id",$id)->find();

        $data['create_time'] = date("Y-m-d H:i",$data['create_time']);
        $data['format_id'] = str_pad($data['id'],11,"0",STR_PAD_LEFT);
		$picker = null;
		if($data['state']>0)
			$picker = db("view_order_picker")->where("qid",$id)->find();

        return view("detail",["data"=>$data,"pick"=>$picker]);
	}

    //提问页面
    public function ques(){
        if(!session("?user_info")){
            return redirect("login");
        }
        $data = db("ques_title")->select();
        $money = db("user")->where("id",session("user_info")['id'])->field("money")->find();
        $sign = wxJsApi();
        return view("ques",["data"=>$data,"sign"=>$sign,"money"=>$money['money']]);
    }

    public function addQues() {
        // 验证登陆session
        if (!session("?user_info"))
            return ['info'=>'登录状态失效', 'login'=>0];

        $ajax['title_id'] = input('post.title_id/d');//标题，随后台变化
        $ajax['content'] = input('post.content/s');//详细内容
        $ajax['star'] = input('post.star/d');//难度
        $ajax['reward'] = input('post.reward/s');//奖励方式
        $ajax['price'] = input('post.price/f');//奖励方式


        //检查余额够不够
        //统一格式化为2位避免比较等计算错误
        $ajax['price'] = format_money($ajax['price']);
        $money = format_money(db("user")->where("id",session("user_info")['id'])->column('money')[0]);

        if($money<$ajax['price'])
            return ['status'=>0,'info'=>"余额不足"];

        $ajax['message'] = input('post.message/s');//用户留言
        //防止ios上传报null错误，ios中前端将""变量置为null
        if($ajax['message']==null)
            $ajax['message'] ="";

        //用户发布定位信息
        $ajax['longitude'] = input('post.longitude/s');
        //客户端未提交正确的经纬度，null值传入数据库报错，转换为""
        if($ajax['longitude']==null){
            $ajax['longitude'] = "";
        }
        $ajax['latitude'] = input('post.latitude/s');
        if($ajax['latitude']==null){
            $ajax['latitude'] = "";
        }
        $ajax['loc'] = input('post.loc/s');
        if($ajax['loc']==null)
            $ajax['loc'] ="";
        $ajax['addr'] = input('post.addr/s');
        if($ajax['addr']==null)
            $ajax['addr'] ="";
        $ajax['poi'] = input('post.poi/s');
        if($ajax['poi']==null)
            $ajax['poi'] ="";

        if($ajax['reward']!="金钱悬赏")
            $ajax['price'] = 0;

        if ($ajax['title_id'] == null || $ajax['content'] == null || $ajax['reward'] == null)
            return ['info'=>'标题，内容，悬赏方式不能为空', 'status'=>0];

        if ($ajax['title_id'] == 0 || $ajax['content'] == "" || $ajax['reward'] == "")
            return ['info'=>'标题，内容，悬赏方式不能为空', 'status'=>0];

		//客户端上传的文件，从微信端获取
        $media_id['img0'] = input('post.img0/s');
        $media_id['img1'] = input('post.img1/s');
        $media_id['img2'] = input('post.img2/s');
        $media_id['img3'] = input('post.img3/s');
        $media_id['img4'] = input('post.img4/s');
        $media_id['img5'] = input('post.img5/s');
        $media_id['img6'] = input('post.img6/s');
        $media_id['img7'] = input('post.img7/s');
        $media_id['voice'] = input('post.voice/s');
        for($i = 0;$i<=7;$i++){
        		$mid = $media_id['img'.$i];
        		if($mid && $mid!=null && $mid!=""){
        			//文件转储
				$info= wxGetFile($mid,"img");
				if($info===false)
        				return ['status'=>0,'info'=>'文件转储失败！'];
        			else
        				$ajax['img'.$i] = $info;
        		}
        }
        if($media_id['voice'] && $media_id['voice']!="" && $media_id['voice']!=null){
			$info= wxGetFile($media_id['voice'],"voice");
			if($info===false)
        			return ['status'=>0,'info'=>'文件转储失败！'];
        		else
        			$ajax['voice'] = $info;
        }

        $ajax['uid'] = session("user_info")['id'];
        $ajax['create_time'] = time();
        $ajax['update_time'] = $ajax['create_time'];
        $ajax['ip'] = get_client_ip();
        $ajax['is_del'] = 0;
        $ajax['is_effect'] = 1;
        $ajax['state'] = 0;
        //正式插入需求表
        $f = db("Ques")->insert($ajax);
        if ($f >= 1){
            //修改用户表一些参数
            db("user")->where('id',session("user_info")['id'])->setInc("count_que");//用户未接单需求+1
            db("user")->where('id',session("user_info")['id'])->setDec("money",$ajax['price']);//用户未余额减去金额，扣钱
            $field['longitude'] = $ajax['longitude'];
            $field['latitude'] = $ajax['latitude'];
            db("user")->where('id',session("user_info")['id'])->update($field);//用户未余额减去金额，扣钱

            return ['status'=>1, 'info'=>'需求单提交成功'];
        }
        else return ['status'=>0, 'info'=>'数据插入失败'];
    }

    //退出
    public function logout(){
        session(null);
        return redirect("login");
    }

    //分享
    public function share($qid,$sid){
        $data = db("view_ques")->where("id",$qid)->find();
        return view("share",["ques"=>$data,"sid"=>$sid]);
    }

    //添加订单
    public function addOrder(){
        $ajax['sid'] = input("post.sid/s");//分享人id
        $ajax['qid'] = input("post.qid/s");//需求单id
        $ajax['code'] = input("post.code/s");//短信验证

        //未输入
        if(!$ajax['code'] || $ajax['code']==""){
            return json(["status"=>0,"info"=>"请输入验证码！"]);
        }
        //未发送验证码
        if(!session("pCode")){
            return json(["status"=>0,"info"=>"验证码未发送，请点击获取验证码！"]);
        }
        //验证码错误
        if(session('pCode')['code'].""!=$ajax['code']){
            return json(["status"=>0,"info"=>"验证码错误！"]);
        }

        //开始接单，代码同app下addOrder相似
        $user_info = db("user")->where("phone",session("pCode")['phone'])->field("is_authentic,id")->find();

        if($user_info == null){
            return ["status"=>0,"info"=>"接单失败：查找不到该号码用户，您尚未注册"];
        }
        //未完善资料
        if($user_info['is_authentic']==0){
            return ["status"=>0,"info"=>"接单失败：尚未完善资料，请先完善后接单！"];
        }

        //查询出需求单
        $where['id'] = $ajax['qid'];
        $where["is_del"]= 0;
        $where["is_effect"]= 1;
        $q = db("ques")->where($where)->find();

        if($q==null)
            return ['info'=>'该需求您无权接单或需求单刚被删除','status'=>0];

        //需求发单人是本人
        if($q['uid']==$user_info['id'])
            return ['info'=>'不可自己接自己的需求单','status'=>0];

        if($q['state']!=0)
            return ['info'=>'很抱歉，晚了一步，该需求已经被接单了！请刷新后查看订单状态','status'=>0];


        //生成订单
        $data['qid']=$q['id'];//需求单编号
        $data['uid']=$user_info['id'];//接单用户id
        $data['quid']=$q['uid'];//发单用户id
        $data['state']=0;
        $data['sid']=$ajax['sid'];
        $data['is_del']=0;
        $data['is_effect']=1;
        $data['create_time']=time();
        $data['ip']=get_client_ip();
        $id = db("ques_order")->insertGetId($data);
        if(!$id)
            return ['status' => 0, 'data' => $data, 'info' => '接单写入失败'];


        db("ques")->where("id",$q['id'])->setField("state",1);//需求单状态置为1，已被接单
        db("user")->where("id",$q['uid'])->setDec("count_que");//被接单，客户未接单需求-1
        return ['status' => 1, 'info' => '接单成功！请打开App查看并完成订单！'];
    }

	//根据经纬度返回地址，接口为juhe数据
    public function getAddr($lng,$lat){
    		$key = GD_KEY;//高德地图全局开发者key
		$url = "http://restapi.amap.com/v3/geocode/regeo?key=".$key."&location=".$lng.",".$lat;
    		return https_get($url);
    }

}
