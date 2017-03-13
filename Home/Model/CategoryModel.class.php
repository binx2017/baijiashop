<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017-3-6
 * Time: 下午 9:18
 */

namespace Home\Model;


use Think\Model;

class CategoryModel extends Model
{
        public function getNested(){
            S(
               [
                   'type'=>'redis',
                   'host'=>CG('redis_host','127.0.0.1'),
                   'port'=>CG('redis_port','6379'),
               ]
            );
            $cat_nested = S('category_nested');
            if (!$cat_nested){
                $list = $this->where(['is_used'=>1,'is_nav'=>1])->order('sort_number')->select();
                $cat_nested = $this->nested($list);
                S('category_nested',$cat_nested);
            }
            return $cat_nested;
        }
        protected function nested($list,$category_id=0){
            $children = [];
            foreach($list as $row){
                if ($row['parent_id'] == $category_id){
                    $row['children'] = $this->nested($list,$row['category_id']);
                    $children[] = $row;
                }
            }
            return $children;
        }
        public function getParent($category_id){
            static $rows = [];
            $row = $this->find($category_id);
            $rows[]=$row;
            if ($row['parent_id'] != 0){
                $this->getParent($row['parent_id']);
            }
            return $rows;
        }
}