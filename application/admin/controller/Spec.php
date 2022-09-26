<?php
/**
 * +----------------------------------------------------------------------
 * | 新闻管理控制器
 * +----------------------------------------------------------------------
 */
namespace app\admin\controller;
use think\Db;
use think\facade\Request;

//实例化默认模型
use app\common\model\Spec as M;

class Spec extends Base
{
    protected $validate = 'Spec';

    //列表
    public function index(){
        //条件筛选
        $keyword = Request::param('keyword');
        $type_id = Request::param('type_id');
        //全局查询条件
        $where=[];
        if(!empty($keyword)){
            $where[]=['title', 'like', '%'.$keyword.'%'];
        }
        if(!empty($type_id)){
            $where[]=['type_id', '=', $type_id];
        }

        $where[] = ['is_delete', '=' ,1];

        //显示数量
        $pageSize = Request::param('page_size') ? Request::param('page_size') : config('page_size');
        $page = Request::param('page') ? Request::param('page') : 1;

        $a = $page-1;
        $b = $a * $pageSize;

        //调取列表
        $list = Db::name('spec')
            ->order('sort asc,id asc')
            ->where($where)
            ->select();
        foreach ($list as $key => $val){
            $list[$key]['item'] = Db::name('spec_item')->order('id asc')->where('spec_id',$val['id'])->select();
        }

        $data_rt['total'] = count($list);
        $list = array_slice($list,$b,$pageSize);
        $data_rt['data'] = $list;

        $rs_arr['status'] = 200;
		$rs_arr['msg'] = 'success';
		$rs_arr['data'] = $data_rt;
		return json_encode($rs_arr,true);
		exit;
    }

    //添加保存
    public function addPost(){
        $data = Request::param();

        $validate = new \app\common\validate\Spec;
        if (!$validate->scene('add')->check($data)) {
            // 验证失败 输出错误信息
            $rs_arr['status'] = 201;
            $rs_arr['msg'] = $validate->getError();
            return json_encode($rs_arr,true);
            exit;
        }else{
            $m = new M();
            $result =  $m->addPost($data);
            if($result['error']){
                $rs_arr['status'] = 500;
        		$rs_arr['msg'] = $result['msg'];
        		return json_encode($rs_arr,true);
        		exit;
            }else{
                $rs_arr['status'] = 200;
		        $rs_arr['msg'] = $result['msg'];
        		return json_encode($rs_arr,true);
        		exit;
            }
        }
    }

    //修改保存
    public function editPost(){
        $data = Request::param();

        $validate = new \app\common\validate\Spec;
        if (!$validate->scene('edit')->check($data)) {
            // 验证失败 输出错误信息
            $rs_arr['status'] = 201;
            $rs_arr['msg'] = $validate->getError();
            return json_encode($rs_arr,true);
            exit;
        }else{
            $m = new M();
            $result = $m->editPost($data);
            if($result['error']){
                $rs_arr['status'] = 500;
        		$rs_arr['msg'] = $result['msg'];
        		return json_encode($rs_arr,true);
        		exit;
            }else{
                $rs_arr['status'] = 200;
		        $rs_arr['msg'] = $result['msg'];
        		return json_encode($rs_arr,true);
        		exit;
            }
        }
    }

    //删除
    public function del(){
        $data = Request::param();

        $validate = new \app\common\validate\Spec;
        if (!$validate->scene('del')->check($data)) {
            // 验证失败 输出错误信息
            $rs_arr['status'] = 201;
            $rs_arr['msg'] = $validate->getError();
            return json_encode($rs_arr,true);
            exit;
        }else{
            $m = new M();
            $datas['id'] = $data['id'];
            $datas['is_delete'] = 2;
            $result = $m->editPost($datas);
            if($result['error']){
                $rs_arr['status'] = 500;
                $rs_arr['msg'] = $result['msg'];
                return json_encode($rs_arr,true);
                exit;
            }else{
                $rs_arr['status'] = 200;
                $rs_arr['msg'] = $result['msg'];
                return json_encode($rs_arr,true);
                exit;
            }
        }
    }


}
