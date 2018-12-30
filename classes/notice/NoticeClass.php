<?php
/**
 * Created by PhpStorm.
 * User: yangyang
 * Date: 2018/10/17
 * Time: 下午5:57
 */

namespace classes\notice;

use classes\AdminClass;
use classes\ListInterface;
use think\Request;

class NoticeClass extends AdminClass implements ListInterface
{
    public $model;

    public function __construct()
    {
        $this->model = new \app\notice\model\NoticeModel();
    }

    public function index()
    {
        return parent::page($this->model);
    }

    public function create()
    {
        // TODO: Implement create() method.
    }

    public function save(Request $request)
    {
        $master = parent::master();

        $model = $this->model;
        $model->title = $request->post('title');
        $model->content = $request->post('fwb-content');
        $model->master_id = $master['id'];
        $model->master_nickname = $master['nickname'];
        $model->created_at = date('Y-m-d H:i:s');
        $model->save();
    }

    public function read($id)
    {
        $model = $this->model->where('id', '=', $id)->find();

        if (is_null($model)) parent::redirect_exception('/notice', ['公告不存在']);

        return $model->getData();
    }

    public function edit($id)
    {
        return self::read($id);
    }

    public function update($id,Request $request)
    {
        $model = $this->model->where('id', '=', $id)->find();

        if (is_null($model)) parent::ajax_exception(301, ['公告不存在']);

        $model->title = $request->post('title');
        $model->content = $request->post('fwb-content');
        $model->save();
    }

    public function delete($id)
    {
        $this->model->whereIn('id', $id)->delete();
    }

    public function validator_save(Request $request)
    {
        $rule = [
            'title' => 'require|min:1|max:255',
            'fwb-content' => 'require|min:1|max:2000',
        ];

        $file = [
            'title' => '标题',
            'fwb-content' => '内容'
        ];

        $result = parent::validator($request->post(), $rule, [], $file);
        if (!is_null($result)) parent::ajax_exception(302, $result);
    }

    public function validator_update($id,Request $request)
    {
        $rule = [
            'title' => 'require|min:1|max:255',
            'fwb-content' => 'require|min:1|max:2000',
        ];

        $file = [
            'title' => '标题',
            'fwb-content' => '内容'
        ];

        $result = parent::validator($request->post(), $rule, [], $file);
        if (!is_null($result)) parent::ajax_exception(303, $result);
    }

    public function validator_delete($id)
    {
        // TODO: Implement validator_delete() method.
    }

}