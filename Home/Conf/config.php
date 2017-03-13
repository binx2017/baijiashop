<?php
return array(
	//'配置项'=>'配置值'
    //'配置项'=>'配置值'
    'DEFAULT_CONTROLLER' => 'Shop',
    'DEFAULT_ACTION'    => 'index',

    // URL路由相关
    'URL_ROUTER_ON' => true,// 开启路由
    'URL_ROUTE_RULES' => [
        'index' => 'Shop/index',
        'catNested' => 'Shop/catNested',
        'goodsNew' => 'Shop/goodsNew',
        'goods/:goods_id'=>'shop/goods',
        'breadcrumbGoods'=>'shop/breadcrumbGoods',
        'galleries'=>'shop/galleries',
        'goodsinfo'=>'shop/goodsinfo',
        'CartAdd' =>'Buy/CartAdd',
        'CartInfo' =>'Buy/CartInfo',
        'cart' =>'Buy/cart',
        'cartUpdate' =>'Buy/cartUpdate',
        'checkout' =>'Buy/checkout',
        'checkGoods' =>'Buy/checkGoods',
        'paymentList' =>'Buy/paymentList',
        'orderGenerate' => 'Buy/orderGenerate',
        'orderResult'   => 'Buy/orderResult',
        'orderStatus'   => 'Buy/orderStatus',

        'register' =>'Member/register',
        'login' =>'Member/login',
        'center' =>'Member/center',
        'addressAdd' =>'Member/addressAdd',
        'addressList' =>'Member/addressList',
        'regionList' =>'Member/regionList',

    ],
);