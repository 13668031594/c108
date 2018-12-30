<?php

namespace app\index\controller;

use think\Db;
use think\Request;

class AssetchangeController extends \app\http\controller\IndexController
{
    private $class;

    public function __construct()
    {
        parent::__construct();

        $this->class = new \classes\index\AssetChangeClass();

        $this->class->is_login();

        $this->class->status();
    }

    //转出
    public function asset_out()
    {
        $this->class->validator_out();

        $this->class->out();

        return parent::success('/');
    }

    //报单
    public function exchange(Request $request)
    {
        Db::startTrans();

        $result = $this->class->validator_exchange($request);

        Db::commit();

        return parent::success('/');
    }

    public function withdraw()
    {

    }
}
