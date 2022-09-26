<?php
/**
 * +----------------------------------------------------------------------
 * | 新闻管理控制器
 * +----------------------------------------------------------------------
 */
namespace app\admin\controller;
use think\Db;
use think\facade\Request;

class Product extends Base
{
    protected $validate = 'Product';

    //列表
    public function pindex(){

        //条件筛选
        $keyword = Request::param('keyword');
        $cate_id = Request::param('cate_id');
        $type_id = Request::param('type_id');
        $zhandian_id = Request::param('zhandian_id');
        $status = Request::param('status');
        $group = Request::param('group');

        $start = Request::param('start');
        $end = Request::param('end');
        $start = strtotime(date($start));
        $end = strtotime(date($end));
        $items = Request::param('items');
        //全局查询条件
        $where=[];

        if(!empty($cate_id)){
            $where[]=['p.cate_id', '=', $cate_id];
        } 
        if(!empty($type_id)){
            $where[]=['p.type_id', '=', $type_id];
        }
        if(!empty($status)){
            $where[]=['p.status', '=', $status];
        }
        if(!empty($group)){
            if($group == 1){
                $where[]=['p.zhandian_uid', '=', 0];
            }else{
                $where[]=['p.zhandian_uid', '>', 0];
            }
        }
        
        $uinfo = Db::name('users')->where('id',$this->admin_id)->find();
        
        //超级管理员
        if($uinfo['group_id'] == 1 || $uinfo['group_id'] == 2  || $uinfo['group_id'] == 3){
            if(!empty($zhandian_id)){
                
                //查询当前站点及所属下级站点
                $cate = Db::name('cate')->select();
                $xz = getChildsId($cate,$zhandian_id);
                
                $itemz = '';
                foreach($xz as $valxz){
                    $itemz .= $valxz['id'].',';
                }
                $idxzs = $itemz.$zhandian_id;
                $where[] = ['p.zhandian_id','in',$idxzs];
                
            }
        }else{
            if(!empty($zhandian_id)){
                $where[]=['p.zhandian_id', '=', $zhandian_id];
            }else{
                $where[]=['p.zhandian_id', 'in', $uinfo['ruless']];
            }
        }
                
        if(isset($start)&&$start!=""&&isset($end)&&$end=="")
        {
            $where[] = ['p.create_time','>=',$start];
        }
        if(isset($end)&&$end!=""&&isset($start)&&$start=="")
        {
            $where[] = ['p.create_time','<=',$end];
        }
        if(isset($start)&&$start!=""&&isset($end)&&$end!="")
        {
            $where[] = ['p.create_time','between',[$start,$end]];
        }

        //显示数量
        $pageSize = Request::param('page_size') ? Request::param('page_size') : config('page_size');
        $page = Request::param('page') ? Request::param('page') : 1;

        $a = $page-1;
        $b = $a * $pageSize;


        if(!empty($keyword)){
            $whra[]=['username|mobile', 'like', '%'.$keyword.'%'];

            $uuid= Db::name('users')->field('id')->where($whra)->buildSql(true);
            //根据所有人的id in查询所有的成绩

            if(!empty($items)){
                $items = trim($items,'^');
                $itemids = explode('^',$items);
                $ids = '';
                foreach($itemids as $val){
                    
                    $item = sellist($ids,$val);
                    
                    $itemq = '';
                    foreach($item as $vals){
                        $itemq .= $vals['product_id'].',';
                    }
                    
                    $itemq = rtrim($itemq,',');
                    
                    $whrs[] = ['product_id','in',$itemq];
                }
                
                $itemid = Db::name('product_relation')->field('product_id')->where($whrs)->buildSql(true);

                $list = Db::name('product')
                    ->alias('p')
                    ->leftJoin('product_cate cate','p.cate_id = cate.id')
                    ->leftJoin('product_type type','p.type_id = type.id')
                    ->field('p.*,cate.title as cate_name,type.title as type_name')
                    ->order('p.create_time desc,p.id DESC')
                    ->where($where)
                    ->where('p.uid','exp','In '.$uuid)
                    ->where('p.id','exp', 'In '.$itemid)
                    ->select();
            }else{
                $list = Db::name('product')
                    ->alias('p')
                    ->leftJoin('product_cate cate','p.cate_id = cate.id')
                    ->leftJoin('product_type type','p.type_id = type.id')
                    ->field('p.*,cate.title as cate_name,type.title as type_name')
                    ->order('p.create_time desc,p.id DESC')
                    ->where($where)
                    ->where('p.uid','exp','In '.$uuid)
                    ->select();
            }
        }else{
            if(!empty($items)){
                
                //筛选
                $items = trim($items,'^');
                $itemids = explode('^',$items);
                
                $ids = '';
                    
                foreach($itemids as $val){
                  
                    $item = sellist($ids,$val);
                    
                    $itemq = '';
                    foreach($item as $vals){
                        $itemq .= $vals['product_id'].',';
                    }
                    
                    $itemq = rtrim($itemq,',');
                    
                    $whrs[] = ['product_id','in',$itemq];
                }
                $itemid = Db::name('product_relation')->field('product_id')->where($whrs)->buildSql(true);

                $list = Db::name('product')
                    ->alias('p')
                    ->leftJoin('product_cate cate','p.cate_id = cate.id')
                    ->leftJoin('product_type type','p.type_id = type.id')
                    ->field('p.*,cate.title as cate_name,type.title as type_name')
                    ->order('p.create_time desc,p.id DESC')
                    ->where($where)
                    ->where('p.id','exp','In '.$itemid)
                    ->select();
            }else {
                $list = Db::name('product')
                    ->alias('p')
                    ->leftJoin('product_cate cate', 'p.cate_id = cate.id')
                    ->leftJoin('product_type type', 'p.type_id = type.id')
                    ->field('p.*,cate.title as cate_name,type.title as type_name')
                    ->order('p.create_time desc,p.id DESC')
                    ->where($where)
                    ->select();
            }
        }
        
        foreach ($list as $keys => $vals){
            $group_name = self::select_name($vals['zhandian_id']);
                $arr = explode('/',$group_name);
                $arrs = array_reverse($arr);
                $group_list = implode('/',$arrs);
                $group_list = ltrim($group_list,'/');
                $list[$keys]['zhandian_name'] = $group_list;
                $list[$keys]['username'] = Db::name('users')->where('id',$vals['uid'])->value('username');
                $list[$keys]['mobile'] = Db::name('users')->where('id',$vals['uid'])->value('mobile');
                $list[$keys]['submit_username'] = Db::name('users')->where('id',$vals['zhandian_uid'])->value('username');
                $list[$keys]['submit_group_id'] = Db::name('users')->where('id',$vals['zhandian_uid'])->value('group_id');
        }


        $data_rt['total'] = count($list);
        $list = array_slice($list,$b,$pageSize);
        $data_rt['data'] = $list;
        //调取列表
        $rs_arr['status'] = 200;
		$rs_arr['msg'] = 'success';
		$rs_arr['data'] = $data_rt;
		return json_encode($rs_arr,true);
		exit;


    }
    
    public function select_name($id){
        $str = '';
        $whr['id'] = $id;
        $info = Db::name('cate')->where($whr)->find();
        $str .= $info['title'].'/';
        
        if($id != 1){
            $str .= self::select_name($info['parentid']);
        }
        
        return $str;
    }
    

    public function pdetail(){
        $id = Request::param('id');
        
        //全局查询条件
        $where=[];
        $wheres=[];

        if(!empty($id)){
            $where[]=['pr.product_id', '=', $id];
            $wheres[]=['product_id', '=', $id];
        }else{
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请选择设备';
    		return json_encode($rs_arr,true);
    		exit;
        }
        
        //查询参数列表
        $itemlist = Db::name('product_relation')
                ->alias('pr')
                ->leftJoin('spec pc','pr.spec_id = pc.id')
                ->leftJoin('spec_item pt','pr.result = pt.id')
                ->field('pr.*,pc.title as spec_name,pt.item as spec_item_name')
                ->where($where)
                ->order('pr.id asc')
                ->select();
        //查询修改记录
        $updlist = Db::name('product_update')
                ->where($wheres)
                ->order('create_time desc')
                ->select();
                
                
        $data_rt['itemlist'] = $itemlist;
        $data_rt['updlist'] = $updlist;
        
        $rs_arr['status'] = 200;
		$rs_arr['msg'] = 'success';
		$rs_arr['data'] = $data_rt;
		return json_encode($rs_arr,true);
		exit;
    }
    
    
    
    //列表
    public function useless_list(){

        //条件筛选
        $keyword = Request::param('keyword');
        $cate_id = Request::param('cate_id');
        $type_id = Request::param('type_id');
        $zhandian_id = Request::param('zhandian_id');
        $status = Request::param('status');
        
        $start = Request::param('start');
        $end = Request::param('end');
        $start = strtotime(date($start));
        $end = strtotime(date($end));
        $items = Request::param('items');
        //全局查询条件
        $where=[];

        if(!empty($cate_id)){
            $where[]=['p.cate_id', '=', $cate_id];
        } 
        if(!empty($type_id)){
            $where[]=['p.type_id', '=', $type_id];
        }
        if(!empty($status)){
            $where[]=['pa.status', '=', $status];
        }
        
        $uinfo = Db::name('users')->where('id',$this->admin_id)->find();
        
        //超级管理员
        if($uinfo['group_id'] == 1 || $uinfo['group_id'] == 2  || $uinfo['group_id'] == 3){
            if(!empty($zhandian_id)){
                
                //查询当前站点及所属下级站点
                $cate = Db::name('cate')->select();
                $xz = getChildsId($cate,$zhandian_id);
                
                $itemz = '';
                foreach($xz as $valxz){
                    $itemz .= $valxz['id'].',';
                }
                $idxzs = $itemz.$zhandian_id;
                $where[] = ['p.zhandian_id','in',$idxzs];
                
            }
        }else{
            if(!empty($zhandian_id)){
                $where[]=['p.zhandian_id', '=', $zhandian_id];
            }else{
                $where[]=['p.zhandian_id', 'in', $uinfo['ruless']];
            }
        }
                
        if(isset($start)&&$start!=""&&isset($end)&&$end=="")
        {
            $where[] = ['pa.create_time','>=',$start];
        }
        if(isset($end)&&$end!=""&&isset($start)&&$start=="")
        {
            $where[] = ['pa.create_time','<=',$end];
        }
        if(isset($start)&&$start!=""&&isset($end)&&$end!="")
        {
            $where[] = ['pa.create_time','between',[$start,$end]];
        }

        //显示数量
        $pageSize = Request::param('page_size') ? Request::param('page_size') : config('page_size');
        $page = Request::param('page') ? Request::param('page') : 1;

        $a = $page-1;
        $b = $a * $pageSize;


        if(!empty($keyword)){
            $whra[]=['username|mobile', 'like', '%'.$keyword.'%'];

            $uuid= Db::name('users')->field('id')->where($whra)->buildSql(true);
            //根据所有人的id in查询所有的成绩

            if(!empty($items)){
                $items = trim($items,'^');
                $itemids = explode('^',$items);
                $ids = '';
                foreach($itemids as $val){
                    
                    $item = sellist($ids,$val);
                    
                    $itemq = '';
                    foreach($item as $vals){
                        $itemq .= $vals['product_id'].',';
                    }
                    
                    $itemq = rtrim($itemq,',');
                    
                    $whrs[] = ['product_id','in',$itemq];
                }
                
                $itemid = Db::name('product_relation')->field('product_id')->where($whrs)->buildSql(true);
                
                $list = Db::name('product_apply')
                    ->alias('pa')
                    ->leftJoin('product p','pa.product_id = p.id')
                    ->field('pa.*,pa.create_time as add_time,pa.status as useless_status,p.type_id,p.cate_id,p.zhandian_uid,p.zhandian_id')
                    ->order('add_time asc,pa.status asc')
                    ->where($where)
                    ->where('p.uid','exp','In '.$uuid)
                    ->where('p.id','exp', 'In '.$itemid)
                    ->select();
            }else{
                
                $list = Db::name('product_apply')
                    ->alias('pa')
                    ->leftJoin('product p','pa.product_id = p.id')
                    ->field('pa.*,pa.create_time as add_time,pa.status as useless_status,p.type_id,p.cate_id,p.zhandian_uid,p.zhandian_id')
                    ->order('add_time asc,pa.status asc')
                    ->where($where)
                    ->where('p.uid','exp','In '.$uuid)
                    ->select();
            }
        }else{
            if(!empty($items)){
                
                //筛选
                $items = trim($items,'^');
                $itemids = explode('^',$items);
                
                $ids = '';
                    
                foreach($itemids as $val){
                  
                    $item = sellist($ids,$val);
                    
                    $itemq = '';
                    foreach($item as $vals){
                        $itemq .= $vals['product_id'].',';
                    }
                    
                    $itemq = rtrim($itemq,',');
                    
                    $whrs[] = ['product_id','in',$itemq];
                }
                $itemid = Db::name('product_relation')->field('product_id')->where($whrs)->buildSql(true);
                
                $list = Db::name('product_apply')
                    ->alias('pa')
                    ->leftJoin('product p','pa.product_id = p.id')
                    ->field('pa.*,pa.create_time as add_time,pa.status as useless_status,p.type_id,p.cate_id,p.zhandian_uid,p.zhandian_id')
                    ->order('add_time asc,pa.status asc')
                    ->where($where)
                    ->where('p.id','exp','In '.$itemid)
                    ->select();
            }else{
                
                $list = Db::name('product_apply')
                    ->alias('pa')
                    ->leftJoin('product p','pa.product_id = p.id')
                    ->field('pa.*,pa.create_time as add_time,pa.status as useless_status,p.type_id,p.cate_id,p.zhandian_uid,p.zhandian_id')
                    ->order('add_time asc,pa.status asc')
                    ->where($where)
                    ->select();
            }
        }
        
        foreach ($list as $keys => $vals){
            $group_name = self::select_name($vals['zhandian_id']);
                $arr = explode('/',$group_name);
                $arrs = array_reverse($arr);
                $group_list = implode('/',$arrs);
                $group_list = ltrim($group_list,'/');
                $list[$keys]['zhandian_name'] = $group_list;
                $list[$keys]['username'] = Db::name('users')->where('id',$vals['uid'])->value('username');
                $list[$keys]['mobile'] = Db::name('users')->where('id',$vals['uid'])->value('mobile');
                $list[$keys]['submit_username'] = Db::name('users')->where('id',$vals['zhandian_uid'])->value('username');
                $list[$keys]['submit_group_id'] = Db::name('users')->where('id',$vals['zhandian_uid'])->value('group_id');
                $list[$keys]['cate_name'] = Db::name('product_cate')->where('id',$vals['cate_id'])->value('title');
                $list[$keys]['type_name'] = Db::name('product_type')->where('id',$vals['type_id'])->value('title');
                
                $list[$keys]['apply_content_list'] = explode("\n",$vals['apply_content']);
               
                
        }


        $data_rt['total'] = count($list);
        $list = array_slice($list,$b,$pageSize);
        $data_rt['data'] = $list;
        //调取列表
        $rs_arr['status'] = 200;
		$rs_arr['msg'] = 'success';
		$rs_arr['data'] = $data_rt;
		return json_encode($rs_arr,true);
		exit;

    }
    
    //查看报废详情
    public function useless_detail(){
        $id = Request::param('id');
        if(empty($id)){
            $rs_arr['status'] = 201;
            $rs_arr['msg'] = '请选择报废记录';
            return json_encode($rs_arr,true);
            exit;
        }else{
            $where['p.id'] = $id;
        }
        
         //$where['p.uid'] = $this->admin_id;
        
        $painfo = Db::name('product_apply')->where('id',$id)->where('reply_uid',$this->admin_id)->find();
       
        if($painfo){
            $where['p.id'] = $painfo['product_id'];
        
            $pinfo = Db::name('product')
                ->alias('p')
                ->leftJoin('product_cate pc','p.cate_id = pc.id')
                ->leftJoin('product_type pt','p.type_id = pt.id')
                ->leftJoin('users u','p.uid = u.id')
                ->leftJoin('cate c','p.zhandian_id = c.id')
                ->leftJoin('product_flow pf','p.id = pf.product_id')
                ->field('p.id,p.status,p.uid,p.is_new,p.leixing,p.reason,p.collect_time,p.cate_id,p.type_id,p.zhandian_id,p.json,pc.title as catename,pt.title as typename,u.username as name,c.title as zhandian_name,pf.apply_status as apply_status,pf.apply_content as apply_content')
                ->where($where)
                ->find();
            
            if($pinfo['status'] == 1){
                $pinfo['status_name'] = '在用';
            }else{
                $pinfo['status_name'] = '闲置';
            }  
            if($pinfo['is_new'] == 1){
                $pinfo['is_new_name'] = '全新';
            }else{
                $pinfo['is_new_name'] = '非全新';
            }  
            if($pinfo['leixing'] == 1){
                $pinfo['leixing_name'] = '使用';
            }else{
                $pinfo['leixing_name'] = '管理';
            }  
            if($painfo['status'] == 1){
                $pinfo['useless_state_name'] = '待审核';
            }else if($painfo['status'] == 2){
                $pinfo['useless_state_name'] = '已通过';
            }else{
                $pinfo['useless_state_name'] = '已驳回';
            }
            
            
            $whr1['pr.product_id'] = $painfo['product_id'];
            $item_list = Db::name('product_relation')
                ->alias('pr')
                ->leftJoin('spec pc','pr.spec_id = pc.id')
                ->leftJoin('spec_item pt','pr.result = pt.id')
                ->field('pr.*,pc.title as spec_name,pc.leixing,pt.item as spec_item_name')
                ->where($whr1)
                ->order('pr.id asc')
                ->select();
            foreach ($item_list as $key => $val){
                if($val['leixing'] == 2){
                    $item_list[$key]['spec_item_name'] = $val['result'];
                }
            }
        
            $pinfo['item_list'] = $item_list;
            $pinfo['useless_reason'] = $painfo['apply_reason'];
            $pinfo['useless_content'] = $painfo['apply_content'];
            $pinfo['useless_status'] = $painfo['status'];
            $pinfo['ctime'] = date("Y-m-d",$pinfo['collect_time']);
            $pinfo['apply_content_list'] = explode("\n",$painfo['apply_content']);
            
            $rs_arr['status'] = 200;
            $rs_arr['msg'] = 'success';
            $rs_arr['data'] = $pinfo;
            return json_encode($rs_arr,true);
            exit;
        }else{
            $rs_arr['status'] = 201;
            $rs_arr['msg'] = '暂无记录';
            return json_encode($rs_arr,true);
            exit;
        }
        
        
    }
    
    //处理报废申请
    public function useless_apply(){
        $id = Request::param('id');
        $status = Request::param('status');
        $reply_content = Request::param('reply_content');
        
        if(!empty($id)){
            $where[] = ['id','=',$id];
        }
        $where[] = ['reply_uid','=',$this->admin_id];
        
        $info = Db::name('product_apply')->where($where)->find();

        if(!$info){
            $rs_arr['status'] = 201;
            $rs_arr['msg'] = '无权限';
            return json_encode($rs_arr,true);
            exit;
        }else{
            if($info['status'] == 2){
                $rs_arr['status'] = 201;
                $rs_arr['msg'] = '已通过！';
                return json_encode($rs_arr,true);
                exit;
            }elseif($info['status'] == 3){
                $rs_arr['status'] = 201;
                $rs_arr['msg'] = '已驳回！';
                return json_encode($rs_arr,true);
                exit;
            }else{
                $data['status'] = $status;
                $data['reply_content'] = $reply_content;
                if(Db::name('product_apply')->where($where)->update($data)){
                    if($status == 2){
                        $dataz['status'] = 3;
                        $dataz['useless_time'] = time();
                        Db::name('product')->where('id',$info['product_id'])->update($dataz);
                    }
                    $rs_arr['status'] = 200;
                    $rs_arr['msg'] = 'success';
                    return json_encode($rs_arr,true);
                    exit;
                }else{
                    $rs_arr['status'] = 201;
                    $rs_arr['msg'] = 'fail';
                    return json_encode($rs_arr,true);
                    exit;
                }  
            }
        }
    }
    
     //单一设备报废记录
    public function useless_apply_list(){
        $id = Request::param('id');
        
        $where = [];
        if(empty($id)){
            $rs_arr['status'] = 201;
            $rs_arr['msg'] = '请选择产品';
            return json_encode($rs_arr,true);
            exit;
        }else{
            $where[] = ['pa.product_id','=',$id];
        }

        $list = Db::name('product_apply')
            ->alias('pa')
            ->leftJoin('users u','pa.reply_uid = u.id')
            ->field('pa.*,u.username as reply_name')->where($where)->order('update_time desc')->select();
   
        $rs_arr['status'] = 200;
        $rs_arr['msg'] = 'success';
        $rs_arr['data'] = $list;
        return json_encode($rs_arr,true);
        exit;
    }
}
