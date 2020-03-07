<?php

namespace dashen\app\controller;

use think\Controller;
use think\Request;
use think\Session;
use think\Image;
use think\File;
use OSS\OssClient;

// Ques类，对应各种疑难请求需要的业务逻辑，对应了疑难请求的功能
class Ques extends Controller {
    // 初始化控制器
    public function _initialize() {
        define("F_PAGE",6);//第一页
        define("M_PAGE",5);//加载更多
        define("UPLOAD_SIZE",10*1024*1024);//最大上传限制10M
        define("UPLOAD_EXT",'jpg,jpeg,png,gif,amr,wav');//上传文件类型

        // 请求过滤不安全以及空格字符串
        Request::instance()->filter(['strip_tags', 'trim']);
    }

    public function getQuesTitle(){
        // 验证登陆session
        if (!session("?user_info"))
            return ['info'=>'登录状态失效', 'login'=>0];

        //检查发单许可
        if(session("user_info")['is_authentic']==0){
            return ["status"=>0,"info"=>"尚未完善资料，请先完善后发单！"];
        }

        $data = db("ques_title")->field("id,title")->select();
        if(count($data)>0){
            return ["status"=>1,"data"=>$data,"info"=>""];
        }
        return ["status"=>0,"info"=>"初始化失败！"];
    }
    // 用户提交疑难
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

        $ajax['uid'] = session("user_info")['id'];
        $ajax['create_time'] = time();
        $ajax['update_time'] = $ajax['create_time'];
        $ajax['ip'] = get_client_ip();
        // 上传图片
        if (request()->file() != null) {
            $files = $this->upload();

            //根据files是否为数组鉴定是否有上传错误和超范围
            if(!is_array($files))
                return['status'=>0,'info'=>$files];

            $ajax = array_merge($ajax, $files);
        }
        $ajax['is_del'] = 0;
        $ajax['is_effect'] = 1;
        $ajax['state'] = 0;
        //正式插入需求表
        $f = db("Ques")->insert($ajax);
        if ($f >= 1){
            //修改用户表一些参数
            db("user")->where('id',session("user_info")['id'])->setInc("count_que");//用户未接单需求+1
            db("user")->where('id',session("user_info")['id'])->setDec("money",$ajax['price']);//用户未余额减去金额，扣钱

            return ['status'=>1, 'info'=>'需求单提交成功'];
        }
        else return ['status'=>0, 'info'=>'数据插入失败'];
    }

    // 删除一条疑难
    public function deleteQues() {
        if (!session("?user_info"))
            return ['info'=>'登录状态失效', 'login'=>0];

        $id = input("post.id/d");
        if ($id <= 0 || $id == null)
            return ['info'=>'无效的删除项', 'status'=>0];

        $f = db("Ques")->where('uid', session("user_info")['id'])->where('state',0)->delete($id);

        if ($f == 0)
            return ['status'=>0, 'info'=>'已经接单的需求无法删除！'];

        if($f['state']==0)//未接单情况下
            db("user")->where('id',session("user_info")['id'])->setDec("count_que");//未接单需求-1
        return ['status'=>1, '已删除'];
    }

    //列出需求单
    public function listQues(){
        if (!session("?user_info"))
            return ['info'=>'登录状态失效', 'login'=>0];

        // 取得前端最小的id，即最早的一条消息，寻找更早的消息,以此来实现瀑布流
        $user_id = input('post.id/d');
        $min_id = input('post.min_id/d');

        //检查不在服务区的用户排除
        $is_service = db("User")->where('id',$user_id)->column('is_service')[0];
        if(!$is_service){
            return ['status'=>0,'info'=>"该用户禁止访问！"];
        }

        if ($min_id == 0)   //第一次加载时从头加载
            $data = db("view_ques")->where('uid',$user_id)->where('is_del',0)->where('is_effect',1)->field('id,title,content,thumbnail,reward,price,create_time,state')->order('id desc')->limit(F_PAGE)->select();
        else    //加载更多
            $data = db("view_ques")->where('uid',$user_id)->where('id', '<', $min_id)->where('is_del',0)->where('is_effect',1)->field('id,title,content,thumbnail,reward,price,create_time,state')->order('id desc')->limit(M_PAGE)->select();

        if (!empty($data)) {
            for($i = 0; $i < count($data); $i++) {
                $data[$i]['content'] = mb_substr($data[$i]['content'], 0, 50, 'utf-8') . " ......";
            }
        }

        return ['status'=>1, 'data'=>$data];
    }

    //查看需求详情
    public function detail() {
        if (!session("?user_info"))
            return ['info'=>'登录状态失效', 'login'=>0];

        $id = input("post.id/d");
        $where['is_del']=0;
        $where['is_effect']=1;
        $where['id']=$id;

        if ($id <= 0 || $id == null)
            return ['status'=>0, 'info'=>'无效的需求'];


        $data = db("view_ques")->where($where)->find();

        if ($data == null)
            return ['status'=>0, 'info'=>'您的查看内容已被删除或者没有权限'];

        return ['status'=>1, 'info'=>'查询成功', 'data'=>$data];
    }

    // 文件上传转储（多文件）
    // 新版上传至阿里oss服务器
    // 2017-8-21
    // @return 如果成功返回文件键值对数组用于数据库存储
    // 如果失败返回String，包含上传错误信息
    private function upload() {

        $files = request()->file();//取得上传文件名
        $data = [];//文件名，键值对

        //便利每个文件
        foreach ( $files as $key => $file ) {
            // 自动保存为临时文件
            $image = $file->rule("get_rand")->validate(['ext'=>UPLOAD_EXT,'size'=>UPLOAD_SIZE])->move(ROOT_PATH . 'public' . DS . 'uploads');

            if ($image) {
                $flag = uploadToOSS($image->getRealPath(),$image->getFilename());//上传至阿里OSS服务器
                if(!$flag)
                    return "文件转储失败！";

                //保存文件名
                $data[$key] = $image->getFileName();

                //压缩第一张图片
                if ($key == 'img0') {
                    $zip = $this->zipImg($image);
                    if($zip['status']){
                        $data['thumbnail'] = $zip['name'];
                    }else{
                        return $zip['message'];
                    }
                }

            } else {
                return $file->getError();
            }

            //删除临时文件
            unlink($image->getRealPath());
        }
        return $data;
    }

    //压缩图片
    //$img 图片对象
    //$w 宽度
    //$h 高度
    //@return array(状态:status，消息:message，文件名:name)
    private function zipImg($img,$w=300,$h=200){
        //打开图片，传入绝对路径
        $image = Image::open($img->getRealPath());

        //压缩图片名
        $thumbName = "thumb_".$img->getFilename();

        //压缩图片的绝对路径
        $thumb_file = $img->getPath().DS.$thumbName;

        //压缩300*200
        $image->thumb($w, $h, Image::THUMB_CENTER)->save($thumb_file);

        //上传至阿里云OSS
        $flag = uploadToOSS($thumb_file,$thumbName);
        //删除临时文件
        unlink($thumb_file);

        if(!$flag)//转储失败
            return ['status'=>false,"message"=>"转储失败"];

        return ['status'=>true,"message"=>"压缩完成","name"=>$thumbName];
    }

    //余额足够返回true，不够返回false
    private function checkMoney($price){
    }

    /*
    //早期上传文件，存放于本地http服务器
    //最新采用oss存储，节约web服务器空间
    private function oldupload() {
        //创建的目录名称，日期(相对项目目录。用于数据库保存)
        $dirName = "public".DS."uploads".DS.(date('Ymd'));
        //创建保存目录（绝对路径。用于保存文件）
        $saveDir = ROOT_PATH.DS.$dirName;

        if (!file_exists($saveDir)){
            mkdir($saveDir);
        }

        $files = request()->file();//取得上传文件名


        foreach ( $files as $key => $file ) {
            // 自动生成文件名
            $info = $file->rule("get_rand")->validate(['ext'=>UPLOAD_EXT,'size'=>UPLOAD_SIZE])->move($saveDir,true,false);

            if ($info) {
                //保存到数据的值
                $data[$key] = $dirName .DS. $info->getFileName();
            } else {
                return $file->getError();
            }

            // 压缩第一张图
            // 最好将gif跳过
            if ($key == 'img0') {
                $image = Image::open($info->getRealPath());
                // 缩略图前面加thumb
                $thumbName = "thumb_".$info->getFilename();

                // 绝对路径加上缩略图名
                $save = $saveDir.DS.$thumbName;
                $image->thumb(300, 200, Image::THUMB_CENTER)->save($save);

                $data['thumbnail'] = $dirName.DS.$thumbName;
            }
        }
        return $data;
    }
     */
}
?>
