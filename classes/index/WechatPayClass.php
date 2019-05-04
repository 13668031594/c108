<?php
/**
 * Created by PhpStorm.
 * User: yangyang
 * Date: 2018/11/15
 * Time: 下午3:07
 */

namespace classes\index;

use app\goods\model\GoodsModel;
use app\member\model\MemberModel;
use app\member\model\MemberRecordModel;
use app\order\model\OrderModel;
use classes\system\SystemClass;
use classes\vendor\AliPay;
use classes\vendor\StorageClass;
use classes\vendor\WechatPay;
use think\Request;

class WechatPayClass extends \classes\IndexClass
{
    private $alipay;
    public $member;
    public $in_member;
    public $asset;
    public $set;
    public $other;
    public $date;

    public function __construct()
    {
        $this->member = parent::member();
        $this->alipay = new WechatPay();
    }

    public function validator_create(Request $request)
    {
        //验证条件
        $rule = [
            'pay_pass|支付密码' => 'require|length:1,255',
            'man|收货人' => 'require|length:1,255',
            'phone|联系电话' => 'require|length:1,255',
            'address|收货地址' => 'require|length:1,255',
        ];

        //验证
        $result = parent::validator($request->post(), $rule);

        //有错误报告则报错
        if (!is_null($result)) parent::ajax_exception(000, $result);

        //会员信息
        $member = parent::member();

        $pay_pass = $request->post('pay_pass');
        if (md5($pay_pass) != $member['pay_pass']) parent::ajax_exception(000, '支付密码错误');

        //获取报单等级和单价
        $radio = $request->post('radio');
        list($level, $remind) = explode('|', $radio);

        //初始化设置类
        $class = new SystemClass();

        //获取升级后的等级
        $after = $class->get_grade($level, $member['grade']);
        if ($after === false) parent::ajax_exception(000, '请刷新重试1');

        //判断单价是否相等
        $set = $class->index();
        if ($remind != $set[$level]) parent::ajax_exception(000, '请刷新重试2');
    }

    //报单验证
    public function create_order(Request $request)
    {
        //验证条件
        $rule = [
            'pay_pass|支付密码' => 'require|length:1,255',
            'man|收货人' => 'require|length:1,255',
            'phone|联系电话' => 'require|length:1,255',
            'address|收货地址' => 'require|length:1,255',
        ];

        //验证
        $result = parent::validator($request->get(), $rule);

        //有错误报告则报错
        if (!is_null($result)) parent::ajax_exception(000, $result);

        //会员信息
        $member = parent::member();
//        if (($member['phone'] != '13668031594') && ($member['phone'] != '13608302076')) parent::ajax_exception(000, '测试中');
        $pay_pass = $request->get('pay_pass');
        if (md5($pay_pass) != $member['pay_pass']) parent::ajax_exception(000, '支付密码错误');

        //获取报单等级和单价
        $radio = $request->get('radio');
        list($level, $remind) = explode('|', $radio);

        //初始化设置类
        $class = new SystemClass();

        //获取升级后的等级
        $after = $class->get_grade($level, $member['grade']);
        if ($after === false) parent::ajax_exception(000, '请刷新重试1');

        //判断单价是否相等
        $set = $class->index();
        if ($remind != $set[$level]) parent::ajax_exception(000, '请刷新重试2');

        //初始化会员模型
        $model = new MemberModel();

        //会员等级数组
        $grades = $model->grades;

        //产品名称
        $order = $class->level;

        $other = self::new_order();

        $this->date = date('Y-m-d H:i:s');

        //报单记录
        $order_model = new OrderModel();
        $order_model->order_number = $other;
        $order_model->remind = $remind;
        $order_model->before_key = $member['grade'];
        $order_model->before_value = $grades[$member['grade']];
        $order_model->after_key = $after;
        $order_model->after_value = $grades[$after];
        $order_model->level_code = $level;
        $order_model->level_name = $order[$level];
        $order_model->member_id = $member['id'];
        $order_model->member_account = $member['account'];
        $order_model->member_nickname = $member['nickname'];
        $order_model->member_phone = $member['phone'];
        $order_model->man = $request->get('man');
        $order_model->phone = $request->get('phone');
        $order_model->address = $request->get('address');
        $order_model->created_at = $this->date;
        $order_model->pay_status = 10;
        $order_model->pay_type = 30;
        $order_model->save();

        return [
            'body' => $order[$level],
            'subject' => $order[$level],
            'out_trade_no' => $other,
            'total_fee' => floor($remind * 100),
//            'total_fee' => 1,
        ];
    }

    //执行支付
    public function pay($param)
    {
        return $this->alipay->pay($param);
    }

    //执行回调，并验证数据格式
    public function notify()
    {
        //验签
        $result = $this->alipay->notify();
        if (!$result) parent::ajax_exception(000, '验证失败');

        list($order_number) = explode('_', $result['out_trade_no']);

        return $order_number;
    }

    public function test_pay(Request $request)
    {
        $member = parent::member();

        $order_number = $request->post('order_number');

        $order_model = new OrderModel();
        $order_model = $order_model->where('order_number', '=', $order_number)->where('member_id', '=', $member['id'])->find();

        if (!is_null($order_model) && ($order_model->pay_status == 20)) return 'success';

        return 'failes';
    }

    //新的订单号
    private function new_order()
    {
        $order = 'o' . time() . rand(100, 999);

        $model = new OrderModel();
        $test = $model->where('order_number', '=', $order)->find();

        if ($test) return self::new_order();

        return $order;
    }

    public function test()
    {
        $order_number = \request()->get('order_number');

        $order_model = new OrderModel();
        $order_model = $order_model->where('order_number', '=', $order_number)->where('pay_type', '=', 30)->where('pay_status', '=', 20)->find();
        if (is_null($order_model)) exit('支付失败');//parent::ajax_exception(000, '订单信息错误');
    }
}