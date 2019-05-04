<?php

namespace app\index\controller;

use classes\index\AlipayClass;
use classes\index\PayClass;
use classes\index\WechatClass;
use classes\index\WechatPayClass;
use classes\vendor\StorageClass;
use think\Request;

class WechatController extends \app\http\controller\IndexController
{
    private $classes;

    public function __construct()
    {
        parent::__construct();

        $this->classes = new WechatPayClass();
    }

    public function test(Request $request)
    {
        $this->classes->is_login();

        $this->classes->validator_create($request);

        $add = null;
        foreach ($request->post() as $k => $v) {

            $add .= $add ? '&' : '?';

            $add .= $k . '=' . $v;
        }

        return parent::success('/index/wechat' . $add);
    }

    //下单
    public function pay(Request $request)
    {
        //验证登录
        $this->classes->is_login();

        //自行下单
        $param = $this->classes->create_order($request);

        //进行支付
        $result = $this->classes->pay($param);

        return redirect($result['mweb_url'].'&redirect_url=http%3A%2F%2Fwww.ahu66.com/notify_wechat',302);
        exit();
        return url($result['mweb_url'].'&redirect_url=http%3A%2F%2Fwww.ahu66.com/notify_wechat');
return $result;
        //返回表单
        return parent::success('/', $result);
    }

    //回调
    public function notify()
    {
        //验证回调情况
        $order_number = $this->classes->notify();

        $payClass = new PayClass();

        //完结订单
        $payClass->over_order($order_number);

        exit('success');
    }

    public function notify_test()
    {
        $all = \request()->get();
        dump($all);
        exit;
    }
}
