<?php

namespace dashen\app\controller;

use think\Session;
use think\Controller;
use think\Request;

class Index extends Controller {
    // 初始化控制器
    public function _initialize() {
        define("F_PAGE",5);//第一页
        define("M_PAGE",2);//加载更多

        // 请求过滤不安全以及空格字符串
        Request::instance()->filter(['strip_tags', 'trim']);
    }

    public function index() {
        $user_info = Session::get("user_info");
        if ($user_info == null) {
            return ['status'=>0, 'info'=>'登录状态失效'];
        }
        return ['status'=>1, 'info'=>'已登录', 'data'=>$user_info['token']];
    }

    //获得主页的需求单，后期可能做数据分析和经纬度计算，所以独立开来
    public function getQues(){
        if(!session("user_info"))
            return ["login"=>0, 'info'=>'登录状态失效！'];

        $min_id = input('post.min_id/d');
		$title_id = input('post.tab_id/d');
		$search = input('post.search/s');

        $where['is_del']=0;
        $where['is_effect']=1;
        $where['state']=0;//主页只显示未接订单
		
		//带有分类
		if($title_id && $title_id != 0)
			$where['title_id']=$title_id;
		//带有搜索
		if($search!="")
			$where['content']=['like',"%$search%"];
		
        $field="id,title,content,thumbnail,reward,price,create_time,my_icon_sm,user_name,longitude,latitude,loc";
        if($min_id==0)
            $data = db("view_ques")->where($where)->field($field)->order('id desc')->limit(F_PAGE)->select();
        else
            $data = db("view_ques")->where($where)->where('id', '<', $min_id)->field($field)->order('id desc')->limit(M_PAGE)->select();

        if (!empty($data)) {
            for ($i = 0; $i < count($data); $i++) {
                $data[$i]['content'] = mb_substr($data[$i]['content'], 0, 30, 'utf-8') . " ......";
            }
        }
        return ['status'=>1,'data'=>$data];
    }

	//获得最新需求单数
	public function countNewQues(){
        if(!session("user_info"))
            return ["login"=>0, 'info'=>'登录状态失效！'];

        $ajax['max_id'] = input('post.max_id/d');
        //$ajax['tab_id'] = input('post.tab_id/d');
        //$ajax['search'] = input('post.search/s');

		$where['is_del'] = 0;
		$where['is_effect'] = 1;
		$where['state'] = 0;
		//if($ajax['tab_id']!=0)
		//	$where['title_id'] = $ajax['tab_id'];
		//if($ajax['search']!="")
		//	$where['content']=['like',"%'".$ajax['search']."'%"];
		$where['id'] = ['>',$ajax['max_id']];
		$where['uid'] = ['<>',session("user_info")['id']];
		$count = db("ques")->where($where)->count();
		return ["status"=>1,'info'=>'','data'=>$count];
	}
}
