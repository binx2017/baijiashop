<?php
namespace Back\Controller;
use Think\Controller;
use Common\Tools\Page;
//use Think\Page;
class RoleController extends Controller{

    public function setAction(){
        if (IS_POST){
        $id = I('post.id','');
        $model = D('Role');
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
                $data = M('Role')->find($id);
            }
            $this->assign('data',$data ? $data:[]);
            $this->display();
        }
    }

    public function listAction(){

        $model = M('Role');

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
        $limit = 4;
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
    public function privilegeAction(){
        if (IS_POST){
//            dump($_POST);die;
            $role_id = I('post.id');
            $role_id_list = I('post.node_id');
            $access_model = M("access");
            $access_model->where(['role_id'=>$role_id])->delete();
            $rows = array_map(function ($node_id) use($role_id){
                return [
                     'node_id'=>$node_id,
                     'role_id'=>$role_id,
                     'level'=>'3',
                ];
            },$role_id_list);
            $access_model->addAll($rows);
//            dump($role_id);die;
            $this->redirect('privilege',['id'=>$role_id]);
        }else{

        }
        $this->assign('node_nested',D('Node')->getNodeNested());
        $this->assign('id',I('get.id'));
//        dump(M('access')->where(['role_id'=>I('get.id')])->getField('node_id',true));die;
        $this->assign('selected_list',M('access')->where(['role_id'=>I('get.id')])->getField('node_id',true));
        $this->display();

    }
}