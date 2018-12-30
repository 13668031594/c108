<?php
/**
 * Created by PhpStorm.
 * User: yangyang
 * Date: 2018/11/21
 * Time: 下午3:26
 */

namespace classes;

use app\master\model\MasterModel;

class AdminClass extends FirstClass
{
    public $user;

    public function master()
    {
        if (! is_null($this->user))return $this->user;

        $master = session('master');
        $model = new MasterModel();
        $master = $model->where('id', '=', $master['id'])->find();
        return $master ? $master->getData() : null;
    }

    public function is_login()
    {
        //尝试获取session中的master信息
        $master = session('master');

        //验证session中的信息格式与过期时间
        if (is_null($master) || !is_array($master) || !isset($master['id']) || !isset($master['login_ass']) || !isset($master['time']) || ($master['time'] < time())) self::errors();

        //初始化管理员模型
        $masters = new MasterModel();

        //尝试获取管理员资料
        $masters = $masters->where('id', '=', $master['id'])->find();

        //没有获取到管理员资料，跳转至登录页面
        if (is_null($masters)) self::errors();

        //登录密钥验证
        if ($master['login_ass'] != $masters->login_ass) self::errors();

        //更新操作时间
        $master['time'] = time() + config('young.admin_login_time');
        session('master',$master);

        $this->user = $masters->getData();
    }

    //报错
    private function errors()
    {
        session('master',null);

        parent::redirect_exception('/admin/login','请重新登录');
    }
}