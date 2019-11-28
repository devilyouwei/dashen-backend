<?php

namespace dashen\app\controller;

use think\Controller;
use think\Request;
use think\Session;

// Service类，对应前端的service模块
class Service extends Controller
{
    // 初始化控制器
    public function _initialize()
    {
        // 请求过滤不安全以及空格字符串
        Request::instance()->filter(['strip_tags', 'trim']);
    }

    // 获取map上的指定用户群
    /*
     * 主要用于前端显示的地图上的各位大神用户，显示：user_name,signature,my_icon_sm,actor,longitude,latitude,id
     */
    public function map()
    {
        // 验证登陆session
        if (!session("?user_info"))
            return ['info' => '登录状态失效', 'login' => 0];

        $data = db("User")->where('is_service', 1)->field('id,my_icon_sm,actor,longitude,latitude,count_que')->select();
        if (empty($data))
            return ['status' => 0, 'info' => '附近没有任何可以找到的人'];
        return ['status' => 1, 'data' => $data, 'info' => '查询成功'];
    }

    // 更新用户地理定位
    public function updateUserPos() {
        // 验证登陆session
        if (!session("?user_info"))
            return ['info' => '登录状态失效', 'login' => 0];

        $ajax['longitude'] = input('post.longitude/s');
        $ajax['latitude'] = input('post.latitude/s');

        if ($ajax['longitude'] != "" && $ajax['latitude'] != "") {
            $flag = db("User")->where('id', session("user_info")['id'])->update(["longitude" => $ajax['longitude'], "latitude" => $ajax['latitude']]);
            if (!$flag)
                return ['status' => 0, 'info' => '坐标未更新'];
        }

        return ['status' => 1, 'info' => '坐标已更新'];
    }

    //查找对应用户的位置，经纬度
    public function findPosById(){
        // 验证登陆session
        if (!session("?user_info"))
            return ['info' => '登录状态失效', 'login' => 0];

        $ajax['id'] = input('post.id/d');
        if(!$ajax['id'] || $ajax['id']==0 ||$ajax['id']==null)
            return ['status' => 0, 'info' => '查找id不得为空'];

        $p = db("user")->where("id",$ajax['id'])->field("longitude,latitude")->find();
        if($p==null)
            return ['status' => 0, 'info' => '找不到对方位置信息'];

        return ['status'=>1,'info'=>'查找成功','data'=>$p];
    }

    public function getIntro(){
        // 验证登陆session
        if (!session("?user_info"))
            return ['info' => '登录状态失效', 'login' => 0];

        $id = input("post.id/d");
        if ($id <= 0 || $id == null)
            return ['status'=>0, 'info'=>'无效的用户'];

        // 只能查看自己的问题详情
        $data = db("User")->where("is_service",1)->field("id,user_name,education,my_icon,actor,signature,university,sex,province,city,county,love_stat")->find($id);

        if ($data == null)
            return ['status'=>0, 'info'=>'您的查看内容已被删除或者没有权限'];

        return ['status'=>1, 'info'=>'查询成功', 'data'=>$data];
    }
}

?>
