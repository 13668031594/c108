<?php
/**
 * Created by PhpStorm.
 * User: yangyang
 * Date: 2018/11/15
 * Time: 下午3:07
 */

namespace classes\index;

use classes\vendor\AliPay;

class AlipayClass extends \classes\IndexClass
{
    public $member;

    public function __construct()
    {
        $this->member = parent::member();
    }

    public function pay()
    {
        $alipay = new AliPay();

        $alipay->pay();
    }

    public function notify()
    {

    }
}