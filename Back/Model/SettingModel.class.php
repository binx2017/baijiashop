<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017-2-22
 * Time: 下午 3:42
 */
namespace Back\Model;

use Think\Model\RelationModel;

class SettingModel extends RelationModel
{
    protected $_link = [
      'options'=>[
          'mapping_type'=>self::HAS_MANY,
          'class_name'=>'SettingOption',
          'foreign_key'=>'Setting_id',
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
    public function groupSetting(){
        $group_list= M('SettingGroup')->order('sort_number')->select();
        $setting_list = $this
            ->relation(true)
            ->field('s.*,it.type_title')
            ->alias('s')
            ->join('left join __INPUT_TYPE__ it using(input_type_id)')
            ->order('sort_number')
            ->select();
        $group_setting_list =[];
        foreach ($group_list as $group){
            $group['setting_list']=[];
            $group_setting_list[$group['setting_group_id']] = $group;
        }
        foreach ($setting_list as $setting) {
            $setting['value_list'] = explode(',',$setting['value']);
            $group_setting_list[$setting['setting_group_id']]['setting_list'][] =$setting;
        }
        return $group_setting_list;
    }
}