<?php

namespace app\bill\controller;

use app\http\controller\AdminController;
use classes\bill\BillClass;
use think\Request;

class BillController extends AdminController
{
    public $class;

    public function __construct()
    {
        $this->class = new BillClass();

        $this->class->is_login();
    }

    public function getTotal()
    {
        return parent::view('order_total');
    }

    public function getTotalTable()
    {
        $this->class->time();

        $total = $this->class->total();

        return parent::tables($total);
    }

    public function getMember()
    {
        return parent::view('member');
    }

    public function getMemberTable()
    {
        $this->class->time();

        $total = $this->class->member();

        return parent::tables($total);
    }

    public function getWithdraw()
    {
        return parent::view('withdraw');
    }

    public function getWithdrawTable()
    {
        $this->class->column = 'change_date';

        $this->class->time();

        $total = $this->class->withdraw();

        return parent::tables($total);
    }
}
