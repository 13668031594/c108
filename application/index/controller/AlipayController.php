<?php

namespace app\index\controller;

use classes\index\IndexClass;
use classes\vendor\AliPay;

class AlipayController extends \app\http\controller\IndexController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function pay(){

        $class = new IndexClass();

        $class->is_login();

        $alipay = new AliPay();

        $alipay->pay();
    }
}
