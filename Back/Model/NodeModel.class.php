<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017-2-22
 * Time: 下午 3:42
 */
namespace Back\Model;
use Think\Model;

class NodeModel extends Model
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
    public function getParentTree(){
        S([
            'type'=>'redis',
            'host'=> CG('redis_host','127.0.0.1'),
            'port'=>CG('redis_port','6379'),
        ]);
        $tree = S('Node_list');
        if(!$tree){
            $list = $this->where(['level'=>['elt',2]])->select();
            $tree = $this->tree($list);
            S('Node_list',$tree);
        }
        return $tree;
    }
    public function tree($list,$node_id = 0){
        static $tree;
        if ($node_id == 0 ) $tree =[];
        foreach($list as $node){
            if ($node['pid'] == $node_id){
                $tree[]=$node;
                $this->tree($list,$node['id']);
            }
        }
        return $tree;
    }
    public function getNodeNested(){
        S([
            'type'=>'redis',
            'host'=>CG('redis_host','127.0.0.1'),
            'port'=>CG('redis_port','6379'),
        ]);
        $nested = S('nest_list');
        if (!$nested){
            $list = $this->select();
            $nested = $this->nested($list);
            S('nest_list',$nested);
        }
        return $nested;
    }
    public function nested($list,$node_id = 0){
        $node_list = [];
        foreach ($list as $node) {
            if ($node['pid'] == $node_id){
                $node['children'] = $this->nested($list,$node['id']);
                $node_list[] = $node;
            }
        }
        return $node_list;
    }

}