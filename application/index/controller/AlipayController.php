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

    public function test(Request $request)
    {
        $this->classes->is_login();

        $this->classes->validator_create($request);

        $add = null;
        foreach ($request->post() as $k => $v) {

            $add .= $add ? '&' : '?';

            $add .= $k . '=' . $v;
        }

        return parent::success('/index/alipay' . $add);
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
        exit($result);
        //返回表单
        return parent::success('/', $result);
    }

    //回调
    public function notify()
    {
        //验证回调情况
        $this->classes->notify();

        exit('支付成功');
    }
}
