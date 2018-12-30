<?php
/**
 * Created by PhpStorm.
 * User: yangyang
 * Date: 2018/11/24
 * Time: 下午4:36
 */

namespace app\welfare\controller;


use app\http\controller\AdminController;
use classes\welfare\WelfareClass;
use think\Request;

class WelfareController extends AdminController
{
    private $class;

    public function __construct(Request $request)
    {
        //初始化类库
        $this->class = new WelfareClass();

//        $this->class->is_login();

        //删除过期图片
        $this->class->image_delete();
    }

    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function getIndex()
    {
        //视图
        return parent::view('index');
    }

    /**
     * json返回列表数据
     *
     * @return \think\response\Json
     */
    public function getTable()
    {
        $result = $this->class->index();

        return parent::tables($result);
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function getCreate()
    {
        //视图
        return parent::view('welfare');
    }

    /**
     * 显示编辑资源表单页
     *
     * @param Request $request
     * @return \think\response\View
     */
    public function getEdit(Request $request)
    {
        //获取数据
        $welfare = $this->class->edit($request->get('id'));

        //视图
        return parent::view('welfare', $welfare);
    }

    /**
     * 删除指定资源
     */
    public function getDelete()
    {
        $ids = explode(',', input('id'));

        //验证资源
        $this->class->validator_delete($ids);

        //删除
        $this->class->delete($ids);

        //反馈成功
        return parent::success('/welfare/index');
    }

    /**
     * 保存与更新入口
     *
     * @param Request $request
     * @return \think\response\Json
     */
    public function postSave(Request $request)
    {
        $id = $request->post('id');

        if (empty($id)) self::save($request);
        else self::update($id, $request);

        //反馈成功
        return parent::success('/welfare/index');
    }

    /**
     * 保存新建的资源
     *
     * @param Request $request
     */
    private function save(Request $request)
    {
        //验证字段
        $this->class->validator_save($request);

        //添加
        $this->class->save($request);
    }

    /**
     * 保存更新的资源
     *
     * @param $id
     * @param Request $request
     */
    private function update($id, Request $request)
    {
        //验证字段
        $this->class->validator_update($id, $request);

        //更新
        $this->class->update($id, $request);
    }


    /**
     * 上传图片
     *
     * @param Request $request
     * @return \think\response\Json
     */
    public function postImage(Request $request)
    {
        $result = $this->class->image($request);

        return parent::success('', null, $result);
    }


}