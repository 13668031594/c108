<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

use think\Route;

/**
 * 后台路由组
 */
Route::get('admin/', 'master/Login/getIndex');//后台首页
Route::get('admin', 'master/Login/getIndex');//后台首页
Route::get('admin/login', 'master/Login/getLogin');//后台登录页面
Route::post('admin/login', 'master/Login/postLogin');//后台登录方法
Route::get('admin/logout', 'master/Login/getLogout');//注销登录
Route::controller('admin/master', 'master/Master');//管理员
Route::controller('admin/system', 'system/System');//系统设置
Route::controller('admin/member','member/Member');//会员操作
Route::controller('admin/bank','bank/Bank');//银行管理
Route::controller('admin/withdraw','withdraw/Withdraw');//提现管理
Route::controller('admin/welfare','welfare/Welfare');//福利管理
Route::controller('admin/trade','trade/Trade');//交易列表
Route::controller('admin/exchange','exchange/Exchange');//兑换订单
Route::controller('admin/order','order/Order');//升级订单
Route::controller('admin/notice','notice/Notice');//公告列表
Route::controller('admin/adv','adv/Adv');//广告列表
Route::controller('admin/goods','goods/Goods');//商品列表
Route::controller('admin/bill','bill/Bill');//统计
/**
 * 后台路由组结束
 */

/**
 * 前台路由开始
 */
//登录
Route::get('index/login', 'member/Login/getLogin');
Route::post('index/login', 'member/Login/postLogin');
Route::get('index/logout', 'member/Login/logout');
Route::get('index/reg', 'member/Login/reg');
Route::post('index/reg', 'member/Login/register');
Route::get('index/reset', 'member/Login/res');
Route::post('index/reset', 'member/Login/reset');
Route::get('index/reg-sms/:phone', 'member/Login/sms_reg');//注册短信发送
Route::get('index/reset-sms/:phone', 'member/Login/sms_reset');//注册短信发送

//首页
Route::get('/index', 'member/Login/index');//首页
Route::get('/', 'index/Index/index');//首页
Route::get('index/family', 'index/Index/family');//家谱
Route::get('index/memorial', 'index/Index/memorial');//纪念堂
Route::get('index/shared', 'index/Index/shared');//家族共享
Route::get('index/information', 'index/Index/information');//咨讯中心
Route::get('index/information-table', 'index/Index/information_table');//咨讯中心-翻页
Route::get('index/information-hy', 'index/Index/information_hy');//文章
Route::get('index/information-hy-table', 'index/Index/information_hy_table');//文章-翻页
Route::get('index/information-info/:id', 'index/Index/information_info');//文章详情
Route::get('index/crowd', 'index/Index/crowd');//众筹
Route::post('index/crowd', 'index/Recharge/save');//众筹-统一下单
Route::get('index/crowd-info/:id', 'index/Recharge/info');//支付轮询
Route::get('index/crowd-out/:id', 'index/Recharge/out');//支付轮询
Route::get('index/financial', 'index/Index/financial');//财务
Route::get('index/financial-table', 'index/Index/financial_table');//财务-翻页
Route::get('index/shift-to-qr', 'index/Index/shift_to_qr');//转入二维码
Route::get('index/roll-out/:id', 'index/Index/roll_out');//转出
Route::post('index/roll-out', 'index/Assetchange/asset_out');//转出
Route::get('index/exchange', 'index/Index/exchange');//转换
Route::post('index/exchange', 'index/Assetchange/exchange');//转换
Route::get('index/worship','index/Index/worship');//祭拜
Route::get('index/added','index/Index/added');//增值服务worship.html
Route::get('index/withdraw', 'index/Index/withdraw');//提现
Route::post('index/withdraw', 'index/Assetchange/withdraw');//提现
Route::get('index/goods', 'index/Index/goods');//产品详情
Route::get('index/withdraw-list','index/Index/withdraw_list');//提现页面
Route::get('index/welfare-list','index/Index/welfare_list');//领奖记录页面
Route::get('index/welfare','index/Index/welfare');//福利奖列表
Route::post('index/welfare','index/Assetchange/welfare');//申请领奖

//个人中心
Route::get('index/personal', 'index/Personal/personal');
Route::get('index/center', 'index/Personal/center');
Route::get('index/self', 'index/Personal/self');
Route::post('index/self', 'index/Personal/nickname');
Route::get('index/password', 'index/Personal/pass');
Route::post('index/password', 'index/Personal/password');
Route::get('index/pay-pass', 'index/Personal/pay_pass');
Route::post('index/pay-pass', 'index/Personal/pay_password');
Route::get('index/share', 'index/Personal/share');
Route::get('index/act', 'index/Personal/act');
Route::post('index/act', 'index/Personal/acted');
Route::get('index/act-info/:id', 'index/Personal/info');
Route::get('index/act-out/:id', 'index/Personal/act_out');
Route::get('index/pay-note', 'index/Personal/pay_note');
Route::get('index/pay-note-table', 'index/Personal/pay_note_table');
Route::get('index/team', 'index/Personal/team');
Route::get('index/team-table/:id', 'index/Personal/team_table');
/**
 * 前台路由结束
 */

Route::get('index/alipay','index/Alipay/pay');
Route::get('notify','index/Alipay/notify');
Route::post('index/test_pay','index/Alipay/test_pay');