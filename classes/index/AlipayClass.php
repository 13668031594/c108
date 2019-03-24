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
use think\Request;

class AlipayClass extends \classes\IndexClass
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
        $this->alipay = new AliPay();
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
        if ($member['phone'] != '13668031594') parent::ajax_exception(000, '测试中');
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
        $order_model->pay_type = 20;
        $order_model->save();

        return [
            'body' => $order[$level],
            'subject' => $order[$level],
            'out_trade_no' => $other,
            'total_amount' => 0.01,//$remind,
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
        $param = request()->get();

        //验签
        $result = $this->alipay->notify($param);
        if (!$result) parent::ajax_exception(000, '验证失败');

        list($order_number) = explode('_', $param['out_trade_no']);

        //报单记录
        $order_model = new OrderModel();
        $order_model = $order_model->where('order_number', '=', $order_number)->where('pay_type', '=', 20)->where('pay_status', '=', 10)->find();
        if (is_null($order_model)) parent::ajax_exception(000, '订单信息错误');
        $order_model->pay_status = 20;
        $order_model->save();

        $model = new MemberModel();
        $grades = $model->grades;
        $remind = $order_model->remind;
        $level = $order_model->level_code;
        $after = $order_model->after_key;
        $before = $order_model->before_key;
        $this->other = $order_model->order_number;
        $this->date = date('Y-m-d H:i:s');

        //修改会员信息
        $member = $model->where('id', '=', $order_model->member_id)->find();
        if (is_null($member)) parent::ajax_exception(000, '会员未找到');
        $member->grade = $order_model->after_key;
        $member->save();

        $goods = new GoodsModel();
        $goods = $goods->where('code', '=', $level)->find();

        //初始化会员记录
        $record = new MemberRecordModel();
        $record->member_id = $member->id;
        $record->account = $member->account;
        $record->nickname = $member->nickname;
        $record->remind = 0 - $remind;
        $record->content = '报单『' . $goods->name . '(' . $order_model->level_name . ')』会员等级由『' . $grades[$before] . '』变为『' . $grades[$after] . '』支付余额『' . $remind . '』';
        $record->integral_now = $member->integral;
        $record->integral_all = $member->integral_all;
        $record->remind_now = $member->remind;
        $record->remind_all = $member->remind_all;
        $record->total_now = $member->total;
        $record->total_all = $member->total_all;
        $record->type = 80;
        $record->created_at = $this->date;
        $record->other = $this->other;
        $record->save();

        //判断单价是否相等
        $class = new SystemClass();
        $set = $class->index();
        if ($remind != $set[$level]) parent::ajax_exception(000, '请刷新重试2');

        $referee = self::reward_1($member, $remind, $grades[$after], $set);//直推销售奖
        self::reward_2($referee, $remind, $grades[$after], $set);//间推销售奖
        $referee = self::reward_82($member, $remind, $grades[$after], $set);//津贴奖
        self::reward_83($referee, $remind, $grades[$after], $set);//育成奖
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

    //直推销售奖
    private function reward_1($member, $remind, $level_name, $set)
    {
        //没有上级
        if (empty($member->referee_id)) return null;

        //寻找上级
        $referee = new MemberModel();
        $referee = $referee->where('id', '=', $member->referee_id)->find();
        if (is_null($referee)) return null;

        //尝试升级
        $referee = self::level_up($member, $referee, $set);

        //计算赏金
        switch ($referee->grade) {
            case '1':
                //会员
                $total = number_format(($remind * $set['rewardMemberFirst'] / 100), 2, '.', '');//计算赏金
                break;
            case '2':
                //经销商
                $total = number_format(($remind * $set['rewardBusinessFirst'] / 100), 2, '.', '');//计算赏金
                break;
            case '3':
                //代理
                $total = number_format(($remind * $set['rewardAgentFirst'] / 100), 2, '.', '');//计算赏金
                break;
            case '4':
                //总监
                $total = number_format(($remind * $set['rewardDirectorFirst'] / 100), 2, '.', '');//计算赏金
                break;
            case '5':
                //董事
                $total = number_format(($remind * $set['rewardChairmanFirst'] / 100), 2, '.', '');//计算赏金
                break;
            default:
                //游客或其他等级
                $total = 0;
                break;
        }

        //没有赏金
        if ($total <= 0) return $referee;

        //修改会员信息
        $referee->remind += $total;
        $referee->remind_all += $total;
        $referee->total += $total;
        $referee->total_all += $total;
        $referee->save();

        //初始化会员记录
        $record = new MemberRecordModel();
        $record->member_id = $referee->id;
        $record->account = $referee->account;
        $record->nickname = $referee->nickname;
        $record->total = $total;
        $record->remind = $total;
        $record->content = '直推下级报单，会员等级升为『' . $level_name . '』获得销售奖『' . $total . '』';
        $record->integral_now = $referee->integral;
        $record->integral_all = $referee->integral_all;
        $record->remind_now = $referee->remind;
        $record->remind_all = $referee->remind_all;
        $record->total_now = $referee->total;
        $record->total_all = $referee->total_all;
        $record->type = 81;
        $record->created_at = $this->date;
        $record->other = $this->other;
        $record->save();

        return $referee;
    }

    //间推销售奖
    private function reward_2($member, $remind, $level_name, $set)
    {
        //没有上级
        if (is_null($member) || empty($member->referee_id)) return null;

        //寻找上级
        $referee = new MemberModel();
        $referee = $referee->where('id', '=', $member->referee_id)->find();
        if (is_null($referee)) return null;

        //计算赏金
        switch ($referee->grade) {
            case '1':
                //会员
                $total = number_format(($remind * $set['rewardMemberSecond'] / 100), 2, '.', '');//计算赏金
                break;
            case '2':
                //经销商
                $total = number_format(($remind * $set['rewardBusinessSecond'] / 100), 2, '.', '');//计算赏金
                break;
            case '3':
                //代理
                $total = number_format(($remind * $set['rewardAgentSecond'] / 100), 2, '.', '');//计算赏金
                break;
            case '4':
                //总监
                $total = number_format(($remind * $set['rewardDirectorSecond'] / 100), 2, '.', '');//计算赏金
                break;
            case '5':
                //董事
                $total = number_format(($remind * $set['rewardChairmanSecond'] / 100), 2, '.', '');//计算赏金
                break;
            default:
                //游客或其他等级
                $total = 0;
                break;
        }

        //没有赏金
        if ($total <= 0) return $referee;

        //修改会员信息
        $referee->remind += $total;
        $referee->remind_all += $total;
        $referee->total += $total;
        $referee->total_all += $total;
        $referee->save();

        //初始化会员记录
        $record = new MemberRecordModel();
        $record->member_id = $referee->id;
        $record->account = $referee->account;
        $record->nickname = $referee->nickname;
        $record->total = $total;
        $record->remind = $total;
        $record->content = '间推下级报单，会员等级升为『' . $level_name . '』获得销售奖『' . $total . '』';
        $record->integral_now = $referee->integral;
        $record->integral_all = $referee->integral_all;
        $record->remind_now = $referee->remind;
        $record->remind_all = $referee->remind_all;
        $record->total_now = $referee->total;
        $record->total_all = $referee->total_all;
        $record->type = 81;
        $record->created_at = $this->date;
        $record->other = $this->other;
        $record->save();

        return $referee;
    }

    //津贴奖
    private function reward_82($member, $remind, $level_name, $set)
    {
        //没有上级
        if (empty($member->families)) return null;

        //寻找上级
        $referee = new MemberModel();
        $referee = $referee->whereIn('id', explode(',', $member->families))->whereIn('grade', [4, 5])->order('id', 'desc')->find();
        if (count($referee) <= 0) return null;

        //计算赏金
        $total = number_format(($remind * $set['rewardTotal'] / 100), 2, '.', '');//计算赏金

        //没有赏金
        if ($total <= 0) return $referee;

        //修改会员信息
        $referee->remind += $total;
        $referee->remind_all += $total;
        $referee->total += $total;
        $referee->total_all += $total;
        $referee->save();

        //初始化会员记录
        $record = new MemberRecordModel();
        $record->member_id = $referee->id;
        $record->account = $referee->account;
        $record->nickname = $referee->nickname;
        $record->total = $total;
        $record->remind = $total;
        $record->content = '下级报单，会员等级升为『' . $level_name . '』获得津贴奖『' . $total . '』';
        $record->integral_now = $referee->integral;
        $record->integral_all = $referee->integral_all;
        $record->remind_now = $referee->remind;
        $record->remind_all = $referee->remind_all;
        $record->total_now = $referee->total;
        $record->total_all = $referee->total_all;
        $record->type = 82;
        $record->created_at = $this->date;
        $record->other = $this->other;
        $record->save();

        return $referee;
    }

    //育成奖
    private function reward_83($member, $remind, $level_name, $set)
    {
        //没有上级
        if (is_null($member) || empty($member->families)) return null;

        //寻找上级
        $referee = new MemberModel();
        $referee = $referee->whereIn('id', explode(',', $member->families))->whereIn('grade', [4, 5])->order('id', 'desc')->find();
        if (count($referee) <= 0) return null;

        //计算赏金
        $total = number_format(($remind * $set['rewardTotal'] * $set['rewardDirector'] / 10000), 2, '.', '');//计算赏金

        //没有赏金
        if ($total <= 0) return $referee;

        //修改会员信息
        $referee->remind += $total;
        $referee->remind_all += $total;
        $referee->total += $total;
        $referee->total_all += $total;
        $referee->save();

        //初始化会员记录
        $record = new MemberRecordModel();
        $record->member_id = $referee->id;
        $record->account = $referee->account;
        $record->nickname = $referee->nickname;
        $record->total = $total;
        $record->remind = $total;
        $record->content = '下级报单，会员等级升为『' . $level_name . '』获得育成奖『' . $total . '』';
        $record->integral_now = $referee->integral;
        $record->integral_all = $referee->integral_all;
        $record->remind_now = $referee->remind;
        $record->remind_all = $referee->remind_all;
        $record->total_now = $referee->total;
        $record->total_all = $referee->total_all;
        $record->type = 83;
        $record->created_at = $this->date;
        $record->other = $this->other;
        $record->save();

        return $referee;
    }

    //等级提升
    private function level_up(MemberModel $member, MemberModel $referee, $set)
    {
        //没有升级到代理，不需要晋升
        if ($member->grade != '3') return $referee;

        //判断直推晋升条件是否满足
        $all_3 = new MemberModel();
        $all_3 = $all_3->where('referee_id', '=', $referee->id)->whereIn('grade', [3, 4, 5])->count();
        if ($all_3 < $set['levelDirectorAgent']) return $referee;//不满足直推

        //计算团队业绩
        $all_member = new MemberModel();
        $all_member = $all_member->where('families', 'like', '%' . $referee->id, '%')->column('id');
        if (count($all_member) < 0) return $referee;
        $all_total = new OrderModel();
        $all_total = $all_total->whereIn('member_id', $all_member)->sum('remind');
        if ($all_total < $set['levelDirectorTotal']) return $referee;//团队业绩不足

        //等级变更
        $referee->grade = 4;
        $referee->save();

        //初始化会员记录
        $record = new MemberRecordModel();
        $record->member_id = $referee->id;
        $record->account = $referee->account;
        $record->nickname = $referee->nickname;
        $record->content = '满足晋升条件，直推代理达到『' . $all_3 . '』团队业绩达到『' . $all_total . '』晋升为总监';
        $record->integral_now = $referee->integral;
        $record->integral_all = $referee->integral_all;
        $record->remind_now = $referee->remind;
        $record->remind_all = $referee->remind_all;
        $record->total_now = $referee->total;
        $record->total_all = $referee->total_all;
        $record->type = 90;
        $record->other = $this->other;
        $record->created_at = $this->date;
        $record->save();

        //继续判断上级
        if (empty($referee->referee_id)) return $referee;
        $referee_2 = new MemberModel();
        $referee_2 = $referee_2->where('id', '=', $referee->referee_id)->where('grade', '=', 4)->find();
        if (is_null($referee_2)) return $referee;

        //判断直推晋升条件是否满足
        $all_3 = new MemberModel();
        $all_3 = $all_3->where('referee_id', '=', $referee_2->id)->whereIn('grade', [4, 5])->count();
        if ($all_3 < $set['levelChairmanDirector']) return $referee;//不满足直推

        //计算团队业绩
        $all_member = new MemberModel();
        $all_member = $all_member->where('families', 'like', '%' . $referee_2->id, '%')->column('id');
        if (count($all_member) < 0) return $referee;
        $all_total = new OrderModel();
        $all_total = $all_total->whereIn('member_id', $all_member)->sum('remind');
        if ($all_total < $set['levelChairmanTotal']) return $referee;//团队业绩不足

        //等级变更
        $referee_2->grade = 5;
        $referee_2->save();

        //初始化会员记录
        $record = new MemberRecordModel();
        $record->member_id = $referee->id;
        $record->account = $referee->account;
        $record->nickname = $referee->nickname;
        $record->content = '满足晋升条件，直推总监达到『' . $all_3 . '』团队业绩达到『' . $all_total . '』晋升为董事';
        $record->integral_now = $referee->integral;
        $record->integral_all = $referee->integral_all;
        $record->remind_now = $referee->remind;
        $record->remind_all = $referee->remind_all;
        $record->total_now = $referee->total;
        $record->total_all = $referee->total_all;
        $record->type = 90;
        $record->other = $this->other;
        $record->created_at = $this->date;
        $record->save();

        return $referee;
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
}