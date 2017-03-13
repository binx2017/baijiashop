<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017-2-22
 * Time: 下午 3:42
 */
namespace Back\Model;
use Think\Model;

class AdminModel extends Model
{

    protected  $patchValidate = true;
    protected $_validate = [
       //验证自己写
    ];
    protected $_auto= [
       //自动加载自己写
        ['salt' ,'mkSalt',self::MODEL_BOTH,'callback'],
        ['password' ,'mkPassword',self::MODEL_BOTH,'callback'],
       ['created_at','time',self::MODEL_INSERT,'function'],
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