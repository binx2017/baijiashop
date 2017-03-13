<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017-3-6
 * Time: 下午 7:23
 */

namespace Back\Controller;


use Org\Util\Rbac;
use Think\Controller;

class CommonController extends Controller
{
    public function _initialize(){

        C('RBAC_ROLE_TABLE',C('DB_PREFIX').C('RBAC_ROLE_TABLE'));
        C('RBAC_ACCESS_TABLE',C('DB_PREFIX').C('RBAC_ACCESS_TABLE'));
        C('RBAC_USER_TABLE',C('DB_PREFIX').C('RBAC_USER_TABLE'));
        C('RBAC_NODE_TABLE',C('DB_PREFIX').C('RBAC_NODE_TABLE'));
        Rbac::checkLogin();
        if(!Rbac::AccessDecision()){
            $this->redirect('Manager/index');
        }
            $this->assign('access',Rbac::getAccessList(session(C('USER_AUTH_KEY'))));
    }
}