<?php

namespace app\withdraw\model;

use think\Model;

class WithdrawModel extends Model
{
    public $statues = [
        '0' => '待处理',
        '1' => '已处理',
        '2' => '已取消',
    ];
}
