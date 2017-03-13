<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017-2-22
 * Time: 下午 3:42
 */
namespace Back\Model;
use Think\Model;

class CategoryModel extends Model
{
    protected  $patchValidate = true;
    protected $_validate = [
       //验证自己写
    ];
    protected $_auto= [
       //自动加载自己写
       ['created_at','time',self::MODEL_INSERT,'function'],
       ['updated_at','time',self::MODEL_BOTH,'function'],
    ];
    public function getTree(){
        S([
            'type'=>'redis',
            'host'=> CG('redis_host','127.0.0.1'),
            'port'=>CG('redis_port','6379'),
        ]);
        $tree = S('category_list');
        if(!$tree){
            $list = $this->order('sort_number')->select();
            $tree = $this->tree($list);
            S('category_list',$tree);
        }
        return $tree;
    }
    public function tree($list,$category_id = 1,$deep=0){
        static $tree = [];
        foreach($list as $row){
            if ($row['parent_id'] == $category_id){
                $row['deep'] = $deep;
                $tree[]=$row;
                $this->tree($list,$row['category_id'],$deep+1);
            }
        }
        return $tree;
    }

}