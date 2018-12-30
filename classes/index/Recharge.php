<?php
/**
 * Created by PhpStorm.
 * User: yangyang
 * Date: 2018/11/15
 * Time: 下午4:23
 */

namespace classes\index;


use app\member\model\Member;
use app\member\model\MemberRecord;
use app\recharge\model\RechargePay;
use classes\FirstClass;
use classes\setting\Setting;
use classes\vendor\Wechat;

class Recharge extends FirstClass
{
    public $member;

    public function __construct()
    {
        $this->member = parent::is_login_member();

        if ($this->member['status'] == '1') parent::ajax_exception(000, '您的账号被冻结了');
    }

    //验证下单字段
    public function validator_save()
    {
        $setting = new Setting();
        $set = $setting->index();

        if ($set['payRechargeSwitch'] == 'off') parent::ajax_exception(000, '众筹已关闭');

        $rule = [
            'amount' => 'require|integer|between:1,100000000',
            'pay_pass' => 'require',
            'webAjAmount' => 'require',
            'webAjJpj' => 'require',
        ];

        $message = [
            'webAjAmount.require' => '请刷新重试',
            'webAjJpj.require' => '请刷新重试',
        ];

        $file = [
            'amount' => '众筹金额',
            'pay_pass' => '支付密码'
        ];

        $result = parent::validator(input(), $rule, $message, $file);
        if (!is_null($result)) parent::ajax_exception(000, $result);

        //验证是否有未完结的订单
        $model = new \app\recharge\model\Recharge();
        $test = $model->where('member_id', '=', $this->member['id'])->where('order_status', '=', '10')->order('created_at', 'desc')->find();
        if (!is_null($test)) parent::ajax_exception(00, '您有一个未完结的订单，请完结后再试。');

        //验证支付密码
        if (md5(input('pay_pass')) != $this->member['pay_pass']) parent::ajax_exception(000, '支付密码错误');

        //验证兑换比例
        if ((input('webAjAmount') != $set['webAjAmount']) || (input('webAjJpj') != $set['webAjJpj'])) parent::ajax_exception(000, '请刷新重试001');
    }

    //下单
    public function save()
    {
        //计算获得家谱卷
        $jpj = input('amount') / input('webAjAmount') * input('webAjJpj');

        $order = new \app\recharge\model\Recharge();
        $order->order_number = self::new_order();
        $order->total = input('amount');
        $order->jpj = number_format($jpj, 2, '.', '');
        $order->proportion = input('webAjAmount') . ':' . input('webAjJpj');
        $order->member_id = $this->member['id'];
        $order->member_account = $this->member['account'];
        $order->member_nickname = $this->member['nickname'];
        $order->member_create = $this->member['created_at'];
        $order->created_at = date('Y-m-d H:i:s');
        $order->save();

        return $order->getData();
    }

    //获取新的订单号
    private function new_order()
    {
        $pattern = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';//字幕字符串

        $key = time();//时间戳

        //再随机2位字幕
        for ($i = 0; $i < 2; $i++) {
            $key .= $pattern[rand(0, 25)];    //生成php随机数
        }

        //验证订单号是否被占用
        $test = new \app\recharge\model\Recharge();
        $test = $test->where('order_number', '=', $test)->find();

        if (!is_null($test)) {

            return self::new_order();
        } else {

            return $key;
        }
    }

    public function pay($order)
    {
        if (isset($order['order_status']) && $order['order_status'] != '10') parent::ajax_exception(000, '订单已锁定，无法支付');
        if (empty($this->member['wechat_id'])) parent::ajax_exception(000, '请从微信公众号重新登录');

        $result = [
            'body' => '家谱众筹',
            'out_trade_no' => $order['order_number'] . '_' . time(),//订单号
            'total_fee' => ($order['total'] * 100),//金额，精确到分
//            'total_fee' => 1,//金额，精确到分
            'order_type' => 'recharge',//订单类型，回调路由组成部分
            'openid' => $this->member['wechat_id']
        ];

        $class = new Wechat();

        $result = $class->jsapi($result);

        //重新配置并获取微信签名
        $sign = $class->jsapi_sign($result);

        return $sign;
    }

    //轮询
    public function info($id)
    {
        $recharge = new \app\recharge\model\Recharge();
        $recharge = $recharge->where('id', '=', $id)->where('order_status', '=', '10')->find();
        if (!is_null($recharge)) parent::ajax_exception(000, '');
    }

    //撤销
    public function out($id)
    {
        $recharge = new \app\recharge\model\Recharge();

        $recharge = $recharge->where('id', '=', $id)->find();

        if (is_null($recharge)) return;

        $recharge->order_status = '40';
        $recharge->change_id = $this->member['id'];
        $recharge->change_nickname = $this->member['nickname'];
        $recharge->change_date = date('Y-m-d H:i:s');
        $recharge->save();
    }
}