<?php
/**
 * Created by PhpStorm.
 * User: yangyang
 * Date: 2018/11/24
 * Time: 下午9:13
 */

namespace classes\trade;

use app\trade\model\TradeModel;
use classes\AdminClass;
use think\Request;

class TradeClass extends AdminClass
{
    public $model;

    public function __construct()
    {
        $this->model = new TradeModel();
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
            switch ($request->get('type')) {
                case '1':
                    $where['buyer_account|buyer_phone'] = ['like', '%' . $account . '%'];
                    break;
                case '2':
                    $where['seller_account|seller_phone'] = ['like', '%' . $account . '%'];
                    break;
                default:
                    $where['buyer_account|buyer_phone|seller_account|seller_phone'] = ['like', '%' . $account . '%'];
                    break;
            }
        }

        return parent::page($this->model, ['where' => $where]);
    }
}