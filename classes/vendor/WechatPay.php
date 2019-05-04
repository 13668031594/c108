<?php

namespace classes\vendor;

use classes\FirstClass;

class WechatPay extends FirstClass
{
    private $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
    private $appid = 'wxa42540f7d160427e';
    private $EncodingAESKey = '1cdf336760ef0dcb120f01bd22b04925';
    private $mch_id = '1529538981';
    private $location_ip = '39.108.53.9';
    private $location = 'www.ahu66.com';

    private $set;
    public $time;

    /**
     * 公众号支付
     *
     * @param $order
     * @return mixed
     */
    public function pay($order)
    {
        //进行配置
        self::set($order, 'MWEB');

        return self::wechat_pay_set();
    }


    /**
     * 进行配置
     *
     * @param $order
     * @param $trade_type
     */
    private function set($order, $trade_type)
    {
        //判断订单信息
//        if (!isset($order['body']) || is_null($order['body'])) parent::errors_default(['请传入订单标题']);
//        if (!isset($order['out_trade_no']) || is_null($order['out_trade_no'])) parent::errors_default(['请传入订单号']);
//        if (!isset($order['total_fee']) || is_null($order['total_fee'])) parent::errors_default(['请传入订单金额']);
//        if (!isset($order['order_type']) || is_null($order['order_type'])) parent::errors_default(['请传入订单类型']);

        $this->time = time();

        $set = [
            'appid' => $this->appid,
            'mch_id' => $this->mch_id,
            'nonce_str' => md5($this->time . rand(000, 999)),
            'body' => $order['body'],
            'out_trade_no' => $order['out_trade_no'] . '_' . time(),
            'total_fee' => $order['total_fee'],
            'spbill_create_ip' => $_SERVER['REMOTE_ADDR'],
            'notify_url' => 'http://' . $this->location . '/notify_wechat',
            'trade_type' => $trade_type,
        ];
//        Storage::put('item/WechatTest/time', $this->time);
//        Storage::put('item/WechatTest/set', parent::json_make($set));
        $this->set = $set;
    }

    public function wechat_pay_set()
    {
        //配置签名
        $this->set['sign'] = self::sign($this->set);
//        Storage::put('item/WechatTest/sign', parent::json_make($this->set['sign']));
//dump($this->set);
//exit;
        //进行支付
        $result = self::to_wechat();

        //反馈
        return $result;
    }

    /**
     * 生成签名
     *
     * @param $set
     * @return string
     */
    private function sign($set)
    {
        //排序
        ksort($set, SORT_STRING);

        return self::sign_implode($set);
    }

    /**
     * 生成签名方法
     *
     * @param $set
     * @return string
     */
    private function sign_implode($set)
    {
        //初始化字符串
        $stringArray = [];

        //循环组合字符串
        foreach ($set as $k => $v) {

            $str = $k . '=' . $v;

            $stringArray[] = $str;
        }

        $stringA = implode('&', $stringArray);

        $stringSignTemp = $stringA . '&key=' . $this->EncodingAESKey;//拼接

        $sign = md5($stringSignTemp);//加密
        $sign = strtoupper($sign);//大写

        //反馈
        return $sign;
    }

    /**
     * 访问微信支付客户端
     *
     * @return mixed
     */
    private function to_wechat()
    {
        $set = self::array_to_xml($this->set);

        $result = self::url_post($this->url, $set);

        $result = self::xml_to_array($result);

        return $result;
    }

    /**
     * 格式转换，xml转array
     *
     * @param $xml
     * @return mixed
     */
    public function xml_to_array($xml)
    {
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $values;
    }

    /**
     * 格式转换，array转xml
     *
     * @param $array
     * @return string
     */
    public function array_to_xml($array)
    {
        $xml = "<xml>\n";

        foreach ($array as $key => $val) {

            if (is_array($val)) {

                $xml .= "<" . $key . ">" . self::array_to_xml($val) . "</" . $key . ">\n";
            } else {

                $xml .= "<" . $key . ">" . $val . "</" . $key . ">\n";
            }
        }

        $xml .= "</xml>";

        return $xml;
    }

    /**
     * 访问url，post
     *
     * @param $url
     * @param $post_data
     * @return mixed
     */
    public function url_post($url, $post_data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

    public function notify()
    {
        //获取微信回调信息，xml格式
        $xml = request()->getContent();

        //转为array
        $array = self::xml_to_array($xml);

        //判断
        if (($array['return_code'] == 'SUCCESS') && ($array['result_code'] == 'SUCCESS')) {

            return $array;
        }else{

            exit('fails');
        }
    }
}
