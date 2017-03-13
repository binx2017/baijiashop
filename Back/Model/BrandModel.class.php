<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017-2-22
 * Time: 下午 3:42
 */
namespace Back\Model;
use Think\Model;

class BrandModel extends Model
{
    protected  $patchValidate = true;
    protected $_validate = [
        ['title','require','请填写品牌名'],
        ['title','','品牌名已经存在',self::EXISTS_VALIDATE,'unique'],
        ['site','url','网址不正确',self::VALUE_VALIDATE],
        ['logo','require','请上传LOGO'],
        ['sort_number','require','请填写排序字段'],
        ['sort_number','number','使用数字'],
    ];
    protected $_auto= [
        ['created_at','time',self::MODEL_INSERT,'function'],
        ['updated_at','time',self::MODEL_BOTH,'function'],
    ];

}