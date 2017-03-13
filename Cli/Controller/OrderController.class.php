<?php

namespace Cli\Controller;
use Think\Controller;

class OrderController extends Controller
{
    /**
     * 处理队列的方法!
     */
    public function processQueueAction()
    {

//         守护进程!
        $redis = new \Redis();
        $redis->connect(CG('redis_host', '127.0.0.1'), CG('redis_port', '6379'));
        $model_goods = M('Goods');
        $model_product = M('Product');
        $model_order = M('Order');
        $model_order_goods = M('OrderGoods');
        while(true) {
            // 获取队列中的订单
            $order_sn = $redis->rpop('order_queue');
            if (! $order_sn) continue;

            // 获取定单信息
            $order_info = $redis
                ->hget('order_hash', $order_sn);
            // 检测订单是否可以生成!
            // 库存即可!
            $order_info = unserialize($order_info);
            // 默认认为库存可用
            $flag = true;
            $order_goods_rows = [];
            $order_id = $model_order->getFieldByOrderSn($order_sn, 'order_id');

            foreach($order_info['goods_list'] as $goods) {
                // 需要进入order_goods表的数据
                $row = [
                    'order_id' => $order_id,
                    'goods_id' => $goods['goods_id'],
                    'product_id' => $goods['product_id'],
                    'buy_quantity' => $goods['buy_quantity'],
                    'buy_price' => $goods['price'],
                ];
                $order_goods_rows[] = $row;
                // 记录需要减少的库存

                $substract = [];
                if (! empty($goods['product_id']))
                {
                    $subrow = [
                        'type'=> 'product',
                        'cond' => ['product_id'=>$goods['product_id']],
                        'quantity' => $goods['buy_quantity']
                    ];
                    $substract[] = $subrow;
                    // 检测货品的库存
                    $quantity = $model_product->getFieldByProductId($goods['product_id'], 'product_quantity');
                }
                else
                {
                    // 商品
                    $quantity = $model_goods->getFieldByGoodsId($goods['goods_id'], 'quantity');
                    $subrow = [
                        'type'=> 'goods',
                        'cond' => ['goods_id'=>$goods['goods_id']],
                        'quantity' => $goods['buy_quantity']
                    ];
                    $substract[] = $subrow;
                }
                // 比较
                if ($goods['buy_quantity'] > $quantity) {
                    // 库存不足, 订单失败
                    $flag = false;// 失败!
                    break;
                }
            }
            // 判断订单校验结果状态
            if ($flag) {
                // 库存检测通过, 订单生成
                // 开启事务
                // M()->startTrans();
                // 删除 order_hash订单信息
                $redis->hdel('order_hash', $order_sn);
                // 订单入库, 更新订单状态
                $data = [
                    'order_status_id' => 2, // 确认
                    'ensure_time' => time(), // 确认时间
                ];
                $model_order->where(['order_sn'=>$order_sn])->save($data);

                // order_goods表, 插入记录
                $model_order_goods->addAll($order_goods_rows);

                // 扣减库存(大力度优惠, 集中的购物时间点, 紧俏商品)
                foreach($substract as $row) {
                    if ('goods' == $row['type']) {
                        $model_goods->where($row['cond'])->setDec('quantity', $row['quantity']);
                    } else {
                        $model_product->where($row['cond'])->setDec('product_quantity', $row['quantity']);
                    }
                }
//                $cond = '';
//                检测库存在处理前后是否一致!
//                if($cond) {
//                    M()->commit();
//                } else {
//                    M()->rollback();
//                }

                // 累加处理完毕的计时器
                $redis->incr('processed_number');

                echo 'order: ', $order_sn, ' Successed', "\n";
            } else {
                // 某些商品库存不足
                $order_info['process_status'] = 'error';
                $redis->hset('order_hash', $order_sn, serialize($order_info));
                echo 'order: ', $order_sn, ' Error', "\n";
            }

        }
    }

}