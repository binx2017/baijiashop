<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017-3-8
 * Time: 上午 10:16
 */

namespace Home\Model;


use Think\Model;

class GoodsModel extends Model
{
    public function getBreakCrumb($goods_id){
        $goods_info = M('goods')->field('name,category_id')->find($goods_id);
//        return $goods_info;
        $goods['title'] = $goods_info['name'];
        $goods['url'] = U('/goods/'.$goods_id);
        $category_id=$goods_info['category_id'];
        $rows = D('category')->getParent($category_id);
//        return $rows;
        $rows = array_reverse($rows);
        foreach ($rows as $row){
            $breakcrumb[] = [
                'title' => $row['category_title'],
                'url' =>U('/category/').$row['category_id'],
            ];
        }
        $breakcrumb[]=$goods;
        return $breakcrumb;
    }
    /*
     * 获取商品的价格
     * */
    public function getPrice($goods_id,$product_id=0,$member_id=0){
        //会员：积分...
        //不去考虑会员，仅仅考虑货品
        if ($product_id==0){
            return $this->getFieldByGoodsId($goods_id,'price');
        }else{
            return M('Product')->getFieldByProductId($product_id,'product_price');
        }
    }

}