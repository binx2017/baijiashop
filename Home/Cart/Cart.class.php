<?php
namespace Home\Cart;

/**
 * Class Cart 购物车类
 */
class Cart
{
    // 存储所购商品列表
    private $goods_list = [];


    /**
     * 添加商品
     */
    public function addGoods($goods_id, $quantity=1, $product_id=0)
    {
        // 判断当前商品是否存在与购物车中.
        // 先确定每个商品的唯一key
        $goods_key = $goods_id;
        if (0 != $product_id) $goods_key .= '-' . $product_id;

        // 确定商品是否存在
        if (isset($this->goods_list[$goods_key])) {
            // 商品存在, 数量增加即可
            $this->goods_list[$goods_key]['quantity'] += $quantity;
        } else {
            // 商品不存在, 加入新的
            $this->goods_list[$goods_key] = [
                'goods_id' => $goods_id,
                'quantity' => $quantity,
                'add_time' => time(),
                'checked' => 1,
            ];
            // 判断是否存在货品
            if (0 != $product_id) $this->goods_list[$goods_key]['product_id'] = $product_id;
        }

        return $this; // return 1, return true;
    }

    /**
     * 商品列表获取
     */
    public function getGoodsList()
    {
        return $this->goods_list;
    }

    public function getfullGoodsList($type='')
    {
        $goods_list = $this->getGoodsList();
        // 购物车中所返回的, 仅仅包含商品id, 货品id, 购买数量, 加入购物车时间 , 几个数据
        // 整理成 前端需要的数据格式
        $model_goods = D('Goods');
        $rows = [];
        $total_price = 0; // 购物车中所有商品总价
        $total_quantity = 0; // 总购买数量
        foreach($goods_list as $goods_key => $goods) {// goods_id, product_id
            if ('checked' == $type  && $goods['checked'] == 0) continue;
            // 获取每个商品详细信息
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

            // 获取货品信息
            // 判断是否具有货品id
            if (isset($goods['product_id'])){
                // 具有货品,货品的属性标签
                $model_attribute = M('Attribute');
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

        return [
            'rows' => $rows,
            'total_price' => $total_price,
            'total_quantity' => $total_quantity,
        ];
    }

    /**
     * 修改购买数量
     */
    public function updateQuantity($goods_key, $quantity)
    {
        if (isset($this->goods_list[$goods_key]))
            $this->goods_list[$goods_key]['quantity'] = $quantity;

        return $this;
    }

    /**
     * 同步
     */
    public function syncGoods()
    {
        // 获取cookie中商品信息
        $cookie_goods_list = cookie('cart_goods_list');
        // $this->goods_list;// 已经存在当前的商品, 从数据表中读取
        $cookie_goods_list = unserialize($cookie_goods_list);
        foreach($cookie_goods_list as $goods_key => $goods) {
            // 判断是否存在与当前的 购物车商品列表中
            if (! isset($this->goods_list[$goods_key])) {
                // 购物车中没有
                $this->goods_list[$goods_key] = $goods;
            } else {
                // 可选, 累加商品数量
                // $this->goods_list[$goods_key]['quantity'] += $goods['quantity'];
            }
        }
        return $this;
    }


    /**
     * 析构方法
     */
    public function __destruct()
    {
        // 序列化
        $goods_list_serialize = serialize($this->goods_list);// string

        // 判断会员是否登录
        if (session('member')) {
//            dump(session('member'));
            // 登录了, 有session中的member数据
            // 购物车商品 字符串, 入库
            $data = [
                'content' => $goods_list_serialize,
                'member_id' => session('member.member_id'),
            ];
            M('Cart')->add($data, [] , true);
        } else {
            // 未登录
            // 数据入cookie(会话数据)
            cookie('cart_goods_list', $goods_list_serialize, ['expire'=>PHP_INT_MAX-time()]);// setcookie('key', 'value', PHP_INT_MAX);
        }
    }

    /**
     * 构造方法
     */
    private function __construct()
    {
        // 判断当前用户是否登录
        if (session('member')) {
            // 登录了, 有session中的member数据
            // 从数据库获取, 未完待续
            $goods_list_serialize = M('Cart')->getFieldByMemberId(session('member.member_id'), 'content');
        } else {
            // 未登录
            // 从cookie中获取
            $goods_list_serialize = cookie('cart_goods_list');
        }
        // 执行反序列化后, 存储在$goods_list
        if ($goods_list_serialize) {
            $this->goods_list = unserialize($goods_list_serialize);
        } else {
            $this->goods_list = [];
        }
    }

    // 记录所选中的keys
//    private $checked_keys = [];
    public function setChecked($goods_keys_list = []) {
//        $this->checked_keys = $goods_keys_list;
        foreach($this->goods_list as $key=>$goods) {
            $this->goods_list[$key]['checked'] = in_array($key, $goods_keys_list) ? 1 : 0;
        }
    }

    // 单例化 购物车对象
    private static $instance;
    private function __clone()
    {
    }
    public static function instance()
    {
        if (! self::$instance instanceof self) {
            self::$instance = new self();
        }
        return self::$instance;
    }

}