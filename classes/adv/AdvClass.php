<?php
/**
 * Created by PhpStorm.
 * User: yangyang
 * Date: 2018/10/17
 * Time: 下午5:57
 */

namespace classes\adv;

use app\adv\model\AdvImagesModel;
use classes\AdminClass;
use classes\FirstClass;
use classes\ListInterface;
use think\Request;

class AdvClass extends AdminClass implements ListInterface
{
    public $model;
    public $image;
    public $dir = 'adv_image';

    public function __construct()
    {
        $this->model = new \app\adv\model\AdvModel();
        $this->image = new AdvImagesModel();

        if (!is_dir($this->dir)) mkdir($this->dir);
    }

    public function index()
    {
        $other = [
            'order_name' => 'sort',
            'order_type' => 'asc'
        ];

        $result = parent::page($this->model, $other);

        foreach ($result['message'] as &$v) {

            if (is_null($v['location'])) $v['location'] = config('young.image_not_found');
        }

        return $result;
    }

    public function create()
    {
        // TODO: Implement create() method.
    }

    public function save(Request $request)
    {
        $model = $this->model;
        $model->title = $request->post('title');
        $model->image = $request->post('imageId');
        $model->show = $request->post('show');
        $model->created_at = date('Y-m-d H:i:s');
        $model->save();
    }

    public function read($id)
    {
        $model = $this->model->where('id', '=', $id)->find();

        if (is_null($model)) parent::redirect_exception('/adv', ['广告不存在']);

        if (is_null($model->location)) $model->location = config('young.image_not_found');

        return $model->getData();
    }

    public function edit($id)
    {
        return self::read($id);
    }

    public function update($id, Request $request)
    {
        $model = $this->model->where('id', '=', $id)->find();

        if (is_null($model)) parent::ajax_exception(601, ['广告不存在']);

        $image = new AdvImagesModel();
        $image = $image->where('id', $request->post('imageId'))->find();

        $model->title = $request->post('title');
        $model->image = $image->id;
        $model->location = $image->location;
        $model->show = $request->post('show');
        $model->describe = $request->post('describe');
        $model->save();

        $image->adv = $model->id;
        $image->save();

        $images = new AdvImagesModel();
        $images->where('adv', '=', $model->id)->where('id', '<>', $image->id)->update(['adv' => null]);
    }

    public function delete($id)
    {
        $this->model->whereIn('id', $id)->delete();
        $image = new AdvImagesModel();
        $image->whereIn('adv', $id)->update(['adv' => null]);
    }

    public function validator_save(Request $request)
    {
        $rule = [
            'title' => 'require|min:1|max:255',
            'describe' => 'require|min:1|max:255',
            'image' => 'require'
        ];

        $file = [
            'title' => '标题',
            'describe' => '描述',
            'show' => '是否显示',
            'image' => '图片'
        ];

        $result = parent::validator($request->post(), $rule, [], $file);
        if (!is_null($result)) parent::ajax_exception(602, $result);
    }

    public function validator_update($id, Request $request)
    {
        $rule = [
            'title' => 'require|min:1|max:255',
            'describe' => 'require|min:1|max:255',
            'show' => 'require',
            'imageId' => 'require'
        ];

        $message = [
//            'imageId.unique' => '不能使用其他广告中的图片'
        ];

        $file = [
            'title' => '标题',
            'describe' => '描述',
            'show' => '是否显示',
            'imageId' => '图片'
        ];

        $result = parent::validator($request->post(), $rule, $message, $file);
        if (!is_null($result)) parent::ajax_exception(602, $result);
    }

    public function validator_delete($id)
    {
        // TODO: Implement validator_delete() method.
    }

    public function image(Request $request)
    {
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('images');

        $location = 'adv_' . $request->get('id');

        // 移动到框架应用根目录/public/uploads/ 目录下
        $info = $file->validate(['size' => (1024 * 1024), 'ext' => 'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . $this->dir, $location);

        if (!$info) parent::ajax_exception(600, $file->getError());

        $model = $this->image;
        $model->location = '/' . $this->dir . '/' . $info->getSaveName();
        $model->created_at = date('Y-m-d H:i:s');
        $model->save();

        return [
            'image' => $model->location,
            'imageId' => $model->id,
        ];
    }

    //删除过期图片
    public function image_delete()
    {
        $date = date('Y-m-d H:i:s', strtotime('-1 day'));

        $model = new AdvImagesModel();

        $result = $model->where('created_at', '<', $date)->where('adv', null)->select();

        if (count($result) > 0) foreach ($result as $v) {

            if (!is_null($v->location) && file_exists(substr($v->location, 1))) unlink(substr($v->location, 1));
        }

        $model = new AdvImagesModel();
        $model->where('created_at', '<', $date)->where('adv', null)->delete();
    }
}