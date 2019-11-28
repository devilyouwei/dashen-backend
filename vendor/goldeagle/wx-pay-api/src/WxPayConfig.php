<?php
namespace wxpay;

/**
 * 微信配置账号信息文件，在使用框架的时候可以使用其他方式来替代
 *
 * Class WxPayConfig
 * @package wxpay
 * @author goldeagle
 */
class WxPayConfig
{
    const APPID = 'wx9b448b375a970212';
    const MCHID = '1435977102';
    const KEY = 'wx9b448b375a970212';
    const APPSECRET = 'd3db73b0dbfcea980ae18ced9900ff34';
    const NOTIFY_URL = 'http://dashen/app/Money/getOrderNotify?id=1';

    const SSLCERT_PATH = '../cert/apiclient_cert.pem';
    const SSLKEY_PATH = '../cert/apiclient_key.pem';

    const CURL_PROXY_HOST = "0.0.0.0";//"10.152.18.220";
    const CURL_PROXY_PORT = 0;//8080;

    const REPORT_LEVENL = 1;

    private static $instance = null;

    private function __construct()
    {
    }

    public function __clone()
    {
        trigger_error('Singleton CANNOT NOT be cloned!', E_USER_ERROR);
    }

    public static function getInstance()
    {
        if (self::$instance === null || !(self::$instance instanceof self)) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}
