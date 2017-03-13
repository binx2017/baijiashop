<?php
namespace Back\Controller;
use Think\Controller;
use Common\Tools\Page;
//use Think\Page;
class SettingController extends Controller{

    public function setAction(){
        if (IS_POST){
                $model_setting = M('Setting');
                $setting_list = I('post.setting');
                foreach ($setting_list as $setting_id=>$values){
                    if (is_array($values)){
                        $values = implode(',',$values);
                    }
                    $model_setting->save([
                        'setting_id'=>$setting_id,
                        'value' => $values,
                    ]);
                }
                $this->redirect('set');

        }else{
            $group_setting_list = D('setting')->groupSetting();
//            dump($group_setting_list);
//            die;
            $this->assign('group_setting_list',$group_setting_list);
            $this->display();
        }
    }

    public function listAction(){

        $model = M('Setting');

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
}