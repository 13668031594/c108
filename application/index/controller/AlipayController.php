<?php

namespace app\index\controller;

use classes\index\AlipayClass;
use classes\index\IndexClass;
use classes\vendor\AliPay;

class AlipayController extends \app\http\controller\IndexController
{
    private $classes;

    public function __construct()
    {
        parent::__construct();

        $this->classes = new AlipayClass();
    }

    public function pay()
    {
        //验证登录
//        $this->classes->is_login();

        //进行支付
        $this->classes->pay();
    }

    public function notify()
    {
        //验证回调情况
        $this->classes->notify();

        exit('ojbk');
    }
}
