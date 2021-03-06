<?php
/**
 * Created by PhpStorm.
 * User: yangyang
 * Date: 2018/11/15
 * Time: 下午8:13
 */

namespace classes\index;


use app\exchange\model\ExchangeModel;
use app\goods\model\GoodsModel;
use app\member\model\MemberModel;
use app\member\model\MemberRecordModel;
use app\order\model\OrderModel;
use app\trade\model\TradeModel;
use app\welfare\model\WelfareModel;
use app\withdraw\model\WithdrawModel;
use classes\system\SystemClass;
use classes\vendor\AliPay;
use think\Db;
use think\Request;

class AssetChangeClass extends \classes\IndexClass
{
    public $member;
    public $in_member;
    public $asset;
    public $set;
    public $other;
    public $date;

    public function __construct()
    {
        $this->member = parent::member();

        if ($this->member['status'] == '1') parent::ajax_exception(000, '您的账号被冻结了');

        $this->date = date('Y-m-d H:i:s');
    }

    //转出3连
    public function validator_out()
    {
        $setting = new SystemClass();
        $this->set = $setting = $setting->index();
        $set = $setting['payActBase'];
        $set_times = $setting['payActTimes'];

        $rule = [
            'account' => 'require|min:6|max:20',
            'number' => 'require|integer|between:1,100000000',
            'pass' => 'require|length:1,255',
        ];
        $file = [
            'account' => '转出账号',
            'number' => '转出金额',
            'pass' => '支付密码'
        ];

        $result = parent::validator(input(), $rule, [], $file);
        if (!is_null($result)) parent::ajax_exception(000, $result);

        if (md5(input('pass')) != $this->member['pay_pass'])parent::ajax_exception(000, '支付密码错误');

        $number = input('number');

        $test = $number % $set_times;
        if (($number < $set) || !empty($test)) parent::ajax_exception(000, '转出金额至少为' . $set . '，且为' . $set_times . '的倍数');

        if (input('number') > $this->member['remind']) parent::ajax_exception(000, '余额不足');

        $account = input('account');

        if ($account == $this->member['account']) parent::ajax_exception(000, '不能转给自己');

        $model = new MemberModel();
        $this->in_member = $model->where('account', '=', $account)->find();

        if (is_null($this->in_member)) parent::ajax_exception(000, '转出会员不存在');
        if ($this->in_member->status >= '2') parent::ajax_exception(000, '该会员已被冻结或禁用');
    }

    public function out()
    {
        Db::startTrans();

        $record = [];
        $update = [];

        $time = time();
        $date = date('Y-m-d H:i:s', $time);

        //转出会员
        $member = new MemberModel();//会员模型
        $member = $member->where('id', '=', $this->member['id'])->find();
        $in_member = $this->in_member;

        $number = input('number');//转移资产

        $order = self::new_trade();

        //转出余额
        $update[$member->id]['id'] = $member->id;
        $update[$member->id]['remind'] = $member->remind - $number;

        $records['member_id'] = $member->id;
        $records['account'] = $member->account;
        $records['nickname'] = $member->nickname;
        $records['integral_now'] = $member->integral;
        $records['integral_all'] = $member->integral_all;
        $records['total_now'] = $member->total;
        $records['total_all'] = $member->total_all;
        $records['remind'] = 0 - $number;
        $records['remind_now'] = $member->remind - $number;
        $records['remind_all'] = $member->remind_all;
        $records['type'] = 40;
        $records['content'] = '转出『余额』' . $number . ',转入账号：' . $in_member->account;
        $records['other'] = $order;
        $records['created_at'] = $date;

        $record[] = $records;

        //转入未激活资产
        $update[$in_member->id]['id'] = $in_member->id;
        $update[$in_member->id]['remind'] = $in_member->remind + $number;

        $records['member_id'] = $in_member->id;
        $records['account'] = $in_member->account;
        $records['nickname'] = $in_member->nickname;
        $records['integral_now'] = $in_member->integral;
        $records['integral_all'] = $in_member->integral_all;
        $records['total_now'] = $in_member->total;
        $records['total_all'] = $in_member->total_all;
        $records['remind'] = $number;
        $records['remind_now'] = $in_member->remind + $number;
        $records['remind_all'] = $in_member->remind_all + $number;
        $records['type'] = 50;
        $records['content'] = '转入『余额』' . $number . ',转出账号：' . $member->account;
        $records['other'] = $order;
        $records['created_at'] = $date;
        $record[] = $records;

        $model = new MemberRecordModel();
        $model->insertAll($record);

        $member_model = new MemberModel();
        $member_model->saveAll($update);

        //交易记录
        $model = new TradeModel();
        $add = [
            'order_number' => $order,
            'remind' => $number,
            'buyer_id' => $in_member->id,
            'buyer_account' => $in_member->account,
            'buyer_nickname' => $in_member->nickname,
            'buyer_phone' => $in_member->phone,
            'seller_id' => $member->id,
            'seller_account' => $member->account,
            'seller_nickname' => $member->nickname,
            'seller_phone' => $member->phone,
            'created_at' => $date
        ];
        $model->insert($add);

        Db::commit();
    }

    //新的订单号
    private function new_trade()
    {
        $order = 't' . time() . rand(100, 999);

        $model = new TradeModel();
        $test = $model->where('order_number', '=', $order)->find();

        if ($test) return self::new_trade();

        return $order;
    }

    //报单验证
    public function validator_exchange(Request $request)
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

        //判断余额是否足够
        if ($member['remind'] < $remind) parent::ajax_exception(000, '余额不足');

        //初始化会员模型
        $model = new MemberModel();

        //会员等级数组
        $grades = $model->grades;

        //产品名称
        $order = $class->level;

        $this->other = self::new_order();

        $this->date = date('Y-m-d H:i:s');

        //报单记录
        $order_model = new OrderModel();
        $order_model->order_number = $this->other;
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
        $order_model->man = $request->post('man');
        $order_model->phone = $request->post('phone');
        $order_model->address = $request->post('address');
        $order_model->created_at = $this->date;
        $order_model->save();

        //修改会员信息
        $before = $member['grade'];
        $member = $model->where('id', '=', $member['id'])->find();
        $member->remind -= $remind;
        $member->grade = $after;
        $member->save();

        $goods = new GoodsModel();
        $goods = $goods->where('code', '=', $level)->find();

        //初始化会员记录
        $record = new MemberRecordModel();
        $record->member_id = $member->id;
        $record->account = $member->account;
        $record->nickname = $member->nickname;
        $record->remind = 0 - $remind;
        $record->content = '报单『' . $goods->name . '(' . $order[$level] . ')』会员等级由『' . $grades[$before] . '』变为『' . $grades[$after] . '』支付余额『' . $remind . '』';
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

        $referee = self::reward_1($member, $remind, $grades[$after], $set);//直推销售奖
        self::reward_2($referee, $remind, $grades[$after], $set);//间推销售奖
        $referee = self::reward_82($member, $remind, $grades[$after], $set);//津贴奖
        self::reward_83($referee, $remind, $grades[$after], $set);//育成奖
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
        $record->other = $this->other;
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
        $record->other = $this->other;
        $record->save();

        return $referee;
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

    //提现验证
    public function validator_withdraw(Request $request)
    {
        //验证条件
        $rule = [
            'number|提现金额' => 'require|integer|between:1,100000000',
            'base|提现基数' => 'require',
            'times|提现倍数' => 'require',
            'poundage|手续费比例' => 'require',
            'after|提现后' => 'require',
            'before|提现前' => 'require',
            'div|手续费' => 'require',
            'withdraw|实际获得' => 'require',
            'pay|支付密码' => 'require',
        ];

        //验证
        $result = parent::validator($request->post(), $rule);
        //有错误报告则报错
        if (!is_null($result)) parent::ajax_exception(000, $result);

        //获取会员资料
        $member = parent::member();

        //必须完善收款信息
        if (empty($member['bank_no']) || empty($member['bank_man']) || empty($member['bank_name'])) parent::ajax_exception(000, '请先完善收款信息');

        //所有参数
        $data = $request->post();

        //验证交易密码
        if (md5($data['pay']) != $member['pay_pass']) parent::ajax_exception(000, '支付密码输入错误');

        //验证交易前金额
        if ($data['before'] != $member['remind']) parent::ajax_exception(000, '请刷新重试（before）');

        //获取配置文件
        $sys = new SystemClass();
        $sys = $sys->index();

        //验证提现配置3件套
        if ($sys['withdrawBase'] != $data['base']) parent::ajax_exception(000, '请刷新重试（base）');
        if ($sys['withdrawTimes'] != $data['times']) parent::ajax_exception(000, '请刷新重试（times）');
        if ($sys['withdrawPoundage'] != $data['poundage']) parent::ajax_exception(000, '请刷新重试（poundage）');

        //提现金额
        $number = number_format($data['number'], 2, '.', '');
        if ($number != $data['number']) parent::ajax_exception(000, '提现金额至多精确到2位小数');

        //验证手续费
        $div = number_format(($number * $data['poundage'] / 100), 2, '.', '');
        if ($div != $data['div']) parent::ajax_exception(000, '请刷新重试（div）');

        //验证提现后余额
        $after = number_format(($member['remind'] - $number), 2, '.', '');
        if ($after != $data['after']) parent::ajax_exception(000, '请刷新重试（after）');

        //验证实际获得金额
        $withdraw = number_format(($number - $div), 2, '.', '');
        if ($withdraw != $data['withdraw']) parent::ajax_exception(000, '请刷新重试（withdraw）');

        //验证倍数信息
        if ($number < $data['base']) parent::ajax_exception(000, '提现金额必须大于：' . $data['base']);
        if (!empty(($number % $data['times']))) parent::ajax_exception(000, '提现金额必须是『' . $data['times'] . '』的倍数');

        Db::startTrans();

        //修改会员信息
        $model = new MemberModel();
        $model = $model->where('id', '=', $member['id'])->find();
        $model->remind -= $number;
        $model->save();

        //建立订单
        $order = new WithdrawModel();
        $order->order_number = self::new_withdraw();
        $order->total = $withdraw;
        $order->remind = $number;
        $order->integral = $div;
        $order->poundage = $data['poundage'];
        $order->member_id = $member['id'];
        $order->member_account = $member['account'];
        $order->member_phone = $member['phone'];
        $order->member_nickname = $member['nickname'];
        $order->member_create = $member['created_at'];
        $order->bank_id = $member['bank_id'] ? $member['bank_id'] : '0';
        $order->bank_no = $member['bank_no'];
        $order->bank_name = $member['bank_name'];
        $order->bank_man = $member['bank_man'];
        $order->created_at = date('Y-m-d H:i:s');
        $order->save();

        //初始化会员记录
        $record = new MemberRecordModel();
        $record->member_id = $model->id;
        $record->account = $model->account;
        $record->nickname = $model->nickname;
        $record->remind = 0 - $number;
        $record->content = '提现扣除余额『' . $number . '』';
        $record->integral_now = $model->integral;
        $record->integral_all = $model->integral_all;
        $record->remind_now = $model->remind;
        $record->remind_all = $model->remind_all;
        $record->total_now = $model->total;
        $record->total_all = $model->total_all;
        $record->type = 60;
        $record->created_at = $this->date;
        $record->other = $order->order_number;
        $record->save();

        Db::commit();
    }

    private function new_withdraw()
    {
        $order = 'w' . time() . rand(100, 999);

        $model = new WithdrawModel();
        $test = $model->where('order_number', '=', $order)->find();

        if ($test) return self::new_order();

        return $order;
    }

    public function validator_welfare(Request $request)
    {
        //验证条件
        $rule = [
            'id|奖品' => 'require|integer',
            'total|消耗积分' => 'require|number|between:1,100000000',

            'pay_pass|支付密码' => 'require',
        ];

        //验证
        $result = parent::validator($request->post(), $rule);
        //有错误报告则报错
        if (!is_null($result)) parent::ajax_exception(000, $result);

        //获取会员资料
        $member = parent::member();

        //所有参数
        $data = $request->post();

        //验证交易密码
        if (md5($data['pay_pass']) != $member['pay_pass']) parent::ajax_exception(000, '支付密码输入错误');

        $welfare = new WelfareModel();
        $welfare = $welfare->where('id', '=', $data['id'])->find();
        if (is_null($welfare)) parent::ajax_exception(000, '请刷新重试(we)');
        if ($welfare->total != $data['total']) parent::ajax_exception(000, '请刷新重试(we)');

        Db::startTrans();

        //修改会员信息
        $model = new MemberModel();
        $model = $model->where('id', '=', $member['id'])->find();
        $model->total -= $data['total'];

        if ($model->total < 0) parent::ajax_exception(000, '累计收入不足');
        $model->save();

        //建立订单
        $order = new ExchangeModel();
        $order->order_number = self::new_exchange();
        $order->welfare_id = $welfare->id;
        $order->welfare_name = $welfare->name;
        $order->welfare_total = $welfare->total;
        $order->welfare_reward = $welfare->reward;
        $order->member_id = $member['id'];
        $order->member_account = $member['account'];
        $order->member_phone = $member['phone'];
        $order->member_nickname = $member['nickname'];
        $order->member_create = $member['created_at'];
        $order->created_at = date('Y-m-d H:i:s');
        $order->save();

        //初始化会员记录
        $record = new MemberRecordModel();
        $record->member_id = $model->id;
        $record->account = $model->account;
        $record->nickname = $model->nickname;
        $record->total = 0 - $welfare->total;
        $record->content = '兑换福利奖『' . $welfare->name . '』扣除累计收入『' . $welfare->total . '』';
        $record->integral_now = $model->integral;
        $record->integral_all = $model->integral_all;
        $record->remind_now = $model->remind;
        $record->remind_all = $model->remind_all;
        $record->total_now = $model->total;
        $record->total_all = $model->total_all;
        $record->type = 70;
        $record->created_at = $this->date;
        $record->other = $order->order_number;
        $record->save();

        Db::commit();
    }

    private function new_exchange()
    {
        $order = 'e' . time() . rand(100, 999);

        $model = new ExchangeModel();
        $test = $model->where('order_number', '=', $order)->find();

        if ($test) return self::new_order();

        return $order;
    }
}