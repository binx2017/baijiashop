<?php
namespace Back\Controller;

use Common\Tools\Page;
use Think\Controller;

//use Think\Page;
class AdminController extends Controller
{

    public function setAction(){
        if (IS_POST){
        $user_id = I('post.user_id','');
        $model = D('Admin');
            if ($model->create()){
                if ($user_id === ''){
                    $user_id = $model->add();
                }else{
                    $model->save();
                }
                //成功
//                dump($_POST);die;
                $model_role_user = M('RoleUser');
                foreach (I('post.role_id',[]) as $role_id){
                    //一条一条加进数据库
                    $row = [
                        'role_id'=>$role_id,
                        'user_id' =>$user_id,
                    ];
                    $model_role_user->add($row,[],true);
                }
                $model_role_user->where(['user_id'=>$user_id,'role_id'=>['not in',I('post.role_id',[0])]])->delete();
                $this->redirect('list');
            }else{
                session('message',$model->getDbError());
                session('data',I('post'));
                $param =[];
                if ($user_id !== ''){
                    $param['user_id'] = $user_id;
                }
                $this->redirect('set',$param);
            }
        }else{
            $user_id = I('get.user_id','');
            $message = session('message');
            session('message',null);
            $this->assign('message',$message?$message:[]);
            $data = session('data');

            session('data',null);
            if(!$data && $user_id){
                $data = M('Admin')->find($user_id);
                unset($data['password']);
            }
            $this->assign('data',$data ? $data:[]);
            $this->assign('role_list',M('Role')->order('sort_number')->select());
            $this->assign('selected_role_list',M('RoleUser')->where(['user_id'=>$user_id])->getField('role_id',true));
            $this->display();
        }
    }

    public function listAction(){

        $model = M('Admin');

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

    public function loginAction(){
        if (IS_POST){
                $username = I('post.username');
                $password = I('post.password');
                $model_admin = M('Admin');
                $cond['username'] = $username;
                $admin = $model_admin->where($cond)->find();
                if ($admin && sha1($admin['salt'].$password) === $admin['password']){
                    unset($admin['password']);
                    unset($admin['salt']);
                    session('admin',$admin);
                    session(C('USER_AUTH_KEY'),$admin['user_id']);//RBAC
                    $this->redirect('Manager/index');
                }else{
                    session('message','登录信息错误');
                    $this->redirect('login');
                }
        }else{

            $message = session('message') ;
            session('message',null);
            $this->assign('message', $message );
            $this->display();
        }
    }

    public function changePwdAction(){
        echo 2;
        if (IS_POST){
            $user_id = I('post.user_id','');
            $model = D('Admin');
            if ($model->create()){
                if ($user_id === ''){
                    $user_id = $model->add();
                }else{
                    $model->save();
                }
                //成功
//                dump($_POST);die;
                $model_role_user = M('RoleUser');
                foreach (I('post.role_id',[]) as $role_id){
                    //一条一条加进数据库
                    $row = [
                        'role_id'=>$role_id,
                        'user_id' =>$user_id,
                    ];
                    $model_role_user->add($row,[],true);
                }
                $model_role_user->where(['user_id'=>$user_id,'role_id'=>['not in',I('post.role_id',[0])]])->delete();
                $this->redirect('list');
            }else{
                session('message',$model->getDbError());
                session('data',I('post'));
                $param =[];
                if ($user_id !== ''){
                    $param['user_id'] = $user_id;
                }
                $this->redirect('set',$param);
            }
        }else{

            $user_id = I('get.user_id','');
            $message = session('message');
            session('message',null);
            $this->assign('message',$message?$message:[]);
            $data = session('data');

            session('data',null);
            if(!$data && $user_id){
                $data = M('Admin')->find($user_id);
                unset($data['password']);
            }
            $this->assign('data',$data ? $data:[]);
            $this->assign('role_list',M('Role')->order('sort_number')->select());
            $this->assign('selected_role_list',M('RoleUser')->where(['user_id'=>$user_id])->getField('role_id',true));
            $this->display();
        }
    }
    public function ajaxAction(){
        $username = I('get.username');
        $password = I('get.password');
        $model_admin = M('Admin');
        $cond['username'] = $username;
        $admin = $model_admin->where($cond)->find();
        if ($admin && sha1($admin['salt'].$password) === $admin['password']){
            $this->ajaxReturn(['status'=>1]);
        }else{
            $this->ajaxReturn(['status'=>0]);
        }
    }
}