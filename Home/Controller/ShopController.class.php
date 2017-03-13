<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017-3-6
 * Time: 下午 8:36
 */

namespace Home\Controller;


use Think\Controller;

class ShopController extends Controller
{
    public function IndexAction(){
        $this->assign('title',CG('shop_title','败家商城'));
        $this->display();
    }
    public function catNestedAction(){
        $cat_nest = D('Category')->getNested();
//        dump($cat_nest);die;
        if ($cat_nest){
            $this->ajaxReturn(['error'=>0,'data'=>$cat_nest]);
        }else{
            $this->ajaxReturn(['error'=>1]);
        }
    }
    public function goodsNewAction(){
        $goods_model = M('goods');
        $list = $goods_model
            ->where(['status'=>1,'deleted'=>0])
            ->order('created_at','desc')
            ->limit(CG('goods_new_size',4))
            ->select();
        if ($list){
            foreach ($list as $key=>$goods){
                $list[$key]['url'] = U('/goods/'.$goods['goods_id']);
            }
            $this->ajaxReturn(['error'=>0,'data'=>$list]);
        }else{
            $this->ajaxReturn(['error'=>1]);
        }
    }
    public function goodsAction(){
        $this->assign('goods_id',I('get.goods_id'));
        $this->display();
    }
    public function breadcrumbGoodsAction(){
        $model_goods = D('goods');
        $goods_id = I('request.goods_id');
        $list = $model_goods->getBreakCrumb($goods_id);
        if ($list){
            $this->ajaxReturn(['error'=>0,'data'=>$list]);
        }else{
            $this->ajaxReturn(['error'=>1]);
        }
    }
    public function galleriesAction(){
        $goods_id = I('request.goods_id');
        $g_model = M('gallery');
        $rows = $g_model
            ->where(['goods_id'=>$goods_id])
            ->select();
        if ($rows){
            $this->ajaxReturn(['error'=>0,'data'=>$rows]);
        }else{
            $this->ajaxReturn(['error'=>1]);
        }
    }
    public function goodsinfoAction(){
        $goods_id = I('request.goods_id');
        $goods_model = M('goods');
        $goods = $goods_model
            ->alias('g')
            ->field('g.*,ss.title')
            ->join('left join __STOCK_STATUS__ ss using(stock_status_id)')
            ->where(['status'=>1,'deleted'=>0,'goods_id'=>$goods_id])
            ->find();
        if ($goods){

            $product_list = M('Attribute')
                ->field('p.product_id,p.promoted,group_concat(a.attribute_title,":",ao.option_value) title')
                ->alias('a')
                ->join('left join __ATTRIBUTE_OPTION__ ao using(attribute_id)')
                ->join('left join __GOODS_ATTRIBUTE_OPTION__ gao using(attribute_option_id)')
                ->join('left join __PRODUCT_OPTION__ po using(goods_attribute_option_id)')
                ->join('left join __PRODUCT__ p using(product_id)')
                ->where(['p.goods_id'=>$goods['goods_id']])
                ->group('p.product_id')
                ->order('a.attribute_title')
                ->select();

        }
        $goods['product_list']=$product_list;

        if ($goods){
            $this->ajaxReturn(['error'=>0,'data'=>$goods]);
        }else{
            $this->ajaxReturn(['error'=>1]);
        }
    }
}