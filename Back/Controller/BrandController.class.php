<?php
namespace Back\Controller;
use Think\Controller;
use Common\Tools\Page;
use Think\Upload;

//use Think\Page;
class BrandController extends Controller{
    public function multiAction(){
        $selected = I('post.selected',[]);
        $operate = 'del';
        if (empty($selected)){
            $operate = '';
        }
        switch ($operate){
            case 'del':
                M('Brand')->where(['brand_id'=>['in',$selected]])->delete();
                break;
        }
        $this->redirect('list');
    }
    public function setAction(){
        if (IS_POST){
        $brand_id = I('post.brand_id','');
        $model = D('Brand');
        $upload = new Upload();
        $upload->rootPath = './Upload/';
        $upload->savePath = 'Brand/';
        $upload->exts = ['gif','jpg','jpeg','png'];
        $upload->maxSize = 1*1024*1024;
        $logo_info = $upload->uploadOne($_FILES['logo']);
        $_POST['logo'] = $logo_info ? ($logo_info['savepath'].$logo_info['savename']): '';
        if ($brand_id){
            if ($_POST['logo']==''){
                unset($_POST['logo']);
            }else{
                $logo = $model->getFieldByBrandId($brand_id,'logo');
                @unlink($upload->rootPath.$logo);
            }
        }
//        dump($logo_info);
//        die;
            if ($model->create()){
                if ($brand_id === ''){
                    $model->add();
                }else{
                    $model->save();
                }
                $this->redirect('list');
            }else{
                session('message',$model->getDbError());
                session('data',I('post'));
                $param =[];
                if ($brand_id !== ''){
                    $param['brand_id'] = $brand_id;
                }
                $this->redirect('set',$param);
            }
        }else{
            $brand_id = I('get.brand_id','');
            $message = session('message');
            session('message',null);
            $this->assign('message',$message?$message:[]);
            $data = session('data');
            session('data',null);
            if(!$data && $brand_id){
                $data = M('Brand')->find($brand_id);
            }
            $this->assign('data',$data ? $data:[]);
            $this->display();
        }
    }
    public function editAction(){
        if (IS_POST){
            $model = D('Brand');
            if ($model->create()){
                $model->save();
                $this->redirect('list');
            }
        }else{
            $message = session('message');
            session('message',null);
            $this->assign('message',$message?$message:[]);
            $data = session('data');
            session('data',null);
            if(!$data){
                $brand_id = I('get.brand_id');
                $data = M('Brand')->find($brand_id);
            }
               $this->assign('data',$data);
               $this->display();
        }
    }
    public function addAction(){
         if (IS_POST){
            $model = D('Brand');

            if ($model->create()){
                $id = $model->add();
                $this->redirect('list');
             }else{

                session('message',$model->getError());
                session('data',I('post.'));

                $this->redirect('add');
             }
         }else{
             $message = session('message');
             session('message',null);
             $this->assign('message',$message?$message:[]);
             $data = session('data');
             session('data',null);
             $this->assign('data',$data);
             $this->display();
         }
    }
    public function ajaxAction(){
        $title=I('request.title');
        $cond['title'] = $title;
        $brand_id = I('request.brand_id','');
        if ($brand_id !== ''){
            $cond['brand_id'] = ['neq',$brand_id];
        }
        $row= M('Brand')->where($cond)->find();
        echo $row ? 'false': 'true';
    }
    public function listAction(){

        $model = M('Brand');

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
        //排序部分 //排序
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
}