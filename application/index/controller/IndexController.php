<?php

namespace app\index\controller;

use classes\index\IndexClass;

class IndexController extends \app\http\controller\IndexController
{
    private $class;

    public function __construct()
    {
        parent::__construct();

        $this->class = new IndexClass();

        $this->class->is_login();

        session('floor', 'index');
    }

    public function header()
    {
        return parent::view('header');
    }

    public function header2()
    {
        return parent::view('header2');
    }

    public function floor()
    {
        return parent::view('floor');
    }

    //首页
    public function index()
    {
        $result = $this->class->header2('首页');

        $result['first_login'] = session('first_login');

        session('first_login', null);

        return parent::view('index', $result);
    }

    //家谱
    public function family()
    {
        $result = $this->class->header2('家谱');

        return parent::view('family', $result);
    }

    //纪念堂
    public function memorial()
    {
        $result = $this->class->header2('纪念堂');

        return parent::view('memorial-hall', $result);
    }

    //家族共享
    public function shared()
    {
        $result = $this->class->header2('家族共享');

        return parent::view('family-shared', $result);
    }

    //资讯中心
    public function information()
    {
        $result = $this->class->header2('资讯中心');

        $result['all_notice'] = $this->class->notice();

        return parent::view('information', $result);
    }

    //资讯中心-翻页
    public function information_table()
    {
        $result = $this->class->notice();

        return parent::tables($result);
    }

    //财务
    public function financial()
    {
        $result = $this->class->header2('财务');

        $result['record'] =  $this->class->record();
        $result['type'] = input('type');

        return parent::view('financial', $result);
    }

    //财务翻页
    public function financial_table()
    {
        $result = $this->class->record();

        return parent::tables($result);
    }

    //转入二维码
    public function shift_to_qr()
    {
        $route = '/index/roll-out/' . $this->class->member['id'];

        $url = $this->class->make_qr('asset_in', $route);

        return parent::view('shift-to-qr', ['url' => $url]);
    }

    //转出页面
    public function roll_out($id)
    {
        $out_man = $this->class->out_man($id);

        return parent::view('roll-out', ['out_man' => $out_man,'member' => $this->class->member]);
    }

    //转换
    public function exchange()
    {
        $result = $this->class->up_grades();

        return parent::view('exchange', $result);
    }

    //祭拜
    public function worship()
    {
        $result = $this->class->header2('祭拜');

        return parent::view('worship',$result);
    }

    //增值服务
    public function added()
    {
        $result = $this->class->header2('增值服务');

        return parent::view('added',$result);
    }
}
