<?php
/**
 * Created by PhpStorm.
 * User: yangyang
 * Date: 2018/11/15
 * Time: 下午3:07
 */

namespace classes\index;

use classes\vendor\AliPay;
use classes\vendor\StorageClass;

class AlipayClass extends \classes\IndexClass
{
    public $member;
    private $alipay;

    public function __construct()
    {
        $this->member = parent::member();
        $this->alipay = new AliPay();
    }

    public function pay()
    {
        return $this->alipay->pay();
    }

    public function notify()
    {
       $result = $this->alipay->notify(request()->get());

       dump($result);

       exit('ooxx');
    }
}