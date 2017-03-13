<?php
/**
 * Created by 小韩说理
 * User: 韩忠康
 * Date: 2017/3/10
 * Time: 17:33
 */

namespace Common\Payment;


use Common\Itfc\IPayment;

class Bank implements IPayment
{
    public function get()
    {
        return '银行转帐';
    }

}