<?php

namespace app\notice\controller;

use app\http\controller\AdminController;
use think\Request;

class NoticeController extends AdminController
{
    private $class;

    public function __construct(Request $request)
    {
        //初始化类库
        $this->class = new \classes\notice\NoticeClass();

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
        //获取管理员列表
//        $result = $this->class->index();

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
        return parent::view('notice', ['type' => 'create']);
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
        return parent::success('/notice/index');
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
        return parent::view('notice', ['notice' => $model]);
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
        return parent::view('notice', ['notice' => $result, 'type' => 'edit']);
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

        //反馈成功
        return parent::success('/notice/index');
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
        return parent::success('/notice/index');
    }

    public function getTable()
    {
        $result = $this->class->index();

        return parent::tables($result);
    }

    public function postSave(Request $request)
    {
        $id = input('id');

        if (empty($id)) return self::save($request);
        else return self::update($id,$request);
    }
}
