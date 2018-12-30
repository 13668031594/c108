<?php

use think\migration\Migrator;
use think\migration\db\Column;

class WelfareContent extends Migrator
{
    public function up()
    {
        $table = $this->table('welfare_content');

        $table->setId('id');

        $table->addColumn(Column::integer('pid')->setComment('福利奖id'));
        $table->addColumn(Column::text('content')->setNullable()->setComment('正文'));

        $table->addColumn(Column::timestamp('created_at')->setNullable()->setComment('添加时间'));
        $table->addColumn(Column::timestamp('updated_at')->setNullable()->setComment('更新时间'));

        $table->save();
    }

    public function down()
    {
        $this->dropTable('welfare_content');
    }
}
