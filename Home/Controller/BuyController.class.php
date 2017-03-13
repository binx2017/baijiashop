<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017-3-9
 * Time: 下午 8:33
 */

namespace Home\Controller;

use Home\Cart\Cart;
use Think\Controller;

class BuyController extends Controller
{
    public function CartAddAction(){
       $cart =  Cart::instance();
       $cart->addGoods(I('request.goods_id'),I('request.quantity'),I('request.product_id'));
       $this->ajaxReturn(['error'=>0]);
    }
    public function CartInfoAction(){
        //获取到购物车中全部商品
        $type = I("request.type",'');
        $cart = Cart::instance();
        $goods_list = $cart->getGoodsList();
        // 购物车中所返回的, 仅仅包含商品id, 货品id, 购买数量, 加入购物车时间 , 几个数据
        // 整理成 前端需要的数据格式
        $model_goods = D('Goods');
        $rows = [];
        $total_price = 0; // 购物车中所有商品总价
        $total_quantity = 0; // 总购买数量
        foreach($goods_list as $goods_key => $goods) {
            // 获取每个商品详细信息
            if($type == 'checked' && $goods['checked'] == 0) continue;
            $goods['url'] = U('/goods/' . $goods['goods_id']);
            $goods['goods_key'] = $goods_key;
            $goods['buy_quantity'] = $goods['quantity'];

            // 累加购买数量
            $total_quantity += $goods['buy_quantity'];

            // 获取商品在数据表中的信息
            $goods_info = $model_goods
                ->field('name, image_thumb')
                ->find($goods['goods_id']);
            $goods = array_merge($goods, $goods_info);

            // 计算商品单价
            $goods['price'] = $model_goods->getPrice($goods['goods_id'], isset($goods['product_id']) ? $goods['product_id'] : 0);

            // 累加总价格
            $goods['total_price'] = $goods['price'] * $goods['buy_quantity'];// 单件商品的总价
            $total_price += $goods['total_price'];

            //获取货品信息
            //判断是否具有获取id
            if (isset($goods['product_id'])){
                //有货品
                $model_attribute  = M('Attribute');
                $product_title_row = $model_attribute
                    ->field('group_concat(a.attribute_title, ":", ao.option_value) product_title')
                    ->alias('a')
                    ->join('left join __ATTRIBUTE_OPTION__ ao using(attribute_id)')
                    ->join('left join __GOODS_ATTRIBUTE_OPTION__ gao using(attribute_option_id)')
                    ->join('left join __PRODUCT_OPTION__ po using(goods_attribute_option_id)')
                    ->where(['po.product_id'=>$goods['product_id']])
                    ->order('a.attribute_title')
                    ->find();
                $goods['product_title'] = $product_title_row['product_title'];
                $rows[] = $goods;

            }
        }
        if ($rows){
            $this->ajaxReturn([
                'error'=>0,
                'data' =>[
                    'goods_list'=>$rows,
                    'total_quantity'=>$total_quantity,
                    'total_price'=>$total_price,
                ]
            ]);
        }else{
            $this->ajaxReturn(['error'=>1]);
        }
    }
    public function cartAction(){
        session('target',['route'=>'/cart','param'=>[]]);
        $this->display();
    }
    public function cartUpdateAction(){
        $cart = Cart::instance();
        $goods_key = I('request.goods_key');
        $quantity = I('request.quantity');
//        dump(I('request.'));die;
        $res = $cart->updateQuantity($goods_key,$quantity);
        if ($res){
            $this->ajaxReturn(['error'=>0,'data'=>$res]);
        }else{
            $this->ajaxReturn(['error'=>1]);
        }

    }
    public function checkoutAction(){
        if (!session('member')){
            $this->redirect('/login');
        }
      $this->display();
    }
    public function checkGoodsAction(){
        $cart  = Cart::instance();
        $cart->setChecked(I('request.goods_keys',[]));
        $this->ajaxReturn(['error'=>0]);
    }
    public function paymentListAction(){
        $rows = M('payment')->where(['enabled'=>1])->order('sort_number')->select();
        if ($rows){
            $this->ajaxReturn(['error'=>0,'data'=>$rows]);
        }else{
            $this->ajaxReturn(['error'=>1]);
        }
    }
    /*
     * 加订单到队列
     * */
    public function orderGenerateAction(){
        $redis = new \Redis();
        $redis->connect(CG('redis_host','127.0.0.1'),CG('redis_port','6370'));

        //1收集订单信息
        $order_info = I('post.');
        $result = Cart::instance()->getfullGoodsList('checked');
        $order_info['goods_list'] = $result['rows'];
        $order_info['process_status'] = 'processing';
        //默认处于未处理的状态
        $order_info['number'] = $redis->incr('order_number');
        //当前前边还有多少订单需要处理（采用记录自己需要的方式）;
        //通常的策略就是:当前时间+顺序号
        $order_sn = date('YmdHis').mt_rand(100,999).mt_rand(100,999).mt_rand(100,999);
        //生成订单sn
        //记录订单信息
        $redis->hset('order_hash',$order_sn,serialize($order_info));
        M('order')->add([
            'member_id'=>session('member.member_id'),
            'order_sn'=> $order_sn,
            'order_status'=>1,
            'shipping_status_id'=>1,
            'payment_status_id'=>1
        ]);
        //选择记录到缓存中，使用redis的hash-table的结构
        //key:order_sn
        //value:订单信息
        $redis->lPush('order_queue',$order_sn);
        //响应
        $this->ajaxReturn(['error'=>0,'data'=>[
            'order_sn'=>$order_sn,
            'url'=>U('/orderResult',['order_sn'=>$order_sn])
        ]]);
    }
    public function orderResultAction(){
        $this->assign('order_sn',I('get.order_sn'));
        $this->display();
    }
    public function orderStatusAction(){
        $order_sn = I('request.order_sn');
        $redis = new \Redis();
        $redis->connect(CG('redis_host','127.0.0.1'),CG('redis_port','6370'));
        //获取订单
        $order_info = $redis->hget('order_hash',$order_sn);
        if($order_info){
            $order_info = unserialize($order_info);
            //订单信息还在，意味着，处理中或者失败
            if ('processing' == $order_info['process_status']){
                $data = [
                    'process_status'=>'processing',
                    'before_number'=>$order_info['number']-$redis->get('processed_number')-1,
                ];
            }elseif('error' == $order_info['process_status']){
                $data = [
                    'process_status'=>'error',
                    'error_info'=>'库存不足',
                ];
            }
        }else{
            //意味着处理成功
            $data = [
                'process_status' => 'success',
            ];
        }
        $this->ajaxReturn(['error'=>0,'data'=>$data]);
    }
}