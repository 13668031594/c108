<?php
/**
 * Created by PhpStorm.
 * User: yangyang
 * Date: 2018/11/24
 * Time: 下午7:25
 */

namespace classes\goods;


use app\goods\model\GoodsContentModel;
use app\goods\model\GoodsImagesModel;
use app\goods\model\GoodsModel;
use classes\AdminClass;
use classes\ListInterface;
use classes\system\SystemClass;
use classes\vendor\StorageClass;
use think\Request;

class GoodsClass extends AdminClass implements ListInterface
{
    public $model;
    public $image;
    public $content;
    private $dir = 'goods_image';

    public function __construct()
    {
        $this->model = new GoodsModel();
        $this->image = new GoodsImagesModel();
        $this->content = new GoodsContentModel();
        if (!is_dir($this->dir)) mkdir($this->dir);//新建文件夹
    }

    public function index()
    {
        $result = parent::page($this->model);

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
        $model->code = $request->post('code');
        $model->describe = $request->post('describe');
        $model->amount = number_format($request->post('amount'), 2, '.', '');
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
        //产品
        $model = $this->model->where('id', '=', $id)->find();
        if (is_null($model)) parent::redirect_exception('/admin/goods/index', '产品不存在');

        $model->location = is_null($model->location) ? config('young.image_not_found') : $model->location;

        return $model->getData();
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
        if (is_null($model)) parent::ajax_exception(000, '产品不存在');

        $model->name = $request->post('name');
        $model->amount = number_format($request->post('amount'), 2, '.', '');
        $model->sort = $request->post('sort');
        $model->updated_at = date('Y-m-d H:i:s');
        $model->save();

        $content = $this->content->where('pid', '=', $model->id)->find();
        if (is_null($content)) {

            $content = new GoodsContentModel();
            $content->pid = $model->id;
            $content->created_at = $model->updated_at;
        }
        $content->content = $request->post('fwb-content');
        $content->updated_at = $model->updated_at;
        $content->save();

        self::image_save($model, $request);

        self::sys_update($model->code,$model->amount);
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
            'code|编号' => 'require|length:1,255|unique:goods,code',
            'describe|描述' => 'require|length:1,255',
            'amount|单价' => 'require|between:0,100000000',
            'sort|排序' => 'require|integer|between:1,999',
            'imageId|图片' => 'require|array',
            'fwb-content|详情' => 'require|max:20000',
        ];

        $result = parent::validator($request->post(), $rule);
        if (!is_null($result)) parent::ajax_exception(000, $result);
    }

    public function validator_update($id, Request $request)
    {
        $rule = [
            'name|名称' => 'require|length:1,255',
            'amount|单价' => 'require|between:0,100000000',
            'sort|排序' => 'require|integer|between:1,999',
            'imageId|图片' => 'require|array',
            'fwb-content|详情' => 'require|max:20000',
        ];

        $result = parent::validator($request->post(), $rule);
        if (!is_null($result)) parent::ajax_exception(000, $result);
    }

    public function validator_delete($id)
    {
        // TODO: Implement validator_delete() method.
    }

    //保存产品与图片关系
    public function image_save(GoodsModel $model, Request $request)
    {
        //id
        $ids = $_POST['imageId'];

        //清除旧图片绑定
        $images = new GoodsImagesModel();
        $images->where('pid', '=', $model['id'])->whereNotIn('pid', $ids)->update(['pid' => null]);

        //添加新图片绑定
        $this->image->whereIn('id', $ids)->update(['pid' => $model['id']]);

        //添加第一张图片到产品封面信息
        $first = array_shift($ids);//获取id
        $images = new GoodsImagesModel();//初始化模型
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

        $location = 'goods_' . time();

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
        $storage = new StorageClass('goods_image_delete');
        $over = $storage->get();
        if (!is_array($over) && ($over >= $date)) return;//执行过

        //寻找并删除文件
        $model = new GoodsImagesModel();
        $result = $model->where('created_at', '<', $date)->where('pid', '=', null)->select();
        if (count($result) > 0) foreach ($result as $v) {

            if (!is_null($v->location) && file_exists(substr($v->location, 1))) unlink(substr($v->location, 1));
        }

        //删除数据
        $model = new GoodsImagesModel();
        $model->where('created_at', '<', $date)->where('pid', null)->delete();

        //保存删除时间
        $storage->save($date);
    }

    /**
     * 修改在配置文件中的金额
     *
     * @param $code
     * @param $amount
     */
    public function sys_update($code, $amount)
    {
        $sys = new SystemClass();

        $set = $sys->index();

        if (isset($set[$code])){

            $set[$code] = $amount;

            //保存到文件
            $sys->storage->save(json_encode($set));
        }
    }
}