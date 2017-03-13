<?php
namespace Back\Controller;
use Think\Controller;
use Common\Tools\Page;
//use Think\Page;
class NodeController extends Controller{

    public function setAction(){

        if (IS_POST){
//            dump($_POST);die;
        $id = I('post.id','');
        $model = D('Node');
            if ($model->create()){
                if ($id === ''){
                    $model->add();
                }else{
                    $model->save();
                }
                $this->redirect('list');
            }else{
                session('message',$model->getDbError());
                session('data',I('post'));
                $param =[];
                if ($id !== ''){
                    $param['id'] = $id;
                }
                $this->redirect('set',$param);
            }
        }else{
            $id = I('get.id','');
            $message = session('message');
            session('message',null);
            $this->assign('message',$message?$message:[]);
            $data = session('data');
            session('data',null);
            if(!$data && $id){
                $data = M('Node')->find($id);
            }
            $this->assign('data',$data ? $data:[]);
            $this->assign('parent_list',D('Node')->getParentTree());
            $this->display();
        }
    }

    public function listAction(){

        $model = M('Node');

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
        $limit = 5;
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
    public function redisAction(){
        S([
            'type'=>'redis',
            'host'=> CG('redis_host','127.0.0.1'),
            'port'=>CG('redis_port','6379'),
        ]);
        S('Node_list',null);
        $this->redirect('set');
    }
}