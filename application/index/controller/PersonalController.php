<?php
/**
 * Created by PhpStorm.
 * User: yangyang
 * Date: 2018/11/16
 * Time: 上午12:06
 */

namespace app\index\controller;


use app\member\model\MemberModel;
use classes\index\IndexClass;

class PersonalController extends \app\http\controller\IndexController
{
    private $class;
    public $member;

    public function __construct()
    {
        parent::__construct();

        $this->class = new IndexClass();

        session('floor', 'self');
    }

    //个人中心
    public function personal()
    {
        $grades = new MemberModel();

        $grades = $grades->grades;

        $result = [
            'member' => $this->class->member,
            'grades' => $grades
        ];

        return parent::view('personal', $result);
    }

    //个人资料
    public function self()
    {
        return parent::view('data', ['member' => $this->class->member]);
    }

    //修改昵称
    public function nickname()
    {
        $this->class->nickname();
        return parent::success('/');
    }

    //登录密码修改页面
    public function pass()
    {
        return parent::view('login-password-change', ['member' => $this->class->member]);
    }

    //修改登录密码
    public function password()
    {
        $this->class->password();
        return parent::success('/');
    }

    //支付密码修改页面
    public function pay_pass()
    {
        return parent::view('pay-password-change', ['member' => $this->class->member]);
    }

    //支付密码修改
    public function pay_password()
    {
        $this->class->pay_pass();
        return parent::success('/');
    }

    //分享
    public function share()
    {
        $route = '/index/reg?referee_account=' . $this->class->member['account'];

        $url = $this->class->make_qr('share', $route);

        return parent::view('shared', ['url' => $url]);
    }

    //激活资产页面
    public function act()
    {
        //未完结的订单
        $test = $this->class->active_test();

        //有
        if (!is_null($test)) {

            $recharge = $test->getData();

            $wechat = $this->class->act_pay($recharge);
//dump($recharge);
            return parent::view('activate-info', ['order' => $recharge, 'wechat' => json_encode($wechat)]);
        } else {

            return parent::view('activate', ['member' => $this->class->member]);
        }

    }

    //激活资产
    public function acted()
    {
        $this->class->validator_act();

        $order = $this->class->act();

        $result = $this->class->act_pay($order);

        return parent::success('', null, ['wechat' => $result, 'order' => $order]);
    }

    //激活轮询
    public function info($id)
    {
        $this->class->info($id);

        return parent::success();
    }

    //撤销激活订单
    public function act_out($id)
    {
        $this->class->out($id);

        return parent::success('/');
    }

    //支付记录
    public function pay_note()
    {
        $result = $this->class->pay_note();

        $result['member'] = $this->class->member;

        return parent::view('pay-note', $result);
    }

    //财务翻页
    public function pay_note_table()
    {
        $result = $this->class->pay_note();

        return parent::tables($result);
    }

    //团队页面
    public function team()
    {
        $team = $this->class->team($this->class->member['id']);

        $total = $this->class->team_total($this->class->member['id']);

        $result = [
            'team' => $team['member'],
            'self' => $this->class->member,
            'grades' => $team['grades']
        ];

        $result = array_merge($result,$total);

        return parent::view('team', $result);
    }

    //团队数据
    public function team_table($id)
    {
        $team = $this->class->team($id);

        return parent::tables(['message' => ['list' => $team]]);
    }
}