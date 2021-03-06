<?php

namespace app\master\controller;

use app\http\controller\AdminController;
use classes\master\LoginClass;
use think\Request;

class LoginController extends AdminController
{
    private $class;

    public function __construct()
    {
        $this->class = new LoginClass();
    }

    public function getLogin()
    {
        $master = $this->class->master();

        if (is_null($master))
            return parent::view('login');
        else
            return self::getIndex();
    }

    public function postLogin(Request $request)
    {
        //验证
        $this->class->validator_login($request);

        //登录
        $master = $this->class->login($request);

        //修改管理员登录信息
        $this->class->refresh_master($master);

        //重定向到首页
        return parent::success('', '登录成功');
    }

    public function getLogout()
    {
        $this->class->logout();

        return self::getLogin();
    }

    public function getIndex()
    {
        $this->class->is_login();

        $master = $this->class->master();

        return parent::view('index', ['master' => $master]);
    }
}
