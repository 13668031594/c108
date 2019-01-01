<?php
/**
 * Created by PhpStorm.
 * User: yangyang
 * Date: 2018/12/31
 * Time: 下午3:49
 */

namespace classes\bill;

use app\member\model\MemberModel;
use app\member\model\MemberRecordModel;
use app\order\model\OrderModel;
use app\withdraw\model\WithdrawModel;
use classes\AdminClass;

class BillClass extends AdminClass
{
    public $join_name = null;
    public $column = 'created_at';
    public $type;
    public $types;
    public $times;
    public $where;
    public $wheres;
    public $number;

    //时间与筛选
    public function time()
    {
        //获取折线图时间模式
        $type = input('type');

        //获取起始时间段
        $begin_time = is_null(input('startTime')) ? '2017-11-1 00:00:00' : input('startTime');

        //获取结束时间段
        $end_time = is_null(input('endTime')) ? self::time_date() : input('endTime');

        if ($end_time < $begin_time) parent::ajax_exception(000, ['起始时间不得大于结束时间']);

        //计算起止时间,并给予计算时间戳
        switch ($type) {
            case 'year':

                $types = '+1 year';

                //起始点
                $begin_date = self::time_date(self::time_stamp($begin_time), 'Y') . '-01-01 00:00:00';

                //结束点
                $end_date = self::time_date(strtotime($types, self::time_stamp($end_time)), 'Y') . '-01-01 00:00:00';

                break;
            case 'month':

                $types = '+1 month';

                //起始点
                $begin_date = self::time_date(self::time_stamp($begin_time), 'Y-m') . '-01 00:00:00';

                //结束点
                $end_date = self::time_date(strtotime($types, self::time_stamp($end_time)), 'Y-m') . '-01 00:00:00';

                break;
            case 'day':
                $types = '+1 day';

                //起始点
                $begin_date = self::time_date(self::time_stamp($begin_time), 'Y-m-d') . ' 00:00:00';
                //结束点
                $end_date = self::time_date(strtotime($types, self::time_stamp($end_time)), 'Y-m-d') . ' 00:00:00';;

                break;
            case 'week':
                $types = '+7 day';

                //起始点
                $begin_date = self::time_date(self::time_stamp($begin_time), 'Y-m-d') . ' 00:00:00';
                //结束点
                $end_date = self::time_date(strtotime($types, self::time_stamp($end_time)), 'Y-m-d') . ' 00:00:00';;

                break;
            default:
                //结束点
                $end_date = self::time_date(strtotime('+1 day', self::time_stamp()), 'Y-m-d') . ' 00:00:00';;

                //起始时间
                $begin_date = self::time_date(strtotime('-7 day', strtotime($end_date)));

                $type = 'day';
                $types = '+1 day';

                break;
        }

        //初始化times
        $times = [];
        $where = [];

        //键
        $key = 0;

        //计算翻页数据
        $page = (int)input('page');
        $page = empty($page) ? 1 : $page;
        $limit = (int)input('limit');
        $limit = empty($limit) ? 20 : $limit;
        $base = ($page - 1) * $limit;


        //修改起始时间
        if (!empty($base)) $begin_date = self::time_date(strtotime('+' . $base . ' ' . $type, strtotime($begin_date)));

        //组合数据及筛选条件
        while (($begin_date < $end_date)) {

            $end = self::time_date(strtotime($types, self::time_stamp($begin_date)));

            if ($key < $limit) {

                $where[$key] = [
                    ($this->join_name . $this->column) => [
                        ['>=', $begin_date],
                        ['<', $end]
                    ]
                ];

                $times[$key] = substr($begin_date, 0, -9);
            }

            $wheres[$key]['begin'] = $begin_date;
            $begin_date = self::time_date(strtotime($types, self::time_stamp($begin_date)));
            $wheres[$key]['end'] = $begin_date;

            $key++;
        }

        $this->number = $key + 1 + $base;
        $this->type = $type;
        $this->types = $types;
        $this->times = $times;
        $this->where = $where;
        $this->wheres = $wheres;
    }

    /**
     * 时间,返回时间戳
     *
     * @param null $date
     * @return false|int
     */
    private function time_stamp($date = null)
    {
        if (is_null($date)) {

            $time = time();
        } else {

            $time = strtotime($date);
        }

        return $time;
    }

    /**
     * 时间，返回字符串
     *
     * @param null $time
     * @param string $style
     * @return false|string
     */
    private function time_date($time = null, $style = 'Y-m-d H:i:s')
    {
        if (is_null($time)) {

            $date = date($style);
        } else {

            $date = date($style, $time);
        }

        return $date;
    }

    /*public function title()
    {
        return [
            '众筹新增'
        ];
    }*/

    public function total()
    {
        $result = [];

        //众筹
//        $result[0]['name'] = '众筹新增';//名称
//        $result[0]['type'] = 'line';//固定type
//        $result[0]['stack'] = 'a';//随机字符串

        //家谱
        /*$result[1]['name'] = '家谱会员新增';//名称
        $result[1]['type'] = 'line';//固定type
        $result[1]['stack'] = 'b';//随机字符串*/

        foreach ($this->where as $k => $v) {

            $order = new OrderModel();
            $member_record = new MemberRecordModel();

//            $result[0]['data'][$k]['total'] = $order->where($v)->sum('remind');
//            $result[0]['data'][$k]['number'] = $order->where($v)->count();
//            $result[0]['data'][$k]['81'] = $member_record->where($v)->where('type', '=', '81')->sum('remind');
//            $result[0]['data'][$k]['82'] = $member_record->where($v)->where('type', '=', '82')->sum('remind');
//            $result[0]['data'][$k]['83'] = $member_record->where($v)->where('type', '=', '83')->sum('remind');
//            $result[0]['data'][$k]['remind'] = $member_record->where($v)->sum('remind');

            $result['message'][$k]['total'] = $order->where($v)->sum('remind');
            $result['message'][$k]['number'] = $order->where($v)->count();
            $result['message'][$k]['81'] = $member_record->where($v)->where('type', '=', '81')->sum('remind');
            $result['message'][$k]['82'] = $member_record->where($v)->where('type', '=', '82')->sum('remind');
            $result['message'][$k]['83'] = $member_record->where($v)->where('type', '=', '83')->sum('remind');
            $result['message'][$k]['remind'] = $member_record->where($v)->whereIn('type', [81, 82, 83])->sum('remind');
            $result['message'][$k]['over'] = $result['message'][$k]['total'] - $result['message'][$k]['remind'];
//            dump($this->wheres);
            $result['message'][$k]['begin'] = $this->wheres[$k]['begin'];
            $result['message'][$k]['end'] = $this->wheres[$k]['end'];
        }

        $result['total'] = $this->number;

        return $result;
    }

    public function member()
    {
        $result = [];


        foreach ($this->where as $k => $v) {

            $member = new MemberModel();

            $result['message'][$k]['number'] = $member->where($v)->count();
            $result['message'][$k]['begin'] = $this->wheres[$k]['begin'];
            $result['message'][$k]['end'] = $this->wheres[$k]['end'];
        }

        $result['total'] = $this->number;

        return $result;
    }

    public function withdraw()
    {
        $result = [];


        foreach ($this->where as $k => $v) {

            $member = new WithdrawModel();

            $result['message'][$k]['number'] = $member->where($v)->where('status', '=', '1')->count();
            $result['message'][$k]['total'] = $member->where($v)->where('status', '=', '1')->sum('total');
            $result['message'][$k]['remind'] = $member->where($v)->where('status', '=', '1')->sum('remind');
            $result['message'][$k]['integral'] = $member->where($v)->where('status', '=', '1')->sum('integral');
            $result['message'][$k]['begin'] = $this->wheres[$k]['begin'];
            $result['message'][$k]['end'] = $this->wheres[$k]['end'];
        }

        $result['total'] = $this->number;

        return $result;
    }
}