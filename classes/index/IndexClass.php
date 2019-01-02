<?php
/**
 * Created by PhpStorm.
 * User: yangyang
 * Date: 2018/11/15
 * Time: 下午3:07
 */

namespace classes\index;

use app\adv\model\AdvModel;
use app\bank\model\BankModel;
use app\exchange\model\ExchangeModel;
use app\goods\model\GoodsContentModel;
use app\goods\model\GoodsImagesModel;
use app\goods\model\GoodsModel;
use app\member\model\MemberModel;
use app\notice\model\NoticeModel;
use app\welfare\model\WelfareContentModel;
use app\welfare\model\WelfareImagesModel;
use app\welfare\model\WelfareModel;
use app\withdraw\model\WithdrawModel;
use classes\goods\GoodsClass;
use classes\member\MemberClass;
use classes\system\SystemClass;
use classes\welfare\WelfareClass;
use think\Image;

class IndexClass extends \classes\IndexClass
{
    public $member;

    public function __construct()
    {
        $this->member = parent::member();
    }

    public function view($view, $data = [])
    {
        $data['errors'] = null;
        $data['success'] = null;

        //获取提示
        $errors = session('errors');
        $success = session('success');

        //初始化errors视图变量
        if (!is_null($errors) && is_string($errors)) $data['errors'] = $errors;

        //初始化success视图变量
        if (!is_null($success) && is_string($success)) $data['success'] = $success;

        //删除提示
        session('errors', null);
        session('success', null);

        //渲染视图
        return view($view, $data);
    }

    public function header2($top = '众筹', $array = [])
    {
        //获取公告
        $model = new NoticeModel();
        $result = $model->order('created_at', 'desc')->column('title');
        if (count($result) > 1) {
            $first = array_shift($result);
            array_unshift($result, $first);
            $result[] = $first;
        }
        $array['notice'] = $result;

        //获取广告
        $model = new AdvModel();
        $result = $model->order('sort', 'asc')->where('show', '=', 'on')->column('id,location,describe');
        foreach ($result as &$v) {
            if (is_null($v['location'])) $v['location'] = config('young.image_not_found');
        }
        $array['adv'] = $result;

        $array['member'] = $this->member;

        $array['top'] = $top;

        return $array;
    }

    //首页编辑器
    public function text()
    {
        $class = new SystemClass();

        return $class->first();
    }

    //公告
    public function notice()
    {
        $model = new NoticeModel();
        $result = parent::page($model);

        return $result;
    }

    //资产记录
    public function record()
    {
        //获取公告
        $model = new MemberClass();

        $request = request();
        $request->get(['id' => $this->member['id']]);

        $result = $model->record($request);

        $record_array = $model->record->types;

        foreach ($result['message'] as &$v) $v['type'] = $record_array[$v['type']];

        return $result;
    }

    //转入二维码
    public function make_qr($dir, $url)
    {
        if (!is_dir($dir)) mkdir($dir);
        //不带LOGO
        vendor('phpqrcode.phpqrcode');
        //生成二维码图片
        $object = new \QrCode();
        $url = 'http://' . config('young.url') . $url;//网址或者是文本内容
        $level = 3;
        $size = '6';
        $ad = $dir . '/' . $this->member['id'] . '.jpg';
        $errorCorrectionLevel = intval($level);//容错级别
        $matrixPointSize = intval($size);//生成图片大小
        $object->png($url, $ad, $errorCorrectionLevel, $matrixPointSize, 2);

        $a = $dir . '/' . $this->member['id'] . 'a.jpg';
            Image::open('04.jpg')
            ->text($this->member['id'], 'FangzhengZhengkai.TTF', 22,'#000000',[230,92])
            ->water($ad,[155,290])
            ->save($a);

        return '/' . $a;
    }

    //转入二维码
    public function make_qr_in($dir, $url)
    {
        if (!is_dir($dir)) mkdir($dir);
        //不带LOGO
        vendor('phpqrcode.phpqrcode');
        //生成二维码图片
        $object = new \QrCode();
        $url = 'http://' . config('young.url') . $url;//网址或者是文本内容
        $level = 3;
        $size = 17;
        $ad = $dir . '/' . $this->member['id'] . '.jpg';
        $errorCorrectionLevel = intval($level);//容错级别
        $matrixPointSize = intval($size);//生成图片大小
        $object->png($url, $ad, $errorCorrectionLevel, $matrixPointSize, 2);

        $a = $dir . '/' . $this->member['id'] . 'a.jpg';
            Image::open('05.jpg')
//            ->text($this->member['id'], 'FangzhengZhengkai.TTF', 22,'#000000',[20,20])
            ->water($ad,[190,450])
            ->save($a);

        return '/' . $a;
    }

    //转出二维码
    public function out_man($id)
    {
        $model = new \app\member\model\MemberModel();

        $member = $model->where('id', '=', $id)->find();

        if (is_null($member)) return null;

        return $member->getData();
    }

    //修改昵称
    public function nickname()
    {
        $rule = [
            'nickname|昵称' => 'require|min:1|max:48',
            'bank_id|银行' => 'length:0,255',
            'bank_man|收款人' => 'length:0,255',
            'bank_no|卡号' => 'length:0,255',
        ];

        /*$file = [
            'nickname' => '昵称',
            ''
        ];*/

        $result = parent::validator(input(), $rule, [], []);
        if (!is_null($result)) parent::ajax_exception(000, $result);


        $member['id'] = $this->member['id'];
        $member['bank_id'] = null;
        $member['bank_name'] = null;
        $member['bank_no'] = input('bank_no');
        $member['bank_man'] = input('bank_man');
        $member['nickname'] = input('nickname');
        if (!empty(input('bank_id'))) {

            $bank = new BankModel();
            $bank = $bank->where('id', '=', input('bank_id'))->find();
            if (!is_null($bank)) {

                $member['bank_id'] = $bank->id;
                $member['bank_name'] = $bank->name;
            }
        }
//        parent::ajax_exception(000, 'a'.$member['bank_id']);
        $model = new \app\member\model\MemberModel();
        $model->saveAll([$member]);

    }

    //修改登录密码
    public function password()
    {
        $rule = [
            'old' => 'require|min:6|max:20',
            'new' => 'require|min:6|max:20',
            'again' => 'require|min:6|max:20'
        ];

        $file = [
            'old' => '旧密码',
            'new' => '新密码',
            'again' => '确认密码',
        ];

        $result = parent::validator(input(), $rule, [], $file);
        if (!is_null($result)) parent::ajax_exception(000, $result);

        $old = input('old');
        $new = input('new');
        $again = input('again');
        if ($new != $again) parent::ajax_exception(000, '确认密码输入错误');
        if (md5($old) != $this->member['password']) parent::ajax_exception(000, '旧密码输入错误');

        $member['id'] = $this->member['id'];
        $member['password'] = md5($new);
        $model = new \app\member\model\MemberModel();
        $model->saveAll([$member]);
    }

    //修改支付密码
    public function pay_pass()
    {
        $rule = [
            'old' => 'require|min:6|max:20',
            'new' => 'require|min:6|max:20',
            'again' => 'require|min:6|max:20'
        ];

        $file = [
            'old' => '旧密码',
            'new' => '新密码',
            'again' => '确认密码',
        ];

        $result = parent::validator(input(), $rule, [], $file);
        if (!is_null($result)) parent::ajax_exception(000, $result);

        $old = input('old');
        $new = input('new');
        $again = input('again');
        if ($new != $again) parent::ajax_exception(000, '确认密码输入错误');
        if (md5($old) != $this->member['pay_pass']) parent::ajax_exception(000, '旧密码输入错误');

        $member['id'] = $this->member['id'];
        $member['pay_pass'] = md5($new);
        $model = new \app\member\model\MemberModel();
        $model->saveAll([$member]);
    }

    public function team($member_id)
    {
        $model = new \app\member\model\MemberModel();

        $result = $model->where('referee_id', '=', $member_id)->column('id,phone,nickname,grade');

        $member = [];
        $i = 0;

        $grades = $model->grades;

        foreach ($result as $v) {

            $member[$i]['id'] = $v['id'];
            $member[$i]['phone'] = $v['phone'];
            $member[$i]['nickname'] = $v['nickname'];
            $member[$i]['grade'] = $grades[$v['grade']];

            $i++;
        }

        return [
            'member' => $member,
            'grades' => $grades
        ];
    }

    public function team_total($member_id)
    {
        $model = new \app\member\model\MemberModel();
        $total = $model->where('families', 'like', '%' . $member_id . '%')->sum('total');

        $model = new \app\member\model\MemberModel();
        $number = $model->where('families', 'like', '%' . $member_id . '%')->count();

        return [
            'total' => $total,
            'number' => $number
        ];
    }

    //可以报单产品
    public function up_grades()
    {
        //会员模型
        $member = parent::member();

        //配置类
        $class = new SystemClass();
        //获取可以报单的产品
        $gradess = $class->up_grade($member['grade']);
        if (count($gradess) > 0) {

            $goods = new GoodsModel();
            $goods = $goods->whereIn('code', array_keys($gradess))->order('sort', 'asc')->column('*');
            foreach ($goods as &$v) if (is_null($v['location']) || !file_exists(substr($v['location'], 1))) $v['location'] = config('young.image_not_found');
            $result['gradess'] = $goods;
        } else {

            $result['gradess'] = [];
        }
        $result['grades'] = $gradess;


        //获取等级
        $grades = new MemberModel();
        $grades = $grades->grades;
        $member['grade_name'] = $grades[$member['grade']];

        //获取会员业绩
        $model = new MemberModel();
        $member['total_all'] = $model->total_all($member['id']);

        //当等级足够，计算直推数
        switch ($member['grade']) {
            case '3':
                $where = [
                    'grade' => ['=', '3'],
                    'referee_id' => ['=', $member['id']],
                ];
                $member['child'] = $model->where($where)->count();
                break;
            case '4':
                $where = [
                    'grade' => ['=', '4'],
                    'referee_id' => ['=', $member['id']],
                ];
                $member['child'] = $model->where($where)->count();
                break;
            default:
                $member['child'] = null;
                break;
        }

        $result['members'] = $member;
        $result['member_grades'] = $grades;

        return $result;
    }

    //银行列表
    public function bank()
    {
        $model = new BankModel();

        $result = $model->order('sort', 'asc')->column('*');

        return $result;
    }

    public function goods_info()
    {
        $id = input('id');
        if (is_null($id)) exit('参数错误');

        //产品
        $model = new GoodsModel();
        $model = $model->where('id', '=', $id)->find();
        if (is_null($model)) exit('产品不存在');

        $model->location = is_null($model->location) ? config('young.image_not_found') : $model->location;

        $model = $model->getData();

        //图片
        $images = new GoodsImagesModel();
        $images = $images->where('pid', '=', $id)->where('id', '<>', $model['cover'])->column('*');
        $image = [];
        $i = 1;
        foreach ($images as $k => $v) {

            $image[$i]['id'] = $v['id'];
            $image[$i]['location'] = is_null($v['location']) ? config('young.image_not_found') : $v['location'];

            $i++;
        }
        ksort($image);

        //正文
        $content = new GoodsContentModel();
        $content = $content->where('pid', '=', $id)->find();

        //集合
        return [
            'self' => $model,
            'images' => $image,
            'content' => $content ? $content->content : ''
        ];
    }

    public function withdraw_list()
    {
        $model = new WithdrawModel();

        $status = $model->statues;

        $member = parent::member();

        $other = [
            'where' => [
                'member_id' => ['=', $member['id']]
            ]
        ];

        $list = parent::page($model, $other);

        foreach ($list['message'] as &$v) $v['status'] = $status[$v['status']];

        return $list;
    }

    public function welfare()
    {
        $model = new WelfareClass();

        return $model->index();
    }

    public function welfare_info()
    {
        $id = input('id');
        if (is_null($id)) exit('参数错误');

        //产品
        $model = new WelfareModel();
        $model = $model->where('id', '=', $id)->find();
        if (is_null($model)) exit('奖品不存在');

        $model->location = is_null($model->location) ? config('young.image_not_found') : $model->location;

        $model = $model->getData();

        //图片
        $images = new WelfareImagesModel();
        $images = $images->where('pid', '=', $id)->where('id', '<>', $model['cover'])->column('*');
        $image = [];
        $i = 1;
        foreach ($images as $k => $v) {

            $image[$i]['id'] = $v['id'];
            $image[$i]['location'] = is_null($v['location']) ? config('young.image_not_found') : $v['location'];

            $i++;
        }
        ksort($image);

        //正文
        $content = new WelfareContentModel();
        $content = $content->where('pid', '=', $id)->find();

        //集合
        return [
            'self' => $model,
            'images' => $image,
            'content' => $content ? $content->content : ''
        ];
    }

    public function welfare_list()
    {
        $model = new ExchangeModel();

        $status = $model->statues;

        $member = parent::member();

        $other = [
            'where' => [
                'member_id' => ['=', $member['id']]
            ]
        ];

        $list = parent::page($model, $other);
        foreach ($list['message'] as &$v) $v['status'] = $status[$v['status']];

        return $list;
    }
}