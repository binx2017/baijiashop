<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017-3-11
 * Time: 下午 5:23
 */

namespace Common\Payment;


use Common\Itfc\IPayment;

class Alipay implements IPayment
{
        public function get(){
            return '支付宝';
        }
}