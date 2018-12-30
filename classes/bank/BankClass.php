<?php
/**
 * Created by PhpStorm.
 * User: yangyang
 * Date: 2018/11/24
 * Time: 下午4:36
 */

namespace classes\bank;


use app\bank\model\BankModel;
use app\member\model\MemberModel;
use classes\AdminClass;
use classes\ListInterface;
use think\Request;

class BankClass extends AdminClass implements ListInterface
{
    public $model;

    public function __construct()
    {
        $this->model = new BankModel();
    }

    public function index()
    {
        return parent::page($this->model, ['order_name' => 'sort', 'order_type' => 'asc']);
    }

    public function create()
    {
    }

    public function save(Request $request)
    {
        $model = $this->model;
        $model->name = $request->post('name');
        $model->sort = $request->post('sort');
        $model->created_at = date('Y-m-d H:i:s');
        $model->save();
    }

    public function read($id)
    {
        $bank = $this->model->where('id', '=', $id)->find();

        if (is_null($bank)) parent::redirect_exception('/admin/bank/index', '银行不存在');

        return $bank->getData();
    }

    public function edit($id)
    {
        return self::read($id);
    }

    public function update($id, Request $request)
    {
        $model = $this->model->where('id', '=', $id)->find();
        if (is_null($model)) parent::redirect_exception('/admin/bank/index', '银行不存在');
        $model->name = $request->post('name');
        $model->sort = $request->post('sort');
        $model->updated_at = date('Y-m-d H:i:s');
        $model->save();

        $member = new MemberModel();
        $member->where('bank_id', '=', $id)->update(['bank_name' => $model->name]);
    }

    public function delete($id)
    {
        $this->model->whereIn('id', $id)->delete();
    }

    public function validator_save(Request $request)
    {
        $rule = [
            'name|名称' => 'require|length:1,255',
            'sort|排序' => 'require|integer|between:0,100',
        ];

        $result = parent::validator($request->post(), $rule);
        if (!is_null($result)) parent::ajax_exception(502, $result);
    }

    public function validator_update($id, Request $request)
    {
        $rule = [
            'name|名称' => 'require|length:1,255',
            'sort|排序' => 'require|integer|between:0,100',
        ];

        $result = parent::validator($request->post(), $rule);
        if (!is_null($result)) parent::ajax_exception(502, $result);
    }

    public function validator_delete($id)
    {
        $test = new MemberModel();
        $test = $test->whereIn('bank_id', $id)->find();
        if (!is_null($test)) parent::ajax_exception(000, $test->bank_name . '正在使用中，无法删除');
    }
}