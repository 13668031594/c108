<?php

namespace app\member\model;

use app\order\model\OrderModel;
use think\Model;

/**
 * Class MemberModel
 * @package app\member\model
 *
 * @property $id
 * @property $phone
 * @property $account
 * @property $nickname
 * @property $password
 * @property $pay_pass
 * @property $created_type
 * @property $status
 * @property $grade
 * @property $lock
 * @property $bank_id
 * @property $bank_name
 * @property $bank_man
 * @property $bank_no
 * @property $level
 * @property $families
 * @property $referee_id
 * @property $referee_nickname
 * @property $referee_account
 * @property $referee_phone
 * @property $remind
 * @property $remind_all
 * @property $integral
 * @property $integral_all
 * @property $total
 * @property $total_all
 * @property $login_times
 * @property $login_ip
 * @property $login_ass
 * @property $login_time
 * @property $created_at
 * @property $updated_at
 */
class MemberModel extends Model
{
    public $grades = [
        '0' => '游客',
        '1' => '会员',
        '2' => '经销商',
        '3' => '代理',
        '4' => '总监',
        '5' => '董事',
    ];

    public $statuses = [
        '0' => '正常',
        '1' => '冻结',
        '2' => '禁用',
    ];

    public $locks = [
        '0' => '正常',
        '1' => '保护',
        '2' => '锁定',
    ];

    public $created_types = [
        '0' => '前台',
        '1' => '后台',
    ];

    //业绩
    public function total_all($member_id)
    {
        //计算团队业绩
        $all_member = self::where('families', 'like', '%' . $member_id, '%')->column('id');
        if (count($all_member) < 0) return 0;

        $all_total = new OrderModel();
        $all_total = $all_total->whereIn('member_id', $all_member)->sum('remind');
        return $all_total;
    }
}
