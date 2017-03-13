<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017-2-22
 * Time: 下午 3:42
 */
namespace Back\Model;


use Think\Model\RelationModel;

class AttributeModel extends RelationModel
{
    protected $_link = [
      'option_list'=>[
          'mapping_type'=>self::HAS_MANY,
          'class_name' => 'AttributeOption',
          'foreign_key'=>'attribute_id'
      ]
    ];
    protected  $patchValidate = true;
    protected $_validate = [
       //验证自己写
    ];
    protected $_auto= [
       //自动加载自己写
       ['created_at','time',self::MODEL_INSERT,'function'],
       ['updated_at','time',self::MODEL_BOTH,'function'],
    ];

}