<?php

namespace app\member\controller;

use app\http\controller\IndexController;
use classes\member\LoginClass;
use classes\vendor\AliPay;
use classes\vendor\StorageClass;
use think\Db;
use think\Request;

class LoginController extends IndexController
{
    private $class;

    public function __construct()
    {
        parent::__construct();

        $this->class = new LoginClass();
    }

    //登录页面
    public function getLogin()
    {
        //验证是否登录
        $log = $this->class->loged();
        if (!$log) return redirect('/');

        //获取保存的账号
        $account = $this->class->account();

        //视图
        return parent::view('login', ['account' => $account]);
    }

    //登录方法
    public function postLogin()
    {
        Db::startTrans();

        //验证是否登录
        $log = $this->class->loged();
        if (!$log) return redirect('/');

        //验证字段
        $this->class->validator_login();

        //验证登录
        $member = $this->class->login();

        //修改管理员登录信息
        $ass = $this->class->refresh_member($member);

        //保存登录状态
        $this->class->refresh_login_member($member->id, $ass);

        //保存账号
        $this->class->save_account($member->account);

        Db::commit();

        //重定向到首页
        return redirect('/');
    }

    //注销方法
    public function logout()
    {
        //注销管理员session
        $this->class->logout();

        //调用view方法
        return self::getLogin();
    }

    //注册页面
    public function reg()
    {
        $account = input('referee_account');

        return parent::view('register', ['referee_account' => $account]);
    }

    //注册会员
    public function register()
    {
        Db::startTrans();

        //注册验证
        $this->class->validator_reg();

        //注册
        $this->class->reg();

        Db::commit();

        return parent::success('/index/login');
    }

    //短信发送
    public function sms_reg($phone)
    {
        //当前时间戳
        $time = time();

        //验证
        $this->class->validator_sms_register($phone, $time);

        //删除所有过期验证码
        $this->class->delete_sms($time);

        //发送
        $end = $this->class->send_sms($phone, $time);

        //反馈
        return parent::success('', '发送成功', ['time' => $end]);
    }

    //密码找回
    public function res()
    {
        return parent::view('password-back');
    }

    //密码找回短信
    public function sms_reset($phone)
    {
        //当前时间戳
        $time = time();

        //验证
        $this->class->validator_sms_reset($phone, $time);

        //删除所有过期验证码
        $this->class->delete_sms($time);

        //发送
        $end = $this->class->send_sms($phone, $time, 'reset');

        //反馈
        return parent::success('', '发送成功', ['time' => $end]);
    }

    //密码找回方法
    public function reset()
    {
        $this->class->validator_reset();

        $this->class->reset();

        return parent::success();
    }

    public function test()
    {
        $class = new AliPay();

        $class->pay();
    }

    public function notify()
    {
        $a = new StorageClass('notify.txt');

        $a->save('1231233');

        exit('ok');
    }
}
