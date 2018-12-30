<?php
/**
 * Created by PhpStorm.
 * User: yangyang
 * Date: 2018/11/24
 * Time: 下午9:13
 */

namespace classes\exchange;


use app\exchange\model\ExchangeModel;
use app\member\model\MemberModel;
use app\member\model\MemberRecordModel;
use classes\AdminClass;
use think\Db;
use think\Request;

class ExchangeClass extends AdminClass
{
    public $model;

    public function __construct()
    {
        $this->model = new ExchangeModel();
    }

    public function index(Request $request)
    {
        $where = [];

        $startTime = $request->get('startTime');
        $endTime = $request->get('endTime');
        $type = $request->get('type');
        $value = $request->get('value');
        $status = $request->get('status');

        if (!empty($startTime)) {
            $where['created_at'] = [ '>=', $startTime];
        }
        if (!empty($endTime)) {
            $where['created_at'] = [ '<', $endTime];
        }
        if (!empty($value)) {
            switch ($type){
                case '2':
                    $where['order_number'] = [ 'like', '%' . $value . '%'];
                    break;
                default:
                    $where['member_account|member_phone'] = [ 'like', '%' . $value . '%'];
                    break;
            }
        }
        if (!empty($status)) {
            $where['status'] = [ '=', ($status - 1)];
        }

        return parent::page($this->model, ['where' => $where]);
    }

    public function status(Request $request)
    {
        Db::startTrans();

        $id = $request->get('id');

        //订单获取
        $order = $this->model->where('id', '=', $id)->find();

        //获取成功
        if (is_null($order)) parent::ajax_exception(0, '订单不存在');

        //未锁定
        if ($order->status != '0') parent::ajax_exception(0, '订单已锁定');

        //新状态获取
        $status = input('value');

        //合法的状态码
        $array = [1, 2];

        //状态码合法
        if (!in_array($status, $array)) parent::ajax_exception(0, '状态错误');

        //获取管理员
        $master = parent::master();

        //修改订单状态
        $order->status = $status;
        $order->change_id = $master['id'];
        $order->change_nickname = $master['nickname'];
        $order->change_date = date('Y-m-d H:i:s');
        $order->save();

        //状态取消，发放积分
        if ($status == '2') {

            self::totalAdd($order->getData());
        }

        Db::commit();
    }

    private function totalAdd($order)
    {
        //会员寻找与家谱卷添加
        $member = new MemberModel();
        $member = $member->where('id', '=', $order['member_id'])->find();
        if (is_null($member)) return;
        $member->total += $order['welfare_total'];
        $member->save();

        //会员变更记录
        $record = new MemberRecordModel();
        $record->member_id = $member->id;
        $record->account = $member->account;
        $record->nickname = $member->nickname;
        $record->total = $order['welfare_total'];
        $record->total_now = $member->total;
        $record->total_all = $member->total_all;
        $record->remind = 0;
        $record->remind_now = $member->remind;
        $record->remind_all = $member->remind_all;
        $record->integral = 0;
        $record->integral_now = $member->integral;
        $record->integral_all = $member->integral_all;
        $record->type = '70';
        $record->content = '管理员取消了您的兑换订单(订单号：' . $order['order_number'] . ')，返还『累计收入』：' . $order['welfare_total'];
        $record->created_at = date('Y-m-d H:i:s');
        $record->save();
    }
}