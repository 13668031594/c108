<?php
/**
 * Created by PhpStorm.
 * User: yangyang
 * Date: 2018/11/24
 * Time: 下午4:36
 */

namespace classes\welfare;


use app\welfare\model\WelfareContentModel;
use app\welfare\model\WelfareModel;
use app\welfare\model\WelfareImagesModel;
use classes\AdminClass;
use classes\ListInterface;
use classes\vendor\StorageClass;
use think\Request;

class WelfareClass extends AdminClass implements ListInterface
{
    public $model;
    public $image;
    public $content;
    public $dir = 'welfare_image';

    public function __construct()
    {
        $this->model = new WelfareModel();
        $this->image = new WelfareImagesModel();
        $this->content = new WelfareContentModel();
        if (!is_dir($this->dir)) mkdir($this->dir);//新建文件夹
    }

    public function index()
    {
        $result = parent::page($this->model, ['order_name' => 'sort', 'order_type' => 'asc']);

        foreach ($result['message'] as &$v) {

            if (is_null($v['location']) || !file_exists(substr($v['location'], 1))) $v['location'] = config('young.image_not_found');
        }

        return $result;
    }

    public function create()
    {
    }

    public function save(Request $request)
    {
        $model = $this->model;
        $model->name = $request->post('name');
        $model->total = number_format($request->post('total'),2,'.','');
        $model->reward = $request->post('reward');
        $model->sort = $request->post('sort');
        $model->created_at = date('Y-m-d H:i:s');
        $model->save();

        $content = $this->content;
        $content->pid = $model->id;
        $content->content = $request->post('fwb-content');
        $content->created_at = $model->created_at;
        $content->save();

        self::image_save($model, $request);
    }

    public function read($id)
    {
        $welfare = $this->model->where('id', '=', $id)->find();

        if (is_null($welfare)) parent::redirect_exception('/admin/welfare/index', '奖项不存在');

        $welfare->location = is_null($welfare->location) ? config('young.image_not_found') : $welfare->location;

        return $welfare->getData();
    }

    public function edit($id)
    {
        $model = self::read($id);

        //图片
        $images = $this->image->where('pid', '=', $id)->where('id', '<>', $model['cover'])->column('*');
        $image = [];
        $i = 1;
        foreach ($images as $k => $v) {

            $image[$i]['id'] = $v['id'];
            $image[$i]['location'] = is_null($v['location']) ? config('young.image_not_found') : $v['location'];

            $i++;
        }
        ksort($image);

        //正文
        $content = $this->content->where('pid', '=', $id)->find();

        //集合
        return [
            'self' => $model,
            'images' => $image,
            'content' => $content
        ];
    }

    public function update($id, Request $request)
    {
        $model = $this->model->where('id', '=', $id)->find();
        if (is_null($model)) parent::redirect_exception('/admin/welfare/index', '奖项不存在');
        $model->name = $request->post('name');
        $model->total = number_format($request->post('total'),2,'.','');
        $model->reward = $request->post('reward');
        $model->sort = $request->post('sort');
        $model->updated_at = date('Y-m-d H:i:s');
        $model->save();

        $content = $this->content->where('pid', '=', $model->id)->find();
        if (is_null($content)){

            $content = new WelfareContentModel();
            $content->pid = $model->id;
        }
        $content->content = $request->post('fwb-content');
        $content->updated_at = $model->updated_at;
        $content->save();

        self::image_save($model, $request);
    }

    public function delete($id)
    {
        $this->model->whereIn('id', $id)->delete();
        $this->image->whereIn('pid', $id)->update(['pid' => null]);
        $this->content->whereIn('pid', $id)->delete();
    }

    public function validator_save(Request $request)
    {
        $rule = [
            'name|名称' => 'require|length:1,255',
            'sort|排序' => 'require|integer|between:0,100',
            'reward|奖励' => 'require|length:1,255',
            'total|消耗消费' => 'require|number|between:0,100000000',
            'imageId|图片' => 'require|array',
            'fwb-content|详情' => 'require|max:20000',
        ];

        $result = parent::validator($request->post(), $rule);
        if (!is_null($result)) parent::ajax_exception(502, $result);
    }

    public function validator_update($id, Request $request)
    {
        $rule = [
            'name|名称' => 'require|length:1,255',
            'sort|排序' => 'require|integer|between:0,100',
            'reward|奖励' => 'require|length:1,255',
            'total|消耗消费' => 'require|number|between:0,100000000',
            'imageId|图片' => 'require|array',
            'fwb-content|详情' => 'require|max:20000',
        ];

        $result = parent::validator($request->post(), $rule);
        if (!is_null($result)) parent::ajax_exception(502, $result);
    }

    public function validator_delete($id)
    {
    }

    //保存商品与图片关系
    public function image_save(WelfareModel $model, Request $request)
    {
        //id
        $ids = $_POST['imageId'];

        //清除旧图片绑定
        $images = new WelfareImagesModel();

        $images->where('pid', '=', $model->id)->whereNotIn('pid', $ids)->update(['pid' => null]);

        //添加新图片绑定
        $this->image->whereIn('id', $ids)->update(['pid' => $model->id]);

        //添加第一张图片到商品封面信息
        $first = array_shift($ids);//获取id
        $images = new WelfareImagesModel();//初始化模型
        $image = $images->where('id', '=', $first)->find();//寻找信息
        if (!is_null($image)) {//找到信息

            //赋值并保存
            $model->cover = $image->id;
            $model->location = $image->location;
            $model->save();
        }
    }

    //图片上传
    public function image(Request $request)
    {
        // 获取表单上传文件 例如上传了001.jpg
        $file = $request->file('images');

        $location = 'welfare_' . time();

        // 移动到框架应用根目录/public/uploads/ 目录下
        $info = $file->validate(['size' => (1024 * 1024), 'ext' => 'jpg,png,gif,jpeg,bmp'])->move($this->dir, $location);

        // 上传失败获取错误信息
        if (!$info) parent::ajax_exception(000, $file->getError());

        $model = $this->image;
        $model->location = '/' . $this->dir . '/' . $info->getSaveName();
        $model->created_at = date('Y-m-d H:i:s');
        $model->save();

        return [
            'image' => $model->location,
            'imageId' => $model->id,
            'index' => $request->post('index'),
        ];
    }

    //过期文件删除
    public function image_delete()
    {
        //过期时间
        $date = date('Y-m-d', strtotime('-1 day')) . ' 00:00:00';

        //验证今天是否执行过删除
        $storage = new StorageClass('welfare_image_delete');
        $over = $storage->get();
        if (!is_array($over) && ($over >= $date)) return;//执行过

        //寻找并删除文件
        $model = new WelfareImagesModel();
        $result = $model->where('created_at', '<', $date)->where('pid', '=', null)->select();
        if (count($result) > 0) foreach ($result as $v) {

            if (!is_null($v->location) && file_exists(substr($v->location, 1))) unlink(substr($v->location, 1));
        }

        //删除数据
        $model = new WelfareImagesModel();
        $model->where('created_at', '<', $date)->where('pid', null)->delete();

        //保存删除时间
        $storage->save($date);
    }
}