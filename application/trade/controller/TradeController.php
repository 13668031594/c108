<?php

namespace app\trade\controller;

use app\http\controller\AdminController;
use classes\trade\TradeClass;
use think\Request;

class TradeController extends AdminController
{
    private $class;

    public function __construct(Request $request = null)
    {
        $this->class = new TradeClass();

        $this->class->is_login();
    }

    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function getIndex()
    {
        return parent::view('trade');
    }

    public function getTable(Request $request)
    {
        $result = $this->class->index($request);

        return parent::tables($result);
    }
}
