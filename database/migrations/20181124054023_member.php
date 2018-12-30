<?php

use think\migration\Migrator;
use think\migration\db\Column;

class Member extends Migrator
{
    public function up()
    {
        $table = $this->table('member');
        $table->setId('id');

        //基础
        $table->addColumn(Column::char('phone', 11)->setComment('手机号'));
        $table->addColumn(Column::string('account')->setComment('账号'));
        $table->addColumn(Column::string('nickname')->setComment('昵称'));
        $table->addColumn(Column::string('password')->setComment('密码'));
        $table->addColumn(Column::string('pay_pass')->setComment('支付密码'));
        $table->addColumn(Column::char('created_type', 1)->setDefault(0)->setComment('创建方式，0前台，1后台'));
        $table->addColumn(Column::char('status', 1)->setDefault(0)->setComment('状态，0正常，1冻结，2禁用'));
        $table->addColumn(Column::char('grade',1)->setDefault(0)->setComment('会员等级'));
        $table->addColumn(Column::char('lock',1)->setDefault(0)->setComment('等级锁'));

        //收款信息
        $table->addColumn(Column::string('bank_id')->setNullable()->setComment('收款银行id'));
        $table->addColumn(Column::string('bank_name')->setNullable()->setComment('收款银行名称'));
        $table->addColumn(Column::string('bank_man')->setNullable()->setComment('收款人'));
        $table->addColumn(Column::string('bank_no')->setNullable()->setComment('收款卡号'));

        //上级
        $table->addColumn(Column::integer('level')->setDefault(1)->setComment('所在层级'));
        $table->addColumn(Column::string('families')->setDefault(0)->setComment('上级缓存'));
        $table->addColumn(Column::integer('referee_id')->setDefault(0)->setComment('上级id'));
        $table->addColumn(Column::string('referee_nickname')->setDefault('无')->setComment('上级昵称'));
        $table->addColumn(Column::string('referee_account')->setDefault('无')->setComment('上级账号'));
        $table->addColumn(Column::char('referee_phone',11)->setDefault('无')->setComment('上级手机号'));

        //钱包
        $table->addColumn(Column::decimal('remind', 18)->setDefault(0)->setComment('余额'));
        $table->addColumn(Column::decimal('remind_all', 18)->setDefault(0)->setComment('余额流水'));
        $table->addColumn(Column::decimal('integral', 18)->setDefault(0)->setComment('积分'));
        $table->addColumn(Column::decimal('integral_all', 18)->setDefault(0)->setComment('积分流水'));
        $table->addColumn(Column::decimal('total', 18)->setDefault(0)->setComment('消费'));
        $table->addColumn(Column::decimal('total_all', 18)->setDefault(0)->setComment('消费流水'));

        //登录字段
        $table->addColumn(Column::integer('login_times')->setDefault(0)->setComment('登录次数'));
        $table->addColumn(Column::string('login_ip')->setNullable()->setComment('登录ip'));
        $table->addColumn(Column::string('login_ass')->setNullable()->setComment('登录验证'));
        $table->addColumn(Column::timestamp('login_time')->setNullable()->setComment('登录时间'));

        //时间
        $table->addColumn(Column::timestamp('created_at')->setNullable()->setComment('创建时间'));
        $table->addColumn(Column::timestamp('updated_at')->setNullable()->setComment('更新时间'));
        $table->save();
    }

    public function down()
    {
        $this->dropTable('member');
    }
}
