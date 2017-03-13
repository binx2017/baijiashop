<?php
namespace Back\Controller;
use Think\Controller;
use Common\Tools\Page;
//use Think\Page;
class PaymentController extends Controller{

    public function setAction(){
        if (IS_POST){
        $payment_id = I('post.payment_id','');
        $model = D('Payment');
            if ($model->create()){
                if ($payment_id === ''){
                    $model->add();
                }else{
                    $model->save();
                }
                $this->redirect('list');
            }else{
                session('message',$model->getDbError());
                session('data',I('post'));
                $param =[];
                if ($payment_id !== ''){
                    $param['payment_id'] = $payment_id;
                }
                $this->redirect('set',$param);
            }
        }else{
            $payment_id = I('get.payment_id','');
            $message = session('message');
            session('message',null);
            $this->assign('message',$message?$message:[]);
            $data = session('data');
            session('data',null);
            if(!$data && $payment_id){
                $data = M('Payment')->find($payment_id);
            }
            $this->assign('data',$data ? $data:[]);
            $this->display();
        }
    }

    public function listAction(){

        $model = M('Payment');

        //条件
        $cond = [];//默认条件
        $filter = [];
        $this->assign('filter',$filter);
        $sort['field'] = I('get.field','sort_number');
        $sort['type'] = I('get.type','asc');
        $order = "`{$sort['field']}`{$sort['type']}";
        $this->assign('sort',$sort);
        $rows = $model->where($cond)->order($order)->select();
        //数据表部分完成
        //文件部分
        $payment_path = APP_PATH.'Common/Payment/';
        $handle = opendir($payment_path);
        while($filename = readdir($handle)){
            if (!in_array($filename,['.','..'])){
                $payment_key_list[] = substr($filename,0,-10);
            }
        }
        closedir($handle);
        //遍历，从数据表中获取方式
        $payment_rows = [];
        foreach ($rows as $row) {
            if(in_array($row['key'],$payment_key_list)){
                $row['status'] = 'installed';//已安装
            }else{
                $row['status'] = 'deleted';//意外删除
            }
            $payment_rows[$row['key']] = $row;
        }
        //遍历所有文件的方式
        foreach ($payment_key_list as $payment_key){
            if (! isset($payment_rows[$payment_key])){
                $payment_rows[$payment_key] = [
                    'key'=> $payment_key,
                    'status'=> 'uninstall',
                ];
            }
        }

        $this->assign('rows', $payment_rows);
        $this->display();
    }
    public function installAction(){
        $key = I('get.key');
        $classname = 'Common\Payment\\'.$key;
        //实例化支付方式累
        //检测是否是标准的支付类（是否实现了IPayment接口）
        $rc= new \ReflectionClass($classname);
        if (!$rc->implementsInterface('Common\Itfc\IPayment')){
            $this->error('当前的插件结构不对',U('list'));
        }
        //反射类对象，代理类的实例化对象的功能
        $method = $rc->newInstance();
        //$method = new $classname;
        $rm = new \ReflectionMethod($classname,'get');
        $get = $rm->invoke($method);
        //$get = $method->get();
        M('Payment')->add([
            'key'=>$key,
            'title'=>$get,
            'enable'=>1,
            'promoted'=>0,
        ]);
        $this->redirect('list');
    }
    public function uninstallAction(){
        $key = I('get.key');
        $row = M('Payment')->where(['key'=>$key])->delete();

            $this->redirect('list');

    }
    public function deleteAction(){
        $key = I('get.key');
        $row = M('Payment')->where(['key'=>$key])->delete();

        $this->redirect('list');
    }
}