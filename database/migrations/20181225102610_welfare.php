<?php

use think\migration\Migrator;
use think\migration\db\Column;

class Welfare extends Migrator
{
    public function up()
    {
        $table = $this->table('welfare');
        $table->setId('id');
        $table->addColumn(Column::string('name', 255)->setComment('奖励名称'));
        $table->addColumn(Column::integer('sort')->setComment('排序'));
        $table->addColumn(Column::decimal('total',18)->setComment('消耗收入'));
        $table->addColumn(Column::string('reward',255)->setComment('奖励'));
        $table->addColumn(Column::timestamp('created_at')->setNullable()->setComment('创建时间'));
        $table->addColumn(Column::timestamp('updated_at')->setNullable()->setComment('更新时间'));
        $table->save();
    }

    public function down()
    {
        $this->dropTable('welfare');
    }
}
