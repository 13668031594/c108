<?php
/**
 * Created by PhpStorm.
 * User: yangyang
 * Date: 2018/11/24
 * Time: 下午9:13
 */

namespace classes\order;

use app\order\model\OrderModel;
use classes\AdminClass;
use think\Request;

class OrderClass extends AdminClass
{
    public $model;

    public function __construct()
    {
        $this->model = new OrderModel();
    }

    public function index(Request $request)
    {
        $where = [];

        $startTime = $request->get('startTime');
        $endTime = $request->get('endTime');
        $account = $request->get('account');

        if (!empty($startTime)) {
            $where['created_at'] = ['>=', $startTime];
        }
        if (!empty($endTime)) {
            $where['created_at'] = ['<', $endTime];
        }
        if (!empty($account)) {
            $where['member_account|member_phone'] = ['like', '%' . $account . '%'];
        }
        $where['pay_status'] = ['=', 20];

        return parent::page($this->model, ['where' => $where]);
    }
}