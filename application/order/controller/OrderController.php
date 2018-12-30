<?php

namespace app\order\controller;

use app\http\controller\AdminController;
use classes\order\OrderClass;
use think\Request;

class OrderController extends AdminController
{
    private $class;

    public function __construct(Request $request = null)
    {
        $this->class = new OrderClass();

        $this->class->is_login();
    }

    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function getIndex()
    {
        return parent::view('order');
    }

    public function getTable(Request $request)
    {
        $result = $this->class->index($request);

        return parent::tables($result);
    }
}
