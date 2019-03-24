<?php

namespace app\index\controller;

use classes\index\AlipayClass;
use classes\index\IndexClass;
use classes\vendor\AliPay;
use think\Request;

class AlipayController extends \app\http\controller\IndexController
{
    private $classes;

    public function __construct()
    {
        parent::__construct();

        $this->classes = new AlipayClass();
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

        //返回表单
        return parent::success('/', $result);
    }

    //回调
    public function notify()
    {
        //验证回调情况
        $this->classes->notify();

        exit('success');
    }

    public function test_pay(Request $request)
    {
        $this->classes->is_login();

        $result = $this->classes->test_pay($request);

        return parent::success('/', $result);
    }
}
