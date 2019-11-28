<?php
namespace dashen\home\controller;

class Index
{
    public function index()
    {
        echo "public:".ROOT_PATH;
        $res = db('User')->select();
        if(count($res==0)){
            return "数据连接成功";
        }
        return "error";
    }
}
