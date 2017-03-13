<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017-2-23
 * Time: 下午 9:00
 */

namespace Back\Controller;


use Think\Controller;

class CodeController extends Controller
{
    public function setAction(){
        if (IS_POST){
            $table = I('post.table');
            $comment = I('post.comment');
            //拼凑表名
            $model_name = $controller_name =implode(array_map('ucfirst',explode('_',$table)));
            $model = M($model_name);
            //获取表的字段
            $fields = I('post.fields');

            $pk_field = $model->getPk();
            //替换生成控制器代码
            $template_file = APP_PATH.'Back/CodeTemplate/Controller.template';
            $template = file_get_contents($template_file);
            $search = ['%CONTROLLER%','%MODEL%','%PK_FIELD%'];
            $replace = [$controller_name,$model_name,$pk_field];
            $content = str_replace($search,$replace,$template);

            //写入控制器类文件
            $controller_file = APP_PATH.'Back/Controller/'.$controller_name.'Controller.class.php';
            file_put_contents($controller_file,$content);
            echo $controller_file,'生成完毕<br>';
            //替换生成模型代码
            $template_file = APP_PATH.'Back/CodeTemplate/Model.template';
            $template = file_get_contents($template_file);
            $search = ['%MODEL%'];
            $replace = [$model_name];
            $content = str_replace($search,$replace,$template);

            //写入模型类文件
            $model_file = APP_PATH.'Back/Model/'.$model_name.'Model.class.php';
            file_put_contents($model_file,$content);
            echo '模型',$model_file,'生成完毕<br>';
        //确保模板目录存在
            $template_path = APP_PATH.'Back/View/'.$controller_name;
            if (!is_dir($template_path)){
                mkdir($template_path);
            }
            //生成list 模板
            //处理字段相关任务
            $field_head_list = $field_value_list ='';
            foreach ($fields as $field=>$options){
                if (isset($options['list'])){
                    //需要展示到列表中

                    if (isset($options['sort'])){
                        $template_file = APP_PATH.'Back/CodeTemplate/fieldHeadSort.template';
                    }else{
                        $template_file = APP_PATH.'Back/CodeTemplate/fieldHead.template';
                    }
                    $template = file_get_contents($template_file);
                    $search = ['%FIELD%','%FIELD_COMMENT%'];
                    $replace = [$options['name'],$options['comment']];
                    $content = str_replace($search,$replace,$template);
                    $field_head_list .= $content;
                }
                if (isset($options['list'])){
                    $template_file = APP_PATH.'Back/CodeTemplate/fieldValue.template';
                    $template = file_get_contents($template_file);
                    $search = ['%FIELD%'];
                    $replace = [$options['name']];
                    $content = str_replace($search,$replace,$template);
                    $field_value_list .= $content;
                }
            }
            $template_file = APP_PATH.'Back/CodeTemplate/list.template';

            $template = file_get_contents($template_file);

            $search = ['%COMMENT%','%PK_FIELD%','%FIELD_HEAD_LIST%','%FIELD_VALUE_LIST%'];
            $replace = [$comment,$pk_field,$field_head_list,$field_value_list];
            $content = str_replace($search,$replace,$template);
            $list_file = $template_path.'/'.'list.html';
            file_put_contents($list_file,$content);
            echo '列表模板',$list_file,'生成完毕<br>';

            //生成set模板
            //1 替换生成子模板
            $field_set_list = '';

            foreach ($fields as $field => $options){
                if (isset($options['set'])){
                    //需要被设置
                    $template_file = APP_PATH.'Back/CodeTemplate/fieldSet.template';
                    $template = file_get_contents($template_file);
                    $search = ['%FIELD%','%FIELD_COMMENT%'];
                    $replace = [$options['name'],$options['comment']];
                    $content= str_replace($search,$replace,$template);
                    $field_set_list .= $content;
                }
            }
            //整体模板
            $template_file = APP_PATH.'Back/CodeTemplate/set.template';
            $template = file_get_contents($template_file);
            $search = ['%COMMENT%','%PK_FIELD%','%FIELD_SET_LIST%'];
            $replace = [$comment,$pk_field,$field_set_list];
            $content = str_replace($search,$replace,$template);
            $set_file = $template_path.'/'.'set.html';
            file_put_contents($set_file,$content);
            echo '列表模板',$set_file,'生成完毕<br>';
        }else{
            $this->display();
        }
    }
    public function ajaxAction(){
        $table = I('get.table');

        $model = M($table);
        $field = $model->getDbFields();
        $pk_field =$model->getPk();
        //获取表的字段

        $this->ajaxReturn(['fields'=>$field,'pk_field'=>$pk_field]);

    }
}