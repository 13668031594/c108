<?php

namespace app\index\controller;

use classes\index\AlipayClass;
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
//        $this->classes->is_login();

        //自行下单
//        $param = $this->classes->create_order($request);

        $param = (array)json_decode('{"body":"会员激活","subject":"会员激活","out_trade_no":"o1553416966638","total_amount":"0.01"}');

        //进行支付
        $result = $this->classes->pay($param);
exit($result);
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
