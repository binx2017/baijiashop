<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017-2-26
 * Time: 下午 2:10
 */
function CG($key,$default = ''){
    $model = M('Setting');
    $value = $model->getFieldByKey($key,'value');
    return $value? $value: $default;
}