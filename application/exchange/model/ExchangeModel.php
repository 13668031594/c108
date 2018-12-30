<?php

namespace app\exchange\model;

use think\Model;

class ExchangeModel extends Model
{
    public $statues = [
        '0' => '待派发',
        '1' => '已派奖',
        '2' => '已取消',
    ];
}
