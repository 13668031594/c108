<?php
/**
 * Created by PhpStorm.
 * User: yangyang
 * Date: 2018/11/26
 * Time: 下午3:23
 */

namespace classes;


use app\member\model\MemberModel;
use classes\system\SystemClass;

class IndexClass extends FirstClass
{
    public $user;

    public function member()
    {
        if (!is_null($this->user)) return $this->user;

        $member = session('member');
        $model = new MemberModel();
        $member = $model->where('id', '=', $member['id'])->find();
        return $member ? $member->getData() : null;
    }

    public function is_login()
    {
        //尝试获取session中的member信息
        $member = session('member');

        //验证session中的信息格式与过期时间
        if (is_null($member) || !is_array($member) || !isset($member['id']) || !isset($member['login_ass']) || !isset($member['time']) || ($member['time'] < time())) self::errors();

        //初始化管理员模型
        $members = new MemberModel();

        //尝试获取管理员资料
        $members = $members->where('id', '=', $member['id'])->find();

        //没有获取到管理员资料，跳转至登录页面
        if (is_null($members)) self::errors();

        //登录密钥验证
        if ($member['login_ass'] != $members->login_ass) self::errors();

        //更新操作时间
        $member['time'] = time() + config('young.index_login_time');
        session('member', $member);

        $this->user = $members->getData();
    }

    //报错
    private function errors()
    {
        session('member', null);

        parent::redirect_exception('/index/login', '请重新登录');
    }

    public function status()
    {
        $member = self::member();

        if ($member['status'] == '1') parent::ajax_exception(000, '您的账号已经被冻结');

    }

    /**
     * 原路返回的报错
     *
     * @param $error
     */
    protected function back($error)
    {
        parent::redirect_exception(request()->url(), $error);
    }

    public function set()
    {
        $set = new SystemClass();

        return $set->index();
    }
}