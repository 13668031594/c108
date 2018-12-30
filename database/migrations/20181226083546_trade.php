<?php

use think\migration\Migrator;
use think\migration\db\Column;

class Trade extends Migrator
{
    public function up()
    {
        $table = $this->table('trade');
        $table->setId('id');
        $table->addColumn(Column::string('order_number')->setComment('订单号'));
        $table->addColumn(Column::decimal('remind',18)->setComment('交易余额'));
        $table->addColumn(Column::integer('buyer_id')->setComment('购买人id'));
        $table->addColumn(Column::string('buyer_account')->setComment('购买人账号'));
        $table->addColumn(Column::string('buyer_phone')->setComment('购买人电话'));
        $table->addColumn(Column::string('buyer_nickname')->setComment('购买人昵称'));
        $table->addColumn(Column::integer('seller_id')->setComment('出售人id'));
        $table->addColumn(Column::string('seller_account')->setComment('出售人账号'));
        $table->addColumn(Column::string('seller_phone')->setComment('出售人电话'));
        $table->addColumn(Column::string('seller_nickname')->setComment('出售人昵称'));
        $table->addColumn(Column::timestamp('created_at')->setNullable()->setComment('创建时间'));
        $table->save();
    }

    public function down()
    {
        $this->dropTable('trade');
    }
}
