<?php

use think\migration\Migrator;
use think\migration\db\Column;

class OrderPay extends Migrator
{
    public function up()
    {
        $table = $this->table('order_pay');
        $table->setId('id');

        //会员字段
        $table->addColumn(Column::integer('member_id')->setComment('会员id'));
        $table->addColumn(Column::string('account')->setComment('账号'));
        $table->addColumn(Column::string('nickname')->setComment('昵称'));

        $table->addColumn(Column::string('type')->setComment('支付类型'));

        //订单字段
        $table->addColumn(Column::string('order_no')->setComment('订单号'));
        $table->addColumn(Column::decimal('total', 18, 2)->setComment('订单金额'));
        $table->addColumn(Column::char('status', 2)->setComment('订单状态'));

        $table->addColumn(Column::timestamp('created_at')->setNullable()->setComment('创建时间'));
        $table->addColumn(Column::timestamp('updated_at')->setNullable()->setComment('更新时间'));
        $table->save();
    }

    public function down()
    {
        $this->dropTable('order_pay');
    }
}
