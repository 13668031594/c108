<?php

use think\migration\Seeder;

class Config extends Seeder
{
    private $date;

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
        $this->date = date('Y-m-d H:i:s');
        \think\Db::query("ALTER TABLE young_master AUTO_INCREMENT = 10000");
        \think\Db::query("ALTER TABLE young_member AUTO_INCREMENT = 10000");

        self::master();//初始管理员
        self::welfare();//初始奖励
        self::adv();//初始广告
        self::goods();//初始化产品
    }

    private function master()
    {
        $master = new \app\master\model\MasterModel();

        $test = $master->find();

        if (is_null($test)) {

            $insert = [
                'id' => '1',
                'nickname' => '超级管理员',
                'account' => 'admins',
                'password' => md5('asdasd123'),
                'created_at' => $this->date,
            ];

            $master->insert($insert);
        }
    }

    public function welfare()
    {
        $model = new \app\welfare\model\WelfareModel();

        $test = $model->count();

        if ($test <= 0) {

            $insert = [
                [
                    'name' => '一等奖',
                    'sort' => '10',
                    'total' => '2600000',
                    'reward' => '别墅一套',
                    'created_at' => $this->date,
                ],
                [
                    'name' => '二等奖',
                    'sort' => '20',
                    'total' => '500000',
                    'reward' => '10万轿车一台',
                    'created_at' => $this->date,
                ],
                [
                    'name' => '三等奖',
                    'sort' => '30',
                    'total' => '100000',
                    'reward' => '海外旅游一次',
                    'created_at' => $this->date,
                ],
                [
                    'name' => '四等奖',
                    'sort' => '40',
                    'total' => '100000',
                    'reward' => '海外旅游一次',
                    'created_at' => $this->date,
                ],
                [
                    'name' => '五等奖',
                    'sort' => '50',
                    'total' => '50000',
                    'reward' => '奖励5000元产品',
                    'created_at' => $this->date,
                ],
                [
                    'name' => '六等奖',
                    'sort' => '60',
                    'total' => '30000',
                    'reward' => '奖励2000元产品',
                    'created_at' => $this->date,
                ],
            ];

            $model->insertAll($insert);
        }
    }

    /**
     * Run Method.
     *
     * Write your database seeder using this method.
     *
     * More information on writing seeders is available here:
     * http://docs.phinx.org/en/latest/seeding.html
     */
    public function adv()
    {
        $model = new \app\adv\model\AdvModel();

        $count = $model->count();

        $insert = [];

        $date = date('Y-m-d H:i:s');

        for ($i = ($count + 1); $i < 6; $i++) {

            $insert[$i]['title'] = '广告' . $i;
            $insert[$i]['sort'] = $i;
            $insert[$i]['describe'] = '描述' . $i;
            $insert[$i]['created_at'] = $date;
        }

        if (count($insert) > 0) $model->insertAll($insert);
    }

    public function goods()
    {
        $model = new \app\goods\model\GoodsModel();
        $test = $model->count();

        if ($test == 0){

            $sys = new \classes\system\SystemClass();
            $level = $sys->level;
            $config = $sys->index();

            $insert = [];

            foreach ($level as $k => $v){

                $i = count($insert);

                $insert[$i]['name'] = '产品'.($i + 1);
                $insert[$i]['code'] = $k;
                $insert[$i]['describe'] = $v;
                $insert[$i]['amount'] = $config[$k];
                $insert[$i]['sort'] = $i + 1;
                $insert[$i]['created_at'] = $this->date;

            }

            if (count($insert) > 0)$model->insertAll($insert);
        }
    }
}