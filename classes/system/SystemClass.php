<?php
/**
 * Created by PhpStorm.
 * User: yangyang
 * Date: 2018/11/21
 * Time: 下午5:30
 */

namespace classes\system;

use app\goods\model\GoodsModel;
use classes\AdminClass;
use classes\vendor\StorageClass;
use think\Request;

class SystemClass extends AdminClass
{
    public $storage;
    public $dir = 'logo';
    public $level = [
        'levelMember' => '会员激活',
        'levelBusiness' => '经销商激活',
        'levelAgent' => '代理激活',
        'levelMemberBusiness' => '会员升经销商',
        'levelMemberAgent' => '会员升代理',
        'levelBusinessAgent' => '经销商升代理',
    ];


    public function __construct()
    {
        $this->storage = new StorageClass('sysSetting.txt');
        if (!is_dir($this->dir)) mkdir($this->dir);
    }

    public function index()
    {
        //读取设定文件
        $set = $this->storage->get();

        //获取默认配置
        $result = self::defaults();

        //设定文件存在，修改返回配置
        if (!is_array($set)) {

            //格式化配置信息
            $set = json_decode($set, true);

            //循环设定数据
            foreach ($result as $k => &$v) {

                //设定文件中有的设定，修改之
                if (isset($set[$k])) $v = $set[$k];
            }
        }

        //返回设定文件
        return $result;
    }

    //保存配置文件
    public function save()
    {
        //获取提交的参数
        $set = input();

        //获取原始配置
        $result = self::defaults();

        //循环修改
        foreach ($result as $k => &$v) {

            //设定文件中有的设定，修改之
            if (isset($set[$k])) {

                $v = $set[$k];
            }
        }

        //保存到文件
        $this->storage->save(json_encode($result));

        //修改产品列表中的金额
        self::goods_update($result);

        return $result;
    }

    //验证
    public function save_validator()
    {
        $rule = [
            'webName|网站名称' => 'require|min:1|max:100',
            'webTitle|网站title' => 'require|min:1|max:100',
            'webKeyword|关键字' => 'require|min:1|max:100',
            'webDesc|网站描述' => 'require|min:1|max:100',
            'webSwitch|网站开关' => 'require',
            'webCloseReason|关闭原因' => 'requireIf:webSwitch,off',
            'webCopyright|版权信息' => 'require|max:10000',

            'levelMember|会员激活' => 'require|number|between:0,100000000',
            'levelBusiness|经销商激活' => 'require|number|between:0,100000000',
            'levelAgent|代理激活' => 'require|number|between:0,100000000',
            'levelMemberBusiness|会员升经销商' => 'require|number|between:0,100000000',
            'levelMemberAgent|会员升代理' => 'require|number|between:0,100000000',
            'levelBusinessAgent|经销商升代理' => 'require|number|between:0,100000000',
            'levelDirectorAgent|总监直推' => 'require|integer|between:0,100000000',
            'levelDirectorTotal|总监业绩' => 'require|number|between:0,100000000',
            'levelChairmanDirector|董事直推' => 'require|integer|between:0,100000000',
            'levelChairmanTotal|董事业绩' => 'require|number|between:0,100000000',

            'rewardMemberFirst|会员直推奖励' => 'require|number|between:0,100',
            'rewardMemberSecond|会员间推奖励' => 'require|number|between:0,100',
            'rewardBusinessFirst|经销商直推奖励' => 'require|number|between:0,100',
            'rewardBusinessSecond|经销商间推奖励' => 'require|number|between:0,100',
            'rewardAgentFirst|代理直推奖励' => 'require|number|between:0,100',
            'rewardAgentSecond|代理间推奖励' => 'require|number|between:0,100',
            'rewardDirectorFirst|总监直推奖励' => 'require|number|between:0,100',
            'rewardDirectorSecond|总监间推奖励' => 'require|number|between:0,100',
            'rewardChairmanFirst|董事直推奖励' => 'require|number|between:0,100',
            'rewardChairmanSecond|董事间推奖励' => 'require|number|between:0,100',
            'rewardTotal|津贴奖' => 'require|number|between:0,100',
            'rewardDirector|育成奖' => 'require|number|between:0,100',
            'rewardChairman|董事分红' => 'require|number|between:0,100',

            'withdrawBase|提现基数' => 'require|number|between:0.01,100000000',
            'withdrawTimes|提现倍数' => 'require|number|between:0.01,100000000',
            'withdrawPoundage|手续费比例' => 'require|number|between:0,100',

            'payActBase|转账基数' => 'require|number|between:0.01,100000000',
            'payActTimes|转账倍数' => 'require|number|between:0.01,100000000',
        ];

        $result = parent::validator(input(), $rule);

        if (!is_null($result)) parent::ajax_exception(0, $result);
    }

    //重置，删除配置文件
    public function reset()
    {
        $this->storage->unlink_files();
    }

    //默认数据
    private function defaults()
    {
        return [
            'webName' => '肽雅丽',
            'webTitle' => '肽雅丽',
            'webKeyword' => '肽雅丽',
            'webDesc' => '肽雅丽',
            'webSwitch' => 'on',
            'webCloseReason' => '网站维护中',
            'webCopyright' => '版权',
            'levelMember' => '399',
            'levelBusiness' => '1680',
            'levelAgent' => '6980',
            'levelMemberBusiness' => '1280',
            'levelMemberAgent' => '6581',
            'levelBusinessAgent' => '5300',
            'levelDirectorAgent' => '8',
            'levelDirectorTotal' => '500000',
            'levelChairmanDirector' => '5',
            'levelChairmanTotal' => '5000000',
            'rewardMemberFirst' => '25',
            'rewardMemberSecond' => '6',
            'rewardBusinessFirst' => '30',
            'rewardBusinessSecond' => '8',
            'rewardAgentFirst' => '40',
            'rewardAgentSecond' => '10',
            'rewardDirectorFirst' => '40',
            'rewardDirectorSecond' => '10',
            'rewardChairmanFirst' => '40',
            'rewardChairmanSecond' => '10',
            'rewardTotal' => '6',
            'rewardDirector' => '10',
            'rewardChairman' => '1',
            'withdrawBase' => '100',
            'withdrawTimes' => '10',
            'withdrawPoundage' => '10',
            'payActBase' => '10',
            'payActTimes' => '10',
        ];
    }

    //上传新的logo
    public function image(Request $request)
    {
        // 获取表单上传文件 例如上传了001.jpg
        $file = $request->file('images');

        $location = 'logo_' . time();

        // 移动到框架应用根目录/public/uploads/ 目录下
        $info = $file->validate(['size' => (1024 * 1024), 'ext' => 'jpg,png,gif,jpeg,bmp'])->move($this->dir, $location);

        // 上传失败获取错误信息
        if (!$info) parent::ajax_exception(000, $file->getError());

        $location = '/' . $this->dir . '/' . $info->getSaveName();

        return [
            'image' => $location,
        ];
    }

    //删除未使用的logo
    public function image_delete($set)
    {
        if (!is_dir($this->dir)) return;//不是文件夹

        $files = scandir($this->dir);//读取文件

        //循环文件
        foreach ($files as $v) {

            if ($v == '.' || $v == '..') continue;//过滤

            $file = $this->dir . '/' . $v;//文件路径

            if (('/' . $file) != $set['logo']) unlink($file);//删除未使用logo
        }
    }

    //升级时，升级后的等级获取
    public function get_grade($level, $before = '0')
    {
        switch ($level) {
            case 'levelMember':
                if ($before >= '1') $result = false;
                else $result = '1';
                break;
            case 'levelBusiness':
                if ($before >= '2') $result = false;
                else $result = '2';
                break;
            case 'levelAgent':
                if ($before >= '3') $result = false;
                else $result = '3';
                break;
            case 'levelMemberBusiness':
                if ($before != '1') $result = false;
                else $result = '2';
                break;
            case 'levelMemberAgent':
                if ($before != '1') $result = false;
                else $result = '3';
                break;
            case 'levelBusinessAgent':
                if ($before != '2') $result = false;
                else $result = '3';
                break;
            default:
                $result = false;
                break;
        }

        return $result;
    }

    public function up_grade($before = '0')
    {
        switch ($before) {
            case '0':
                $result = [
                    'levelMember' => '会员激活',
                    'levelBusiness' => '经销商激活',
                    'levelAgent' => '代理激活',
                ];
                break;
            case '1':
                $result = [
                    'levelMemberBusiness' => '会员升经销商',
                    'levelMemberAgent' => '会员升代理',
                ];
                break;
            case '2':
                $result = [
                    'levelBusinessAgent' => '经销商升代理',
                ];
                break;
            case '3':
                $result = [];
                break;
            default:
                $result = [];
                break;
        }

        return $result;
    }

    public function goods_update($result)
    {
        foreach ($this->level as $k => $v){

            if (!isset($result[$k]))continue;

            $model = new GoodsModel();
            $model = $model->where(['code' => $k])->find();
            if (is_null($model))continue;
            $model->amount = $result[$k];
            $model->save();
        }
    }
}