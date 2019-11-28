<?php

namespace dashen\app\controller;

use think\Session;
use think\Controller;
use wxpay\database\WxPayUnifiedOrder;
use wxpay\JsApiPay;
use wxpay\NativePay;
use wxpay\PayNotifyCallBack;
use think\Log;
use wxpay\WxPayApi;
use wxpay\WxPayConfig;

class Money {
    public function _initialize()
    {
        // 请求过滤不安全以及空格字符串
        Request::instance()->filter(['strip_tags', 'trim']);
    }

    public function getMoney() {
        // 验证登陆session
        if (!session("?user_info"))
            return ['info' => '登录状态失效', 'login' => 0];

        $money =db("User")->where('id',session("user_info")['id'])->column('money')[0];
        if($money===null)
            return["status"=>0,"info"=>"获取钱包金额失败"];

        return["status"=>1,"info"=>"获取成功","data"=>$money];
    }

    /*
     * 需要申请权限
     */
    //生成订单
    public function createOrder(){
        // 验证登陆session
        // if (!session("?user_info"))
        //    return ['info' => '登录状态失效', 'login' => 0];

        //获取支付密钥
        $pconfig = db("payment_config")->where("type","weixin")->find();

        $money = input("get.money/f")*100;

        // 商品名称
        $subject = 'DCloud项目捐赠';
        // 订单号，示例代码使用时间值作为唯一的订单ID号
        $out_trade_no = date('YmdHis', time());
        // 商品金额（单位为分）
        $total = 10;

        $unifiedOrder = new WxPayUnifiedOrder();
        $unifiedOrder->SetBody($subject);//商品或支付单简要描述
        $unifiedOrder->SetOutTradeNo($out_trade_no);
        $unifiedOrder->SetTotalFee($total);
        $unifiedOrder->SetTradeType("APP");
        $unifiedOrder->setNotifyUrl("localhost");//异步通知url
        $unifiedOrder->setAppid($pconfig['APPID']);//公众账号ID
        $unifiedOrder->setMchId($pconfig['mch_id']);//商户

        $result = WxPayApi::unifiedOrder($unifiedOrder);

        return $result;
    }


    //后端接受订单数据
    public function getOrderNotify(){
        $data['name']="zhifu";
        $data['type']="zhifu";
        $data['APPID']="ssss";
        $data['APPSECRET']="ssss";
        $data['mch_id']="ssss";
        $data['mch_keyString']="ssss";
        db("payment_config")->insert();
    }
}
