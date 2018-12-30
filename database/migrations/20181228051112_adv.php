<?php

use think\migration\Migrator;
use think\migration\db\Column;

class Adv extends Migrator
{
    public function up()
    {
        $table = $this->table('adv');
        $table->setId('id');
        $table->addColumn(Column::string('title')->setComment('标题'));
        $table->addColumn(Column::string('describe')->setComment('描述'));
        $table->addColumn(Column::integer('sort')->setComment('排序'));
        $table->addColumn(Column::integer('image')->setNullable()->setComment('图片id'));
        $table->addColumn(Column::string('location')->setNullable()->setComment('图片路径'));
        $table->addColumn(Column::char('show', 3)->setDefault('on')->setComment('是否显示'));
        $table->addColumn(Column::timestamp('created_at')->setNullable()->setComment('创建时间'));
        $table->save();
    }

    public function down()
    {
        $this->dropTable('adv');
    }
}
