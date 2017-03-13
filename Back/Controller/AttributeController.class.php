<?php
namespace Back\Controller;
use Think\Controller;
use Common\Tools\Page;
//use Think\Page;
class AttributeController extends Controller{

    public function setAction(){
        if (IS_POST){

        $attribute_id = I('post.attribute_id','');
        $model = D('Attribute');
            if ($model->create()){
                if ($attribute_id === ''){
                    $attribute_id = $model->add();
                }else{
                    $model->save();
                }
                //属性添加成功
                //是否是select-multi类型
                $input_type = M('InputType')->getFieldByInputTypeId(I('post.input_type_id'),'type_title');
                if ($input_type === 'select-multi'){
                    $option_list =I('post.option');
                    $model_attribute_option = D('AttributeOption');
                    foreach($option_list as $option){
                        $option['attribute_id'] = $attribute_id;
                        if ($model_attribute_option->create($option)) $model_attribute_option->add('',[],true);
                    }
                }
                $this->redirect('list');
            }else{
                session('message',$model->getDbError());
                session('data',I('post'));
                $param =[];
                if ($attribute_id !== ''){
                    $param['attribute_id'] = $attribute_id;
                }
                $this->redirect('set',$param);
            }
        }else{
            $attribute_id = I('get.attribute_id','');
            $message = session('message');
            session('message',null);
            $this->assign('message',$message?$message:[]);
            $data = session('data');
            session('data',null);
            if(!$data && $attribute_id){
                $data = M('Attribute')->find($attribute_id);
            }
            $this->assign('data',$data ? $data:[]);
            $this->assign('type_list',M('Type')->order('sort_number')->select());
            $input_type_list = [];
            foreach (M('InputType')->order('sort_number')->select() as $row){
                if (in_array($row['type_title'],['text','select-multi'])){
                    $input_type_list[]=$row;
                }
            }
            $this->assign('input_type_list',$input_type_list);
            //如果是编辑操作
            if ($attribute_id) {
                // 如果是选项类型, 则显示预设值数据列表
                $input_type = M('InputType')->where(['input_type_id'=>$data['input_type_id']])->find();

                if ('select-multi' === $input_type['type_title']) {
                    $this->assign('input_type',$input_type);
                    $this->assign('option_list', M('AttributeOption')->where(['attribute_id'=>$attribute_id])->order('sort_number')->select());
                }
            }
            $this->display();
        }
    }

    public function listAction(){

        $model = M('Attribute');

        //条件
        $cond = [];//默认条件
        $filter=[];
        $type_id = $filter['type_id'] = I('get.type_id','0');
        if ($type_id !== '0'){
            $cond['type_id'] = $type_id;
        }

        $this->assign('filter',$filter);
        //分页
        $limit = 3;
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
        //类型
        $this->assign('type_list',M('type')->order('sort_number')->select());
        $this->display();
    }
}