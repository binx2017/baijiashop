<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017-3-9
 * Time: 下午 10:58
 */

namespace Home\Model;


use Think\Model;

class MemberModel extends Model
{
    protected  $patchValidate = true;
    protected $_validate = [
        //验证自己写
        ['password','confirm','两次输入的密码不一致',self::EXISTS_VALIDATE,'confirm'],
    ];
    protected $_auto= [
        //自动加载自己写
        ['password_salt' ,'mkSalt',self::MODEL_BOTH,'callback'],
        ['password' ,'mkPassword',self::MODEL_BOTH,'callback'],
        ['sort_number','0'],
        ['created_at','time',self::MODEL_INSERT,'function'],
        ['register_time','time',self::MODEL_INSERT,'function'],
        ['updated_at','time',self::MODEL_BOTH,'function'],
    ];
    protected function mkSalt(){
        $salt = substr(md5(time()),0,5);
        $this->salt = $salt;
        return $salt;

    }
    protected function mkPassword($value){
        return sha1($this->salt.$value);
    }
}