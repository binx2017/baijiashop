<?php
namespace Back\Controller;
use Think\Controller;
use Common\Tools\Page;
//use Think\Page;
class CategoryController extends Controller{

    public function setAction(){
        if (IS_POST){
        $category_id = I('post.category_id','');
        $model = D('Category');
            if ($model->create()){
                if ($category_id === ''){
                    $model->add();
                }else{
                    $model->save();
                }
                S([
                    'type'=>'redis',
                    'host'=> CG('redis_host','127.0.0.1'),
                    'port'=>CG('redis_port','6379'),
                ]);
                S('category_list',null);
                S('category_nested',null);
                $this->redirect('list');
            }else{
                session('message',$model->getDbError());
                session('data',I('post'));
                $param =[];
                if ($category_id !== ''){
                    $param['category_id'] = $category_id;
                }
                $this->redirect('set',$param);
            }
        }else{
            $category_id = I('get.category_id','');
            $message = session('message');
            session('message',null);
            $this->assign('message',$message?$message:[]);
            $data = session('data');
            session('data',null);
            if(!$data && $category_id){
                $data = M('Category')->find($category_id);
            }
            $this->assign('data',$data ? $data:[]);
            $this->assign('category_list',D('category')->getTree());
            $this->display();
        }
    }

    public function listAction(){

        $model = D('Category');
        $rows = $model->getTree();
//        dump($rows);die;
        $this->assign('rows',$rows);

        $this->display();
    }
    public function redisAction(){
        S([
            'type'=>'redis',
            'host'=> CG('redis_host','127.0.0.1'),
            'port'=>CG('redis_port','6379'),
        ]);
        S('category_list',null);
        S('category_nested',null);
    }
}