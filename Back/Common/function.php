<?php

function sortU($route,$param=[],$sort=[],$field=''){
    $param['field']= $field;
    $param['type'] = $sort['field']==$field && $sort['type']=='asc' ? 'desc':'asc';
    return U($route,$param);
}
function sortClass($sort,$field){
    return $sort['field']!=$field ?'':$sort['type'];
}