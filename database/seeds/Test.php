<?php

use think\migration\Seeder;

class Test extends Seeder
{
    /**
     * Run Method.
     *
     * Write your database seeder using this method.
     *
     * More information on writing seeders is available here:
     * http://docs.phinx.org/en/latest/seeding.html
     */
    public function run()
    {
//        self::withdraw();
//        self::trade();
//        self::exchange();
//        self::order();
    }

    private function withdraw()
    {
        $member = new \app\member\model\MemberModel();

        $member = $member->column('id,nickname,created_at,account,phone,bank_name,bank_id,bank_no,bank_man');

        if (count($member) <= 0) return;

        $insert = [];
        $i = 1;
        $date = date('Y-m-d H:i:s');

        foreach ($member as $v) {

            $insert[$i]['order_number'] = 'ABC0000' . $i;
            $insert[$i]['total'] = 900;
            $insert[$i]['remind'] = 1000;
            $insert[$i]['integral'] = 100;
            $insert[$i]['member_id'] = $v['id'];
            $insert[$i]['member_nickname'] = $v['nickname'];
            $insert[$i]['member_create'] = $v['created_at'];
            $insert[$i]['member_account'] = $v['account'];
            $insert[$i]['member_phone'] = $v['phone'];
            $insert[$i]['bank_no'] = $v['bank_no'];
            $insert[$i]['bank_id'] = $v['bank_id'];
            $insert[$i]['bank_name'] = $v['bank_name'];
            $insert[$i]['bank_man'] = $v['bank_man'];
            $insert[$i]['created_at'] = $date;

            $i++;
        }

        if (count($insert) > 0) {
            $model = new \app\withdraw\model\WithdrawModel();
            $model->insertAll($insert);
        }
    }

    private function trade()
    {
        $one = new \app\member\model\MemberModel();
        $one = $one->order('id', 'asc')->find();
        $two = new \app\member\model\MemberModel();
        $two = $two->order('id', 'desc')->find();

        $insert = [
            [
                'order_number' => 'trade0',
                'remind' => '100',
                'buyer_id' => $one->id,
                'buyer_account' => $one->account,
                'buyer_nickname' => $one->nickname,
                'buyer_phone' => $one->phone,
                'seller_id' => $two->id,
                'seller_account' => $two->account,
                'seller_nickname' => $two->nickname,
                'seller_phone' => $two->phone,
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'order_number' => 'trade1',
                'remind' => '100',
                'buyer_id' => $one->id,
                'buyer_account' => $one->account,
                'buyer_nickname' => $one->nickname,
                'buyer_phone' => $one->phone,
                'seller_id' => $two->id,
                'seller_account' => $two->account,
                'seller_nickname' => $two->nickname,
                'seller_phone' => $two->phone,
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'order_number' => 'trade2',
                'remind' => '100',
                'buyer_id' => $one->id,
                'buyer_account' => $one->account,
                'buyer_nickname' => $one->nickname,
                'buyer_phone' => $one->phone,
                'seller_id' => $two->id,
                'seller_account' => $two->account,
                'seller_nickname' => $two->nickname,
                'seller_phone' => $two->phone,
                'created_at' => date('Y-m-d H:i:s')
            ],
        ];

        $model = new \app\trade\model\TradeModel();
        $model->insertAll($insert);
    }

    private function exchange()
    {
        $member = new \app\member\model\MemberModel();
        $member = $member->all();

        $welfare = new \app\welfare\model\WelfareModel();
        $welfare = $welfare->all();

        $insert = [];
        foreach ($welfare as $v) {

            foreach ($member as $va) {

                $in['order_number'] = 'cccc' . count($insert);
                $in['member_id'] = $va->id;
                $in['member_account'] = $va->account;
                $in['member_phone'] = $va->phone;
                $in['member_nickname'] = $va->nickname;
                $in['member_create'] = $va->created_at;
                $in['welfare_id'] = $v->id;
                $in['welfare_name'] = $v->name;
                $in['welfare_total'] = $v->total;
                $in['welfare_reward'] = $v->reward;
                $in['created_at'] = date('Y-m-d H:i:s');

                $insert[] = $in;
            }
        }

        if (count($insert) > 0) {

            $exchange = new \app\exchange\model\ExchangeModel();
            $exchange->insertAll($insert);
        }
    }

    private function order()
    {
        $member = new \app\member\model\MemberModel();
        $member = $member->all();

        $grades = new \app\member\model\MemberModel();
        $grades = $grades->grades;

        $welfare = new \classes\system\SystemClass();
        $config = $welfare->index();

        $insert = [];
        foreach ($welfare->level as $k => $v) {

            foreach ($member as $va) {

                $in['order_number'] = 'order' . count($insert);
                $in['remind'] = $config[$k];
                $in['before_key'] = $va->grade;
                $in['before_value'] = $grades[$va->grade];
                $in['after_key'] = $welfare->get_grade($k,$va->grade);
                $in['after_value'] = $grades[$in['after_key']];
                $in['level_name'] = $v;
                $in['member_id'] = $va->id;
                $in['member_account'] = $va->account;
                $in['member_phone'] = $va->phone;
                $in['member_nickname'] = $va->nickname;
                $in['created_at'] = date('Y-m-d H:i:s');

                $insert[] = $in;
            }
        }

        if (count($insert) > 0) {

            $exchange = new \app\order\model\OrderModel();
            $exchange->insertAll($insert);
        }
    }
}