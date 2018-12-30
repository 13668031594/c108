<?php

namespace app\adv\controller;

use app\http\controller\AdminController;
use think\Request;

class AdvController extends AdminController
{
    private $class;

    public function __construct(Request $request)
    {
        //初始化类库
        $this->class = new \classes\adv\AdvClass();

        //验证登录,并获取管理员信息
        $this->class->is_login();
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
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function getCreate()
    {
        //视图
        return parent::view('adv');
    }

    /**
     * 保存新建的资源
     *
     * @return \think\Response
     */
    public function save(Request $request)
    {
        //验证字段
        $this->class->validator_save($request);

        //添加
        $this->class->save($request);

        //反馈成功
        return parent::success('/adv/index');
    }

    /**
     * 显示指定的资源
     *
     * @param  int $id
     * @return \think\Response
     */
    public function getRead($id)
    {
        //获取数据
        $model = $this->class->read($id);

        //视图
        return parent::view('adv', ['adv' => $model]);
    }

    /**
     * 显示编辑资源表单页.
     *
     * @param  int $id
     * @return \think\Response
     */
    public function getEdit($id)
    {
        //获取数据
        $result = $this->class->edit($id);

        //视图
        return parent::view('adv', ['adv' => $result]);
    }

    /**
     * 保存更新的资源
     *
     * @param  int $id
     * @return \think\Response
     */
    public function update($id,Request $request)
    {
        //验证字段
        $this->class->validator_update($id,$request);

        //更新
        $this->class->update($id,$request);

        //删除过期图片
        $this->class->image_delete();

        //反馈成功
        return parent::success('/adv/index');
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
        return parent::success('/adv/index');
    }

    public function getTable()
    {
        $result = $this->class->index();
        $this->class->image_delete();

        return parent::tables($result);
    }

    public function postSave(Request $request)
    {
        $id = input('id');

        if (empty($id)) return self::save($request);
        else return self::update($id,$request);
    }

    public function postImage(Request $request)
    {
        $result = $this->class->image($request);

        return parent::success('',null,$result);
    }
}
