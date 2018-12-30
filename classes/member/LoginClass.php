<?php
/**
 * Created by PhpStorm.
 * User: yangyang
 * Date: 2018/11/15
 * Time: 下午1:00
 */

namespace classes\member;

use app\member\model\MemberModel;
use app\member\model\MemberRecordModel;
use app\member\model\SmsModel;
use classes\IndexClass;
use classes\vendor\SmsClass;

class LoginClass extends IndexClass
{
    /**
     * 返回记住的账号
     *
     * @return mixed
     */
    public function account()
    {
        return session('member_account');
    }

    public function loged()
    {
        //尝试获取session中的member信息
        $member = session('member');

        //验证session中的信息格式与过期时间
        if (is_null($member) || !is_array($member) || !isset($member['id']) || !isset($member['login_ass']) || !isset($member['time']) || ($member['time'] < time())) return true;

        $login_ass = $member['login_ass'];

        //赋值会员id
        $member_id = $member['id'];

        //初始化会员模型
        $member = new MemberModel();

        //尝试获取会员资料
        $member = $member->where('id', '=', $member_id)->find();

        //没有获取到会员资料，跳转至登录页面
        if (is_null($member)) return true;

        //获取资料数组，去其他数据
        $member = $member->getData();

        //获取当前ip
        $login_ip = $_SERVER["REMOTE_ADDR"];

        //登录ip不同，证明在其他地方登录，跳转至登录页面
        if ($login_ip != $member['login_ip']) return true;

        if ($login_ass != $member['login_ass']) return true;

        //验证成功，会员已经登录，应直接跳转到首页
        return false;
    }

    /**
     * 登录字段验证
     */
    public function validator_login()
    {
        //验证条件
        $rule = [
            'account' => 'require|max:20|min:6',
            'password' => 'require|max:20|min:6',
        ];

        $file = [
            'account' => '账号',
            'password' => '密码',
            'captcha' => '验证码',
        ];

        //验证
        $result = parent::validator(input(), $rule, [], $file);

        //有错误报告则报错
        if (!is_null($result)) parent::redirect_exception('/index/login', $result);
    }

    /**
     * 登录方法，登录成功返回member模型
     *
     * @return \app\member\model\MemberModel|array|false|\PDOStatement|string|\think\Model
     */
    public function login()
    {
        //初始化模型
        $member = new MemberModel();

        //尝试获取管理员信息
        $member = $member->where('account', '=', input('account'))
            ->where('password', '=', md5(input('password')))
            ->find();

        //获取失败，账密错误
        if (is_null($member)) parent::redirect_exception('/index/login', '账号或密码错误');

        //返回管理员信息
        return $member;
    }

    /**
     * 记住账号功能
     *
     * @param $account
     */
    public function save_account($account)
    {
        if (input('recommend') == '1') session('member_account', $account);
    }

    /**
     * 修改登录信息
     *
     * @param MemberModel $member
     * @return mixed|string
     */
    public function refresh_member(MemberModel $member)
    {
        $member->login_times += 1;
        $member->login_time = date('Y-m-d H:i:s');
        $member->login_ip = $_SERVER["REMOTE_ADDR"];
        $member->login_ass = md5(time() . rand(100, 999));
        $member->save();

        return $member->login_ass;
    }

    /**
     * 重置登录时间
     *
     * @param $member_id
     * @param $login_ass
     */
    public function refresh_login_member($member_id, $login_ass)
    {
        $member = [
            'id' => $member_id,
            'time' => time() + config('young.index_login_time'),
            'login_ass' => $login_ass,
        ];

        session('member', $member);
    }

    /**
     * 注销
     */
    public function logout()
    {
        session('member', null);
    }

    /**
     * 注册字段验证
     */
    public function validator_reg()
    {
        //验证条件
        $rule = [
            'account' => 'require|max:20|min:6|unique:member,account',
            'pass' => 'require|max:20|min:6',
            'again' => 'require|max:20|min:6',
            'referee_account' => 'min:6|max:20',
            'code' => 'require|length:5'
        ];

        $file = [
            'account' => '账号',
            'pass' => '密码',
            'again' => '验证码',
            'referee_account' => '推广账号',
            'code' => '验证码',
        ];

        //验证
        $result = parent::validator(input(), $rule, [], $file);

        //有错误报告则报错
        if (!is_null($result)) parent::ajax_exception(000, $result);

//        self::test_phone_code();//手机验证码

        $pass = input('pass');
        $again = input('again');

        if ($pass != $again) parent::ajax_exception(000, '确认密码输入错误');

        //初始化模型
        $member = new MemberModel();

        //尝试获取管理员信息
        $member = $member->where('account', '=', input('account'))->find();

        //获取失败，账密错误
        if (!is_null($member)) parent::ajax_exception(000, '账号被占用');
    }

    /**
     * 注册方法，登录成功返回member模型
     *
     * @return \app\member\model\MemberModel|array|false|\PDOStatement|string|\think\Model
     */
    public function reg()
    {
        $member = new MemberModel();

        $member = self::referee_add($member);

        $phone = input('account');
        $nickname = substr($phone, 0, 3) . '****' . substr($phone, 7);
        $pass = input('pass');

        $member->created_at = date('Y-m-d H:i:s');
        $member->phone = $phone;
        $member->account = $phone;
        $member->nickname = $nickname;
        $member->password = md5($pass);
        $member->pay_pass = md5($pass);
        $member->save();

        //返回管理员信息
        return $member;
    }

    private function referee_add(MemberModel $member)
    {
        $p_account = input('referee_account');
        if (empty($p_account)) return $member;

        $test = new MemberModel();
        $referee = $test->where('account', '=', input('referee_account'))->find();
        if (is_null($referee)) parent::ajax_exception(000, '推广人不存在');

        $referee = $referee->getData();

        $families = empty($referee['families']) ? $referee['id'] : ($referee['families'] . ',' . $referee['id']);

        $member->families = $families;//上级缓存
        $member->referee_id = $referee['id'];//上级id
        $member->referee_account = $referee['account'];//上级账号
        $member->referee_nickname = $referee['nickname'];//上级昵称
        $member->level = $referee['level'] + 1;//自身层级


        return $member;
    }

    /**
     * 发送验证码前验证
     *
     * @param $phone
     * @param $time
     */
    public function validator_sms_register($phone, $time)
    {
        $term = [
            'phone' => 'require|length:11|unique:member,account',//联系电话，必填
        ];

        $errors = [
            'phone.require' => '请输入联系电话',
            'phone.length' => '请输入11位的联系电话',
            'phone.unique' => '该电话号码已经注册过账号，请更换联系电话或填写账号信息',
        ];

        //参数判断
        $result = parent::validator(['phone' => $phone], $term, $errors);
        if (!is_null($result)) parent::ajax_exception(000, $result);

        //验证上次发送验证码时间
        self::validator_sms_time($phone, $time);
    }

    /**
     * 发送验证码前验证
     *
     * @param $phone
     * @param $time
     */
    public function validator_sms_reset($phone, $time)
    {
        $term = [
            'phone' => 'require|length:11',//联系电话，必填
        ];

        $errors = [
            'phone.require' => '请输入联系电话',
            'phone.length' => '请输入11位的联系电话',
            'phone.unique' => '账号不存在',
        ];

        //参数判断
        $result = parent::validator(['phone' => $phone], $term, $errors);
        if (!is_null($result)) parent::ajax_exception(000, $result);

        $test = new MemberModel();
        $test = $test->where('account|phone', '=', $phone)->find();
        if (is_null($test)) parent::ajax_exception(000, '账号不存在');

        //验证上次发送验证码时间
        self::validator_sms_time($phone, $time);
    }

    /**
     * 验证上次发送验证码时间
     *
     * @param $phone
     * @param $time
     */
    public function validator_sms_time($phone, $time)
    {
        //获取该电话号码最新的验证码
        $test = new SmsModel();
        $test_code = $test->where('phone', '=', $phone)->order('created_at', 'desc')->find();

        //没有找到数据
        if (!is_null($test_code)) {

            //比较是否超时
            if ($time < $test_code->end) {

                $end = $test_code->end - $time;

                parent::ajax_exception('001', $end);
            }
        }
    }

    //删除所有超时验证码
    public function delete_sms($time)
    {
        $model = new SmsModel();
        $model->where('end', '<', $time)->delete();
    }

    //发送短信
    public function send_sms($phone, $time, $type = 'reg')
    {
        //初始化短信类
//        $class = new SmsClass();

        //生成验证码
        $code = rand(10000, 99999);

        //发送短信
//        $result = $class->sendSms($phone, $code, $templateCode);
        $content = '【肽雅丽】';
        switch ($type) {
            case 'reset':
                $content .= '您正在使用手机找回密码';
                break;
            default:
                $content .= '您正在使用手机注册';

                break;
        }
        $content .= '，验证码为：' . $code . '，2分钟内有效!';

        $result = self::send($phone, $content);

        //判断回执
        if ($result != '0') parent::ajax_exception(000, '发送失败' . $result);

        //生成结束时间
        $end = $time + 120;

        //添加到数据库
        $model = new SmsModel();
        $model->phone = $phone;
        $model->end = $end;
        $model->code = $code;
        $model->created_at = date('Y-m-d H:i:s');
        $model->save();

        return $end;
    }

    //验证短信
    public function validator_phone()
    {
        $phone = input('account');
        $code = input('code');

        //获取该电话号码最新的验证码
        $test = new SmsModel();
        $test_code = $test->where('phone', '=', $phone)->order('created_at', 'desc')->find();

        //没有找到数据
        if (is_null($test_code)) parent::ajax_exception(000, '验证码输入错误');

        //当前时间戳
        $now_time = time();

        //比较是否超时
        if ($now_time > $test_code->end) parent::ajax_exception(000, '验证码已经失效,请重新获取');

        //比较验证码是否正确
        if ($code != $test_code->code) parent::ajax_exception(000, '验证码输入错误');
    }

    public function validator_reset()
    {
        //验证条件
        $rule = [
            'account' => 'require|max:20|min:6',
            'pass' => 'require|max:20|min:6',
            'again' => 'require|max:20|min:6',
            'code' => 'require|length:5'
        ];

        $file = [
            'account' => '账号',
            'pass' => '密码',
            'again' => '验证码',
            'code' => '验证码',
        ];

        //验证
        $result = parent::validator(input(), $rule, [], $file);

        //有错误报告则报错
        if (!is_null($result)) parent::ajax_exception(000, $result);

        self::test_phone_code();//手机验证码

        $pass = input('pass');
        $again = input('again');

        if ($pass != $again) parent::ajax_exception(000, '确认密码输入错误');
    }

    /**
     * 注册方法，登录成功返回member模型
     *
     * @return \app\member\model\MemberModel|array|false|\PDOStatement|string|\think\Model
     */
    public function reset()
    {
        //初始化模型
        $member = new MemberModel();

        //尝试获取管理员信息
        $member = $member->where('account', '=', input('account'))->find();

        //获取失败，账密错误
        if (is_null($member)) parent::ajax_exception(000, '账号不存在');

        $member->password = md5(input('pass'));
        $member->save();

        //返回管理员信息
        return $member;
    }

    public function test_phone_code()
    {
        $code = input('code');
        $phone = input('account');

        $test = new SmsModel();
        $test = $test->where('phone', '=', $phone)->order('created_at', 'desc')->find();

        if (is_null($test)) parent::ajax_exception(000, '请重新获取验证码');

        if ($test->end < time()) parent::ajax_exception(000, '验证码已过期');

        if ($test->code != $code) parent::ajax_exception(000, '验证码错误');

    }

    /**
     * @param $phone //电话
     * @param $content //内容
     * @return mixed|null
     */
    public function send($phone, $content)
    {
        $statusStr = array(
            "0" => "短信发送成功",
            "-1" => "参数不全",
            "-2" => "服务器空间不支持,请确认支持curl或者fsocket，联系您的空间商解决或者更换空间！",
            "30" => "密码错误",
            "40" => "账号不存在",
            "41" => "余额不足",
            "42" => "帐户已过期",
            "43" => "IP地址限制",
            "50" => "内容含有敏感词"
        );
        $smsapi = "http://api.smsbao.com/";
        $user = "ahu66"; //短信平台帐号
        $pass = md5("dxh102090"); //短信平台密码
        $sendurl = $smsapi . "sms?u=" . $user . "&p=" . $pass . "&m=" . $phone . "&c=" . urlencode($content);
        $result = file_get_contents($sendurl);
        return $result;
    }
}