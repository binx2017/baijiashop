<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017-2-22
 * Time: 下午 3:42
 */
namespace Back\Model;
use Think\Model;

class GoodsModel extends Model
{
    protected  $patchValidate = true;
    protected $_validate = [
       //验证自己写
        ['sku_id','checkSkuId','选择合理的库存单位',self::EXISTS_VALIDATE,'callback'],
        ['tax_id','checkTaxId','选择合理的税类型',self::EXISTS_VALIDATE,'callback'],
        ['stock_status_id','stockStatusId','选择合理的税类型',self::EXISTS_VALIDATE,'callback'],
        ['length_unit_id','lengthUnitId','选择合理的税类型',self::EXISTS_VALIDATE,'callback'],
    ];
    protected $_auto= [
       //自动加载自己写
        ['upc' , 'mkUpc',self::MODEL_INSERT,'callback'],
        ['deleted','0',self::MODEL_INSERT],
        ['date_available','mkDateAvailable',self::MODEL_INSERT,'callback'],
        ['description','mkDescription',self::MODEL_BOTH,'callback'],
        ['created_at','time',self::MODEL_INSERT,'function'],
        ['updated_at','time',self::MODEL_BOTH,'function'],
    ];
    protected function mkUpc($upc){
        if ($upc != ''){
            return $upc;
        }
        return date('YmdHis').mt_rand(1000,9999).mt_rand(1000,9999);
    }
    protected function mkDateAvailable($value=''){
        if ($value != ''){
            return $value;
        }
        return date('Y-m-d');
    }
    protected function mkDescription($value){
        $pattern = '/<script.*?>.*?<\/script>/is';
        $result = preg_replace_callback($pattern,function ($match){
            return htmlspecialchars($match[0]);
        },$value);
        return $result;
    }
    public function checkSkuId($value){
        return (bool)M('Sku')->find($value);
    }
    public function checkTaxId($value){
        return (bool)M('tax')->find($value);
    }
    public function stockStatusId($value){
        return (bool)M('stockStatus')->find($value);
    }
    public function lengthUnitId($value){
        return (bool)M('lengthUnit')->find($value);
    }
}