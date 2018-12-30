<?php

use think\migration\Migrator;
use think\migration\db\Column;

class Order extends Migrator
{
    public function up()
    {
        $table = $this->table('order');
        $table->setId('id');

        //升级信息
        $table->addColumn(Column::string('order_number')->setComment('订单号'));
        $table->addColumn(Column::decimal('remind',18)->setComment('使用余额'));
        $table->addColumn(Column::integer('before_key')->setComment('升级前身份key'));
        $table->addColumn(Column::string('before_value')->setComment('升级前身份value'));
        $table->addColumn(Column::integer('after_key')->setComment('升级后身份key'));
        $table->addColumn(Column::string('after_value')->setComment('升级后身份value'));
        $table->addColumn(Column::string('level_name')->setComment('产品名'));

        //会员信息
        $table->addColumn(Column::integer('member_id')->setComment('会员id'));
        $table->addColumn(Column::string('member_account')->setComment('会员账号'));
        $table->addColumn(Column::string('member_phone')->setComment('会员电话'));
        $table->addColumn(Column::string('member_nickname')->setComment('会员昵称'));

        $table->addColumn(Column::timestamp('created_at')->setNullable()->setComment('创建时间'));
        $table->save();
    }

    public function down()
    {
        $this->dropTable('order');
    }
}
