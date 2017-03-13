<?php
namespace Back\Controller;

use Common\Tools\Page;
use back\Model;
use Think\Controller;
use Think\Image;
use Think\Upload;

//use Think\Page;
class GoodsController extends Controller {

    public function setAction(){
        if (IS_POST){
//            dump($_POST);die;
        $goods_id = I('post.goods_id','');
        $model = D('Goods');
            if ($model->create()){
                if ($goods_id === ''){
                   $goods_id =  $model->add();
                }else {
                    $model->save();
                }
                //1 维护分类与商品的关联
                $model_category_goods = M('categoryGoods');
                $model_category_goods->where(['goods_id' => $goods_id])->delete();
                $category_id_list = I('post.category_list', []);
//                    dump($category_id_list);
//                    die;
                if (!empty($category_id_list)) {
                    $row = array_map(function ($category_id) use ($goods_id) {
                        return [
                            'category_id' => $category_id,
                            'goods_id' => $goods_id,
                        ];
                    }, $category_id_list);
                    $model_category_goods->addAll($row);
                }
                // 2商品相册管理
                $model_gallery = D('Gallery');
                // 遍历
                foreach (I('post.galleries', []) as $row) {
                    $row['goods_id'] = $goods_id;
                    // create时, 传递处理好的数据,否者会认为是post数据
                    if ($model_gallery->create($row)) {
                        // 存在主键, 则更新
                        if (isset($row['gallery_id'])) {
                            $model_gallery->save();
                        } else {
                            // 否则则添加
                            $model_gallery->add();
                        }
                    }
                }
                //3 商品属性关系管理
                //遍历所有的商品属性值
                $model_goods_attribute = M('GoodsAttribute');
//dump($_POST['goods_attribute']);die;
                foreach (I('post.goods_attribute',[]) as $attribute) {
                    $attribute['goods_id'] = $goods_id;
                    $attribute['product_option'] = isset($attribute['product_option']) ? 1 : 0;
                    //判断value是否为数组，如果是，不进入到goods_attribute

                    if (is_array($attribute['value'])){
                        //剔除attribute中的value值
                        $value_list = $attribute['value'];
                        unset($attribute['value']);
                    }elseif(!isset($attribute['value'])){
                       //是否存在value元素
                        $value_list = [0];
                    }
//                dump($attribute);die;
                    //先将数据插入到goods_attribute表
                    if ($model_goods_attribute->create($attribute)){
                        //判断当前是否存在主键
                        if (isset($attribute['goods_attribute_id'])){
                            //是，进行更新操作
                            $model_goods_attribute->save();
                            $goods_attribute_id = $attribute['goods_attribute_id'];
                        }else{
                            //不是，执行添加
                            $model_goods_attribute->create($attribute);
                            $goods_attribute_id = $model_goods_attribute->add($attribute);
                        }
                    }

                   //对数组型value处理
                    //入 goods_attribute_option
//                dump($value_list);die;
                    if (isset($value_list)){
                        $model_goods_attribute_option = M('GoodsAttributeOption');
                        foreach($value_list as $attribute_option_id){
                            $row = $model_goods_attribute_option
                                     ->where(['goods_attribute_id'=>$goods_attribute_id,'attribute_option_id'=>$attribute_option_id])
                                     ->find();
                            if ($row){
                                //不再处理，继续处理下一个选择项
                                continue;
                            }
                            //当前选择的之前没有选择过
                            $row = [
                                'goods_attribute_id'=>$goods_attribute_id,
                                'attribute_option_id'=>$attribute_option_id,
                            ];
                            $model_goods_attribute_option->add($row);
                        }
                        //删除之前，但是当前没有使用的
                        //找到不在本次提交的attribute_option_id中的选项预设值，删除
                        $model_goods_attribute_option->where([
                            'goods_attribute_id'=>$goods_attribute_id,
                            'attribute_option_id'=>['not in',$value_list]
                        ])->delete();
                    }

                }

                $this->redirect('list');
                }else{

                session('message',$model->getDbError());
                session('data',I('post'));
                $param =[];
                if ($goods_id !== ''){
                    $param['goods_id'] = $goods_id;
                }
                $this->redirect('set',$param);
            }
        }else{
            $goods_id = I('get.goods_id','');
            $message = session('message');
            session('message',null);
            $this->assign('message',$message?$message:[]);
            $data = session('data');
            session('data',null);
            if(!$data && $goods_id){
                $data = M('Goods')->find($goods_id);
            }

            $this->assign('data',$data ? $data:[]);
            $this->assign('sku_list',D('sku')->order('sort_number')->select());
            $this->assign('tax_list',D('tax')->order('sort_number')->select());
            $this->assign('stock_status_list',M('stockStatus')->order('sort_number')->select());
            $this->assign('length_unit_list',M('lengthUnit')->order('sort_number')->select());
            $this->assign('weight_unit_list',M('weightUnit')->order('sort_number')->select());
            $this->assign('brand_list',M('brand')->order('sort_number')->select());
            $this->assign('category_list',D('category')->getTree());
             $this->assign('category_linked',M('categoryGoods')->where(['goods_id'=>$goods_id])->getField('category_id',true));
            $this->assign('gallery_list',M('Gallery')->where(['goods_id'=>$goods_id])->select());
           //类型数据
            $this->assign('type_list',M('Type')->order('sort_number')->select());
            //编辑时展示属性数据
            if ($goods_id){
                $attribute_list = D('attribute')
                        ->relation(true)
                    ->alias('a')
                    ->field('a.*,it.type_title')
                    ->join('left join __INPUT_TYPE__ it using(input_type_id)')
                    -> where(['type_id'=>$data['type_id']])
                    ->select();
                //$attribute_list,当前类型下，全部的属性
                //遍历全部的attribute_list

                $model_goods_attribute = D('GoodsAttribute');
                foreach ($attribute_list as $key=>$attribute){
                    //每条属性，找到与属性关联的商品数据
                    $goods_attribute = $model_goods_attribute
                        ->relation(true)
                        ->where(['goods_id'=>$goods_id,'attribute_id'=>$attribute['attribute_id']])
                        ->find();
//                    dump($goods_attribute);die;
                    //当可以检索到对应的商品属性关联时，才做后续的处理
                    if ($goods_attribute){
                        //为了便于判断选中的元素，将attribute_option_id独立管理
                        //变成selected_attribute_option_id_list
                        $goods_attribute['selected_attribute_option_id_list'] = array_map(function ($row){
                            return $row['attribute_option_id'];
                        },$goods_attribute['selected_option_list']);
                        $attribute_list[$key]['goods_attribute'] = $goods_attribute;
                    }
                }

                $this->assign('attribute_list',$attribute_list);

            }
            $this->display();
        }
    }

    public function listAction(){

        $model = M('Goods');

        //条件
        $cond = [];//默认条件
        $title = $filter['title'] = I('get.title','');
        if ($title !== ''){
            $cond['title'] = ['like',$title.'%'];
        }
        $site = $filter['site']= I('get.site','');
        if ($site !== ''){
            $cond['site']  =['like','%'.$site.'%'];
        }
        $this->assign('filter',$filter);
        //分页
        $limit = 2;
        $total = $model->where($cond)->count();
        $page = new Page($total,$limit);
        //排序部分 //排序(保证每个表都有sort_number)
        $sort['field'] = I('get.field','sort_number');
        $sort['type'] = I('get.type','asc');
        $order = "`{$sort['field']}`{$sort['type']}";
        $this->assign('sort',$sort);
        //完成查询
        $rows = $model->where($cond)->order($order)->limit($page->firstRow.','.$limit)->select();
        //展示
        $this->assign('rows', $rows);
        $page->setConfig('header','显示开始 %PAGE_START% 到 %PAGE_END% 之 %TOTAL_ROW% (总 %TOTAL_PAGE%页)');
        $page->setConfig('theme','<div class="col-sm-6 text-left"><ul class="pagination">%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END%</ul></div><div class="col-sm-6 text-right">%HEADER%</div>');
        $page->setConfig('prev','<');
        $page->setConfig('next','>');
        $page->setConfig('first','|<');
        $page->setConfig('last','>|');
        $page_html = $page->show();
        $this->assign('page_html',$page_html);
        $this->display();
    }
    public function checkAction(){
        $field = I('request.field');
        switch($field){
            case 'sku_id':
            echo D('Goods')->checkSkuId(I('request.sku_id')) ? 'true' : 'false';
//                echo M('sku')->find(I('request.sku_id')) ? 'true' : 'false';
            break;
            case 'tax_id':
            echo D('Goods')->checkTaxId(I('request.tax_id')) ? 'true' : 'false';
            break;
            case 'stock_status_id':
            echo D('Goods')->stockStatusId(I('request.stock_status_id')) ? 'true' : 'false';
            break;
            case 'length_unit_id':
            echo D('Goods')->stockStatusId(I('request.length_unit_id')) ? 'true' : 'false';
            break;
        }
    }

    public function uploadAction()
    {
        $type = I('request.type','goods');
        //完成图像上传
        $upload = new Upload();
        switch($type){
            case 'description':
            case 'goods':
                $upload->savePath = 'Goods/';
                $name = 'image';
                break;
            case 'gallery':
                $upload->savePath = 'Gallery/';
                $name = 'galleries';
                break;
        }
        $upload->rootPath = './Upload/';

        $upload->exts = ['git','jpg','jpeg','png'];
        $upload->maxSize = 1*1024*1024;
        $info = $upload->uploadOne($_FILES[$name]);

        if ($info){
            $data['error'] = 0;
            $data['image'] = $info['savepath'].$info['savename'];
            $image = new Image();
            $image->open($upload->rootPath.$info['savepath'].$info['savename']);
            //保存
            $thumb_root = './Public/Thumb/';
            $save_path = date('Y-m-d').'/';
            if (!is_dir($thumb_root.$save_path)){
                mkdir($thumb_root.$save_path);
            }
            switch ($type){
                case 'goods':
                    $image->thumb(CG('goods_image_width',263),CG('goods_image_height',298),Image::IMAGE_THUMB_FILLED);
                    $image->save($thumb_root.$save_path.$info['savename']);
                    $data['image_thumb'] = $save_path.$info['savename'];
                    break;
                case 'gallery':
                    $image->thumb(CG('goods_image_width',800),CG('goods_image_height',800),Image::IMAGE_THUMB_FILLED);
                    $image->save($thumb_root.$save_path.'big-'.$info['savename']);
                    $image->thumb(CG('goods_image_width',300),CG('goods_image_height',300),Image::IMAGE_THUMB_FILLED);
                    $image->save($thumb_root.$save_path.'medium-'.$info['savename']);
                    $image->thumb(CG('goods_image_width',30),CG('goods_image_height',30),Image::IMAGE_THUMB_FILLED);
                    $image->save($thumb_root.$save_path.'small-'.$info['savename']);
                    $data['image_big'] = $save_path.'big-'.$info['savename'];
                    $data['image_medium'] = $save_path.'medium-'.$info['savename'];
                    $data['image_small'] = $save_path.'small-'.$info['savename'];
                    break;
            }
        }else{
            $data['error'] = 1;
            $data['errorInfo'] = $upload->getError();
        }
        $this->ajaxReturn($data);
    }

    public function removeAction(){
        $gallery_id = I('request.gallery_id',null);
        $image_small = I('request.image_small',null);
        if (!is_null($gallery_id)){
            $model=M('gallery');
            $model->find($gallery_id);
            @unlink('./Upload/'.$model->image);
            @unlink('./Public/Thumb/'.$model->image_small);
            @unlink('./Public/Thumb/'.$model->image_medium);
            @unlink('./Public/Thumb/'.$model->image_big);
            $model->delete();
        }
        if (!is_null($image_small)){
            @unlink('./Public/Thumb/'.$image_small);
            @unlink('./Public/Thumb/'.str_replace('small-','medium-',$image_small));
            @unlink('./Public/Thumb/'.str_replace('small-','big-',$image_small));
            @unlink('./Upload/Gallery/'.str_replace('small-','',$image_small));
        }
        $this->ajaxReturn(['error'=>0]);
    }
    public function multiAction(){
        $selected = I('post.selected',[]);
        $operate = 'del';
        if (empty($selected)){
            $operate = '';
        }
        switch ($operate){
            case 'del':
                $goods_model = M('goods');
                $goods_model->find('goods_id');

                M('goods')->where(['goods_id'=>['in',$selected]])->delete();
                break;
        }
        $this->redirect('list');
    }
    public function attributeAction(){
        $rows =
           D('attribute')
                ->relation(true)
                ->alias('a')
                ->field('a.*,it.type_title')
                ->join('left join __INPUT_TYPE__ it using(input_type_id)')
                -> where(['type_id'=>I('request.type_id')])
                ->select();
        if (is_array($rows)){
            $this->ajaxReturn(['error'=>0,'data'=>$rows]);
        }else{
            $this->ajaxReturn(['error'=>1]);
        }
    }
    public function productAction(){
        $goods_id = I('get.goods_id');
        // 获取全部选项属性 及 属性的信息
        $model_goods_attribute = M('GoodsAttribute');
        $cond['goods_id'] = $goods_id;
        $cond['product_option'] = '1';
//        dump($cond);die;
        $option_list = $model_goods_attribute
            ->field('ga.*, a.attribute_title')
            ->alias('ga')
            ->join('left join __ATTRIBUTE__ a using(attribute_id)')
            ->where($cond)
            ->order('a.sort_number')
            ->select();
//        dump($model_goods_attribute->_sql());die;
        $model_goods_attribute_option =M('GoodsAttributeOption');
        foreach ($option_list as $key=>$option)
        {
            $value_list =$model_goods_attribute_option
                ->field('gao.*,ao.option_value')
                ->alias('gao')
                ->join('left join __ATTRIBUTE_OPTION__ ao using(attribute_option_id)')
                ->where(['goods_attribute_id'=>$option['goods_attribute_id']])
                ->select();
            $option_list[$key]['value_list'] = $value_list;
        }

        $this->assign('option_list',$option_list);
        $this->assign('goods_id',$goods_id);
        //获取当前商品的货品
        $model_product = M('product');
        $product_list = $model_product->where(['goods_id'=>$goods_id])->select();
        $model_product_option = M('productOption');
        foreach ($product_list as $key=>$product){
            $product_list[$key]['value_list']=$model_product_option
                ->field('ao.option_value')
                ->alias('po')
                ->join('left join __GOODS_ATTRIBUTE_OPTION__ gao using(goods_attribute_option_id)')
                ->join('left join __ATTRIBUTE_OPTION__ ao using(attribute_option_id)')
                ->join('left join __ATTRIBUTE__ a using(attribute_id)')
                ->where(['product_id'=>$product['product_id']])
                ->order('a.sort_number')
                ->select();
        }
//        dump($product_list);die;
        $this->assign('product_list',$product_list);
        $this->display();
    }
    public function productSetAction(){
        $model_product = M('product');
        $model_product_option = M('productOption');
        $goods_id = I('post.goods_id');
        foreach (I('post.product',[]) as $product){
            $product['goods_id'] = $goods_id;
            $product['promoted'] = isset($product['promoted'])? '1':'0';
            $product['enabled'] = isset($product['enabled'])? '1':'0';
            if (isset($product['product_id'])){
                $model_product->save($product);
            }else{
                $product_id = $model_product->add($product);
                $rows = array_map(function ($goods_attribute_option_id) use ($product_id){
                    return [
                        'goods_attribute_option_id'=>$goods_attribute_option_id,
                        'product_id'=>$product_id
                    ];
                },$product['goods_attribute_option_id']);
                $model_product_option->addAll($rows);
            }
        }
        $this->redirect('product',['goods_id'=>$goods_id]);
    }
}