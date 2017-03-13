<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017-3-9
 * Time: 下午 10:54
 */

namespace Home\Controller;


use Home\Cart\Cart;
use Think\Controller;

class MemberController extends Controller
{
        public function registerAction(){
            if (IS_POST){
                $model_member = D('member');
                if ($model_member->create()){
                    $member_id = $model_member->add();

                    session('member',$model_member->field('member_id,nickname,register_time')->find($member_id));

                    $cart = Cart::instance();
                    $cart->syncGoods();
                    $target = session('target');
                    if ($target) {
                        session('target', null);
                        // session中存在重定向的目标地址
                        $this->redirect($target['route'], $target['param']);
                    } else {
                        $this->redirect('/center');// 假设重定向到用户中心
                    }
                }
            }else{
                $this->display();
            }
        }
    public function loginAction()
    {
        if (IS_POST) {
            $telephone = I('post.telephone');
            $password = I('post.password');
            // 先利用电话获取用户
            $model_member = M('Member');
            $row = $model_member->where(['telephone'=>$telephone])->find();
            if ($row && $row['password'] === sha1($row['password_salt'] . $password)) {
                // 校验通过
                session('member', [
                    'member_id' => $row['member_id'],
                    'nickname' => $row['nickname'],
                    'register_time' => $row['register_time'],
                ]);

                // 同步购物车
                $cart = Cart::instance();
                $cart->syncGoods();// 同步商品, async(异步)

                $target = session('target');
                if ($target) {
                    session('target', null);
                    // session中存在重定向的目标地址
                    $this->redirect($target['route'], $target['param']);
                } else {
                    $this->redirect('/center');// 假设重定向到用户中心
                }
            } else {
                $this->redirect('login');
            }
        } else {
            $this->display();
        }
    }

    public function centerAction(){
            $this->display();
        }
    public function addressAddAction(){
        $model_member = D('Address');
//        dump($_POST);die;
        $_POST['is_default'] = 1;
        $_POST['member_id'] = session('member.member_id');
        if ($model_member->create()){
            $address_id = $model_member->add();
            $cond = [
                'member_id' => session('member.member_id'),
                'is_default' => 1,
                'address_id' => $address_id,
            ];
            $model_member->where($cond)->save(['is_default'=>0]);
            $this->ajaxReturn(['error'=>0]);
        }else{
            $this->ajaxReturn(['error'=>1]);
        }
    }
    public function addressListAction(){
        if (!session('member')){
            $this->redirect('/login');
        }
        $rows = M('Address')
            ->field('a.*, one.title one_title, two.title two_title, three.title three_title')
            ->alias('a')
            ->join('left join __REGION__ one ON a.region_one_id=one.region_id')
            ->join('left join __REGION__ two ON a.region_two_id=two.region_id')
            ->join('left join __REGION__ three ON a.region_three_id=three.region_id')
            ->where(['member_id'=>session('member.member_id')])
            ->select();
        if ($rows){
            $this->ajaxReturn(['error'=>0,'data'=>$rows]);
        }else{
            $this->ajaxReturn(['error'=>1]);
        }
    }
    public function regionListAction(){
        $parent_id = I('request.parent_id');
        $rows = M('region')->where(['parent_id'=>$parent_id])->select();
        if($rows){
            $this->ajaxReturn(['error'=>0,'data'=>$rows]);
        }else{
            $this->ajaxReturn(['error'=>1]);
        }
    }
}