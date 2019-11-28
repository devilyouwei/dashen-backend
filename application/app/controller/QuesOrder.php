<?php
namespace dashen\app\controller;

use think\Controller;
use think\Request;

class QuesOrder extends Controller{
    public function _initialize() {
        define("F_PAGE",5);//第一页
        define("M_PAGE",2);//加载更多
        // 请求过滤不安全以及空格字符串
        Request::instance()->filter(['strip_tags', 'trim']);
    }

    //添加，下订单
    public function addOrder(){
        // 验证登陆session
        if (!session("?user_info"))
            return ['info'=>'登录状态失效', 'login'=>0];


        //检查接单许可
        if(session("user_info")['is_authentic']==0){
            return ["status"=>0,"info"=>"接单失败：尚未完善资料，请先完善后接单！"];
        }

        //取得需求单编号
        $where['id'] = input('post.id/d');
        $where["is_del"]= 0;
        $where["is_effect"]= 1;
        $q = db("ques")->where($where)->find();

        if($q==null)
            return ['info'=>'该需求您无权接单或需求单刚被删除','status'=>0];
        //自己接自己的单
        if($q['uid']==session("user_info")['id'])
            return ['info'=>'自己不可以接自己的单','status'=>0];
        else{
            if($q['state']!=0)
                return ['info'=>'很抱歉，该需求已经被接单了！','status'=>0];

            //生成订单
            $data['qid']=$q['id'];//需求单编号
            $data['uid']=session("user_info")['id'];//接单用户id
            $data['sid']=session("user_info")['id'];//分享用户id（本人）
            $data['quid']=$q['uid'];//发单用户id
            $data['state']=0;
            $data['is_del']=0;
            $data['is_effect']=1;
            $data['create_time']=time();
            $data['ip']=get_client_ip();
            $id = db("ques_order")->insertGetId($data);
            if(!$id)
                return ['status' => 0, 'data' => $data, 'info' => '接单写入失败'];


            db("ques")->where("id",$q['id'])->setField("state",1);//需求单状态置为1，已被接单
            db("user")->where("id",$q['uid'])->setDec("count_que");//被接单，客户未接单需求-1
            return ['status' => 1, 'info' => '接单成功！'];
        }

        return ['status'=>1,'info'=>'接单成功！'];
    }

    //接单方，查看订单列表
    public function pickerGetOrders(){
        // 验证登陆session
        if (!session("?user_info"))
            return ['info'=>'登录状态失效', 'login'=>0];

        //订单状态,1表示完成的，0表示进行中的
        $state = input('post.state/d');
        //最小的id
        $min_id = input('post.min_id/d');
        $field = 'id,title,user_name,create_time,price,content,state,my_icon_sm';
        $where['is_del'] = 0;
        $where['is_effect'] = 1;
        $where['state'] = $state;
        $where['uid'] = session("user_info")['id'];


        if ($min_id == 0)   //第一次加载时从头加载
            $data = db("view_order_picker")->where($where)->field($field)->order('id desc')->limit(F_PAGE)->select();
        else    //加载更多
            $data = db("view_order_picker")->where($where)->where('id', '<', $min_id)->field($field)->order('id desc')->limit(M_PAGE)->select();


        if (!empty($data)) {
            for ($i = 0; $i < count($data); $i++) {
                $data[$i]['content'] = mb_substr($data[$i]['content'], 0, 30, 'utf-8') . " ......";
            }
        }
        return ['status'=>1, 'data'=>$data];
    }

    //接单方，查看订单详情
    public function pickerShowOrder(){
        // 验证登陆session
        if (!session("?user_info"))
            return ['info'=>'登录状态失效', 'login'=>0];

        $id = input('post.id/d');

        $where['id'] = $id;
        $where['uid'] = session("user_info")['id'];
        $where['is_effect'] = 1;
        $where['is_del'] = 0;
        $data = db("view_order_picker")->where($where)->find();
        if($data==null){
            return ['status'=>0,'info'=>'无权查看该订单！'];
        }

        return ['status'=>1,'data'=>$data];
    }

    //发单方，查看订单详情
    public function posterShowOrder(){
        // 验证登陆session
        if (!session("?user_info"))
            return ['info'=>'登录状态失效', 'login'=>0];

        //此处前端获得是需求单的id，并非订单id
        //下面根据需求id和需求发布者id锁定一条订单
        $qid = input('post.id/d');
        $where['is_del'] = 0;
        $where['is_effect'] = 1;
        $where['quid'] = session("user_info")['id'];//发单方用户id
        $where['qid'] = $qid;//需求单id
        $data = db("view_order_poster")->where($where)->find();
        if($data == null){
            return ['status'=>0,"info"=>"您无权查看该接单详情以及接单人信息"];
        }

        return ['status'=>1,'data'=>$data];
    }

    //确认订单完成
    public function confirmOrder(){
        // 验证登陆session
        if (!session("?user_info"))
            return ['info'=>'登录状态失效', 'login'=>0];

        $qid = input('post.id/d');
        $where['qid']=$qid;//订单id
        $where['quid']=session("user_info")['id'];//发需求人id
        $where['is_effect']= 1;
        $where['is_del']= 0;

        //修改订单状态ques_order表
        $flag = db("ques_order")->where($where)->setField("state",1);//确认订单完成，订单状态置为1
        if(!$flag){
            return["info"=>"确认失败，您没有权限或者接单已被确认！请刷新",'status'=>1];
        }

        //修改需求状态ques表
        $flag = db("ques")->where("id",$qid)->where("uid",session("user_info")['id'])->setField("state",2);//订单完成后，需求置为2，表示需求已解决
        if(!$flag){
            return["info"=>"确认失败，您没有权限或者接单已被确认！请刷新",'status'=>1];
        }

        /*打钱给接单者
         * 需要变量：分享人id，接单者id，悬赏金额，对应百分比
         */


        $money = db("view_order_money")->where("qid",$qid)->find();//确认订单完成，订单状态置为1
        if($money==null)
            return ['status'=>0,"info"=>"分钱失败，找不到订单！"];

        /*
         * 注1：自己分享自己接单的分享人和接单人同一个（sid=uid）
         * 注2：没有中间分享人的也表示为分享人和接单人同一个（sid=uid）
         */

        //分享人钱 
        $smoney = format_money($money['price']*$money['sharecent']/100);
        //接单人钱
        $wmoney = format_money($money['price']*$money['workcent']/100);

        db("user")->where("id",$money['sid'])->setInc("money",$smoney);
        db("user")->where("id",$money['uid'])->setInc("money",$wmoney);

        //全都正常通过
        return ['status'=>1,"info"=>"订单已结束，相应悬赏已发送！"];
    }
}
