<?php

use think\migration\Migrator;
use think\migration\db\Column;

class Exchange extends Migrator
{
    public function up()
    {
        $table = $this->table('exchange');
        $table->setId('id');

        $table->addColumn(Column::string('order_number')->setComment('订单号'));

        //奖品情况
        $table->addColumn(Column::integer('welfare_id')->setComment('奖励id'));
        $table->addColumn(Column::string('welfare_name')->setComment('奖励名称'));
        $table->addColumn(Column::decimal('welfare_total',18)->setComment('消耗收入'));
        $table->addColumn(Column::string('welfare_reward')->setComment('奖励'));

        //会员情况
        $table->addColumn(Column::integer('member_id')->setComment('会员id'));
        $table->addColumn(Column::string('member_account')->setComment('会员账号'));
        $table->addColumn(Column::string('member_phone')->setComment('会员电话'));
        $table->addColumn(Column::string('member_nickname')->setComment('会员昵称'));
        $table->addColumn(Column::timestamp('member_create')->setComment('会员注册时间'));

        //操作情况
        $table->addColumn(Column::char('status', 1)->setDefault(0)->setComment('订单状态，0待处理，1已处理，2已取消'));
        $table->addColumn(Column::integer('change_id')->setNullable()->setComment('操作人id'));
        $table->addColumn(Column::string('change_nickname')->setNullable()->setComment('操作人昵称'));
        $table->addColumn(Column::timestamp('change_date')->setNullable()->setComment('操作人时间'));

        $table->addColumn(Column::timestamp('created_at')->setNullable()->setComment('下单时间'));
        $table->save();
    }

    public function down()
    {
        $this->dropTable('exchange');
    }
}
