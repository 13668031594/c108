<?php

namespace app\exchange\controller;

use app\http\controller\AdminController;
use classes\exchange\ExchangeClass;
use think\Request;

class ExchangeController extends AdminController
{
    private $class;

    public function __construct(Request $request = null)
    {
        $this->class = new ExchangeClass();

        $this->class->is_login();
    }

    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function getIndex()
    {
        return parent::view('exchange');
    }

    public function getTable(Request $request)
    {
        $result = $this->class->index($request);

        return parent::tables($result);
    }

    public function getStatus(Request $request)
    {
        $this->class->status($request);

        return parent::success('/exchange/index');
    }
}
