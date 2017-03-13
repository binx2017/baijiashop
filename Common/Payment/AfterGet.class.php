<?php
/**
 * Created by 小韩说理
 * User: 韩忠康
 * Date: 2017/3/10
 * Time: 16:54
 */

namespace Common\Payment;


use Common\Itfc\IPayment;

class AfterGet implements IPayment
{
    public function get()
    {
        return '货到付款';
    }

}