<?php
/**
 * Created by PhpStorm.
 * User: yangyang
 * Date: 2018/11/21
 * Time: 下午5:29
 */

namespace app\system\controller;

use app\http\controller\AdminController;
use classes\system\SystemClass;
use think\Request;

class SystemController extends AdminController
{
    private $class;

    public function __construct()
    {
        $this->class = new SystemClass();

        $this->class->is_login();
    }

    public function getIndex()
    {
        $self = $this->class->index();

        return parent::view('index', ['self' => $self]);
    }

    public function postIndex()
    {
        $this->class->save_validator();

        $result = $this->class->save();

        $this->class->image_delete($result);//删除未使用的logo

        return parent::success('/system/index');
    }

    public function postImage(Request $request)
    {
        $src = $this->class->image($request);

        if (!is_array($src)) {

            $result = [
                'code' => '1',
                'msg' => $src,
                'data' => [
                    'src' => '',
                    'total' => ''
                ]
            ];
        } else {

            $result = [
                'code' => '0',
                'msg' => '',
                'data' => $src
            ];
        }


        return json_encode($result);
    }

    public function getFirst()
    {
        $text = $this->class->first();

        return parent::view('first', ['text' => $text]);
    }

    public function postFirst(Request $request)
    {
        $this->class->save_first($request);

        return parent::success('/system/first');
    }
}