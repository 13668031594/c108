<?php

namespace app\member\model;

use think\Model;

class MemberRecordModel extends Model
{
    public $types = [
        '10' => '后台调整',
        '40' => '余额转出',
        '50' => '余额转入',
        '60' => '提现订单',
        '70' => '兑奖订单',
        '80' => '报单',
        '81' => '销售奖',
        '82' => '津贴奖',
        '83' => '育成奖',
        '90' => '晋升'
    ];
}
