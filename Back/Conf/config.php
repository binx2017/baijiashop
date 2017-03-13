<?php
return [
    'USER_AUTH_ON' => true,
    'USER_AUTH_TYPE' => 2,
    'USER_AUTH_KEY' =>'user_id',
    'USER_AUTH_GATEWAY'=>'/Back/Admin/login',
    'RBAC_ROLE_TABLE'=>'role',
    'RBAC_USER_TABLE'=>'role_user',
    'RBAC_ACCESS_TABLE'=>'access',
    'RBAC_NODE_TABLE'=>'node',

    //过滤不需要的验证
    'NOT_AUTH_MODULE'=>'Manager',
    'NOT_AUTH_ACTION'=>'index',
    ];