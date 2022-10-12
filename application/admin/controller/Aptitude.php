<?php
namespace app\admin\controller;
use think\Db;
use think\facade\Request;

use app\common\model\RegisterCredential as RC;
use app\common\model\Brand as B;


class Aptitude extends Base
{
    protected $validate = 'Aptitude';
    
    //获取公司列表
    public function company_list(){
        
        $leixing = Request::param('leixing');
        
        $list = Db::name('company')->where('leixing',$leixing)->order('sort asc')->select();
        if(count($list) > 0){
            $rs_arr['status'] = 200;
    		$rs_arr['msg'] = 'success';
    		$rs_arr['data'] = $list;
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = 'filed';
    		return json_encode($rs_arr,true);
    		exit;
        }
    }
    
    public function user_list(){
        
        $group_id = Request::param('group_id');
        
        $list = Db::name('users')->field('id,username')->where('group_id',$group_id)->order('username asc')->select();
        if(count($list) > 0){
            $rs_arr['status'] = 200;
    		$rs_arr['msg'] = 'success';
    		$rs_arr['data'] = $list;
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = 'filed';
    		return json_encode($rs_arr,true);
    		exit;
        }
    }
    
    //注册证列表
    public function register_list(){

        //条件筛选
        $keyword = Request::param('keyword');
        $name = Request::param('name');
        $leibie = Request::param('leibie');
        $datetype = Request::param('datetype');
        $start = Request::param('start');
        $end = Request::param('end');
        $status = Request::param('status');
        //全局查询条件
        $where=[];

        if(!empty($keyword)){
            $where[]=['rc.bianhao|rc.principal_name|rc.product_name', 'like', '%'.$keyword.'%'];
        }
        if(!empty($name)){
            $where[]=['rc.name', 'like', '%'.$name.'%'];
        }
        if(!empty($leibie)){
            $where[]=['rc.leibie', '=', $leibie];
        }
         
        if(!empty($datetype)){
            if(isset($start)&&$start!=""&&isset($end)&&$end=="")
            {
                $where[] = ['rc.'.$datetype,'>=',$start];
            }
            if(isset($end)&&$end!=""&&isset($start)&&$start=="")
            {
                $where[] = ['rc.'.$datetype,'<=',$end];
            }
            if(isset($start)&&$start!=""&&isset($end)&&$end!="")
            {
                $where[] = ['rc.'.$datetype,'between',[$start,$end]];
            }
        }

        //显示数量
        $pageSize = Request::param('page_size') ? Request::param('page_size') : config('page_size');
        $page = Request::param('page') ? Request::param('page') : 1;

        $a = $page-1;
        $b = $a * $pageSize;
        
        $list = Db::name('register_credential')
            ->alias('rc')
            ->leftJoin('users u','rc.principal = u.id')
            ->field('rc.*,u.username as username')
            ->order('rc.end_time desc,rc.validity_time DESC')
            ->where($where)
            ->select();
            
        foreach($list as $key => $val){
            $time = date('Y-m-d',time());
            //根据日期类型去判断天数
            if($val['reminder_type'] == 1){
                $reminderday = $val['reminder_time'];
            }elseif($val['reminder_type'] == 2){
                $reminderday = $val['reminder_time']*30;
            }else{
                $reminderday = $val['reminder_time']*365;
            }
                    
            if($val['end_time'] != '0000-00-00'){
                if($val['end_time'] < $time){
                    $list[$key]['status'] = 1;
                    $list[$key]['number'] = -intval((strtotime($time)-strtotime($val['end_time']))/86400);
                }else{
                    $remind_time = strtotime($val['end_time'])-($reminderday*86400);
                    if($remind_time < time()){
                        $list[$key]['status'] = 2;
                        $list[$key]['number'] = intval((time()-$remind_time)/86400);
                    }else{
                        $list[$key]['status'] = 3;
                        $list[$key]['number'] = intval(($remind_time-time())/86400);
                    }
                }
            }else{
                if($val['validity_time'] < $time){
                    $list[$key]['status'] = 1;
                    $list[$key]['number'] = -intval((strtotime($time)-strtotime($val['validity_time']))/86400);
                }else{
                    $remind_time = strtotime($val['validity_time'])-($reminderday*86400);
                    if($remind_time < time()){
                        $list[$key]['status'] = 2;
                        $list[$key]['number'] = intval((time()-$remind_time)/86400);
                    }else{
                        $list[$key]['status'] = 3;
                        $list[$key]['number'] = intval(($remind_time-time())/86400);
                    }
                }
            }
            
            $list[$key]['exceed_phone'] = explode('^',$val['exceed_phone']);
           
            
        }
        
        if(!empty($status)){
            $list = seacharr_by_value($list,'status',$status);
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
    
    
    //注册证添加
    public function register_add(){
        $data = Request::param();
        if(empty($data['name'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入注册人';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['leibie'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请选择注册证类别';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['bianhao'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入注册证编号';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['principal'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请选择资质负责人';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['approval_time'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入批准日期';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['validity_time'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入有效期';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['reminder_time'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '临期提醒天数';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['reminder_type'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请选择临期提醒时间类型';
    		return json_encode($rs_arr,true);
    		exit;
        } if(empty($data['reminder_rate'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入临期提醒频率';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['exceed_rate'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '过期提醒频率';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['exceed_phone'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入提醒手机号';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['product_home'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入注册人住所';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['product_address'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入注册人生产地址';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['product_name'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入产品名称';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['product_names'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入产品简称';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['product_specification'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入规格型号';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['product_type'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入样本类型';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['product_ingredient'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入主要组成成分';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['product_purpose'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入预期用途';
    		return json_encode($rs_arr,true);
    		exit;
        }
        
        if(empty($data['product_information'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入产品储存条件及有效期';
    		return json_encode($rs_arr,true);
    		exit;
        }
        
        $data['principal_name'] = Db::name('users')->where('id',$data['principal'])->value('username');
        
        $RC = new RC();
        $result =  $RC->addPost($data);
        if($result['error']){
            $rs_arr['status'] = 500;
    		$rs_arr['msg'] = $result['msg'];
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            $zzid = Db::name('register_credential')->order('id desc')->limit(1)->value('id');
            //判断结束日期
            if(!empty($data['end_time'])){
                $endtime = $data['end_time'];
            }else{
                $endtime = $data['validity_time'];
            }
            //根据日期类型去判断天数
            if($data['reminder_type'] == 1){
                $reminderday = $data['reminder_time'];
            }elseif($data['reminder_type'] == 2){
                $reminderday = $data['reminder_time']*30;
            }else{
                $reminderday = $data['reminder_time']*365;
            }
            //判断频率是否合理
            if($reminderday < $data['reminder_rate']){
                $rs_arr['status'] = 201;
        		$rs_arr['msg'] = '临期提醒频率不得大于过期时间';
        		return json_encode($rs_arr,true);
        		exit;
            }
            //计算提醒日期
            $remind_time = strtotime($endtime)-($reminderday*86400);
            $remind_day = date('Y-m-d',$remind_time);
            $remind_rate = $data['reminder_rate']*86400;
            $end_time = strtotime($endtime);
            //循环添加提醒记录
            while($remind_time < $end_time)
            {
                if($remind_time > time()){
                    $ins['type_id'] = 1;
                    $ins['bianhao'] = $data['bianhao'];
                    $ins['zzid'] = 'register'.$zzid;
                    $ins['name'] = Db::name('company')->where('id',$data['name'])->value('title');
                    $ins['neirong'] = 'CU资产管理-医疗器械注册证-'.$data['bianhao'].'-即将到期，请及时处理';
                    $ins['remind_time'] = date('Y-m-d',$remind_time);
                    $ins['remind_type'] = 1;
                    $ins['phone'] = $data['exceed_phone'];
                    $ins['create_time'] = time();
                    $ins['update_time'] = time();
                    
                    Db::name('remind')->insert($ins);
                }
                
                $remind_time = $remind_time + $remind_rate;
            }
            
            //循环添加过期记录
            for($i = 1;$i <= 3;$i++){
                $remind_endtime = $end_time + ($i*$data['exceed_rate']*86400);
                if($remind_endtime > time()){
                    $ins['type_id'] = 1;
                    $ins['bianhao'] = $data['bianhao'];
                    $ins['zzid'] = 'register'.$zzid;
                    $ins['name'] = Db::name('company')->where('id',$data['name'])->value('title');
                    $ins['neirong'] = 'CU资产管理-医疗器械注册证-'.$data['bianhao'].'-已过期，请及时处理';
                    $ins['remind_time'] = date('Y-m-d',$remind_endtime);
                    $ins['remind_type'] = 2;
                    $ins['phone'] = $data['exceed_phone'];
                    $ins['create_time'] = time();
                    $ins['update_time'] = time();
                    
                    Db::name('remind')->insert($ins);
                }
            }
            
            //增加操作记录
            aptitudelog($type_id = 1, $zzid, $aptitude_type = 1, $aptitude_content = '新增该数据', $aptitude_uid = $this->admin_id);

            $rs_arr['status'] = 200;
	        $rs_arr['msg'] = $result['msg'];
    		return json_encode($rs_arr,true);
    		exit;
        }
    }
    
    //修改保存
    public function register_upd(){
        $data = Request::param();
        $reminder_type = $data['reminder_type'];
       
        if(empty($data['id'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入id';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            $info = Db::name('register_credential')->where('id',$data['id'])->find();
        }
        
        $aptitude_content = '';
        if(empty($data['name'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入注册人';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['name'] != $info['name']){
                $aptitude_content = $aptitude_content.'注册证类别：（原内容） '.$info['name'].' （新内容） '.$data['name'].'^';
            }
        }
        
        if(empty($data['leibie'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请选择注册证类别';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['leibie'] != $info['leibie']){
                $aptitude_content = $aptitude_content.'注册证类别：（原内容） '.$info['leibie'].' （新内容） '.$data['leibie'].'^';
            }
        }
        
        if(empty($data['bianhao'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入注册证编号';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['bianhao'] != $info['bianhao']){
                $aptitude_content = $aptitude_content.'注册证编号：（原内容） '.$info['bianhao'].' （新内容） '.$data['bianhao'].'^';
            }
        }
        
        if(empty($data['principal'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请选择资质负责人';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['principal'] != $info['principal']){
                $old = Db::name('users')->where('id',$info['principal'])->value('username');
                $new = Db::name('users')->where('id',$data['principal'])->value('username');
                $aptitude_content = $aptitude_content.'资质负责人：（原内容） '.$old.' （新内容） '.$new.'^';
            }
        } 
        if(empty($data['approval_time'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入批准日期';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['approval_time'] != $info['approval_time']){
                $aptitude_content = $aptitude_content.'批准日期：（原内容） '.$info['approval_time'].' （新内容） '.$data['approval_time'].'^';
            }
        } 
        if(empty($data['validity_time'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入有效期';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['validity_time'] != $info['validity_time']){
                $aptitude_content = $aptitude_content.'有效期：（原内容） '.$info['validity_time'].' （新内容） '.$data['validity_time'].'^';
            }
        } 
        
        if(empty($data['submit_time'])){
            $data['submit_time'] = '0000-00-00';
        }
        if($data['submit_time'] != $info['submit_time']){
            $aptitude_content = $aptitude_content.'提交延续日：（原内容） '.$info['submit_time'].' （新内容） '.$data['submit_time'].'^';
        }
        if(empty($data['continue_time'])){
            $data['continue_time'] = '0000-00-00';
        }
        if($data['continue_time'] != $info['continue_time']){
            $aptitude_content = $aptitude_content.'延续批准日：（原内容） '.$info['continue_time'].' （新内容） '.$data['continue_time'].'^';
        }
        if(empty($data['effective_time'])){
            $data['effective_time'] = '0000-00-00';
        }
        if($data['effective_time'] != $info['effective_time']){
            $aptitude_content = $aptitude_content.'生效日期：（原内容） '.$info['effective_time'].' （新内容） '.$data['effective_time'].'^';
        }
        if(empty($data['end_time'])){
            $data['end_time'] = '0000-00-00';
        }
        if($data['end_time'] != $info['end_time']){
            $aptitude_content = $aptitude_content.'延续效期至：（原内容） '.$info['validity_time'].' （新内容） '.$data['validity_time'].'^';
        }
        
        if(empty($data['reminder_time'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '临期提醒天数';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['reminder_time'] != $info['reminder_time']){
                $aptitude_content = $aptitude_content.'临期提醒天数：（原内容） '.$info['reminder_time'].' （新内容） '.$data['reminder_time'].'^';
            }
        } 
        
        
        if(empty($reminder_type)){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请选择临期提醒时间类型';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($reminder_type != $info['reminder_type']){
                if($info['reminder_type'] == 1){
                    $old = '日';
                }elseif($info['reminder_type'] == 2){
                    $old = '月';
                }else{
                    $old = '年';
                }
                if($reminder_type == 1){
                    $new = '日';
                }elseif($reminder_type == 2){
                    $new = '月';
                }else{
                    $new = '年';
                }
                $aptitude_content = $aptitude_content.'临期提醒时间类型：（原内容） '.$old.' （新内容） '.$new.'^';
            }
        }
        if(empty($data['reminder_rate'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入临期提醒频率';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['reminder_rate'] != $info['reminder_rate']){
                $aptitude_content = $aptitude_content.'临期提醒频率：（原内容） '.$info['reminder_rate'].'天/次 （新内容） '.$data['reminder_rate'].'天/次^';
            }
        }
        if(empty($data['exceed_rate'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '过期提醒频率';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['exceed_rate'] != $info['exceed_rate']){
                $aptitude_content = $aptitude_content.'过期提醒频率：（原内容） '.$info['exceed_rate'].'天/次 （新内容） '.$data['exceed_rate'].'天/次^';
            }
        } 
        if(empty($data['exceed_phone'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入提醒手机号';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['exceed_phone'] != $info['exceed_phone']){
                $aptitude_content = $aptitude_content.'提醒手机号：（原内容） '.$info['exceed_phone'].' （新内容） '.$data['exceed_phone'].'^';
            }
        } 
        if(empty($data['product_home'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入注册人住所';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['product_home'] != $info['product_home']){
                $aptitude_content = $aptitude_content.'注册人住所：（原内容） '.$info['product_home'].' （新内容） '.$data['product_home'].'^';
            }
        } 
        if(empty($data['product_address'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入注册人生产地址';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['product_address'] != $info['product_address']){
                $aptitude_content = $aptitude_content.'注册人生产地址：（原内容） '.$info['product_address'].' （新内容） '.$data['product_address'].'^';
            }
        } 
        if(empty($data['product_name'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入产品名称';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['product_name'] != $info['product_name']){
                $aptitude_content = $aptitude_content.'产品名称：（原内容） '.$info['product_name'].' （新内容） '.$data['product_name'].'^';
            }
        } 
        if(empty($data['product_names'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入产品简称';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['product_names'] != $info['product_names']){
                $aptitude_content = $aptitude_content.'产品简称：（原内容） '.$info['product_names'].' （新内容） '.$data['product_names'].'^';
            }
        } 
        if(empty($data['product_specification'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入规格型号';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['product_specification'] != $info['product_specification']){
                $aptitude_content = $aptitude_content.'规格型号：（原内容） '.$info['product_specification'].' （新内容） '.$data['product_specification'].'^';
            }
        } 
        if(empty($data['product_type'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入样本类型';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['product_type'] != $info['product_type']){
                $aptitude_content = $aptitude_content.'样本类型：（原内容） '.$info['product_type'].' （新内容） '.$data['product_type'].'^';
            }
        } 
        if(empty($data['product_ingredient'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入主要组成成分';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['product_ingredient'] != $info['product_ingredient']){
                $aptitude_content = $aptitude_content.'主要组成成分：（原内容） '.$info['product_ingredient'].' （新内容） '.$data['product_ingredient'].'^';
            }
        } 
        if(empty($data['product_purpose'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入预期用途';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['product_purpose'] != $info['product_purpose']){
                $aptitude_content = $aptitude_content.'预期用途：（原内容） '.$info['product_purpose'].' （新内容） '.$data['product_purpose'].'^';
            }
        }
        
        if(empty($data['product_information'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入产品储存条件及有效期';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['product_information'] != $info['product_information']){
                $aptitude_content = $aptitude_content.'产品储存条件及有效期：（原内容） '.$info['product_information'].' （新内容） '.$data['product_information'].'^';
            }
        }
        
        $data['principal_name'] = Db::name('users')->where('id',$data['principal'])->value('username');
        
        
        
       
        $RC = new RC();
        $result = $RC->editPost($data);
        if($result['error']){
            $rs_arr['status'] = 500;
    		$rs_arr['msg'] = $result['msg'];
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            
            $zzid = 'register'.$data['id'];
            Db::name('remind')->where('zzid',$zzid)->delete();
            
            //判断结束日期
            if(!empty($data['end_time'])){
                $endtime = $data['end_time'];
            }else{
                $endtime = $data['validity_time'];
            }
            
            //根据日期类型去判断天数
            if($reminder_type == 1){
                $reminderday = $data['reminder_time'];
            }else if($reminder_type == 2){
                $reminderday = $data['reminder_time']*30;
            }else{
                $reminderday = $data['reminder_time']*365;
            }
            
            //判断频率是否合理
            if($reminderday < $data['reminder_rate']){
                $rs_arr['status'] = 201;
        		$rs_arr['msg'] = '临期提醒频率不得大于过期时间';
        		return json_encode($rs_arr,true);
        		exit;
            }
            //计算提醒日期
            $remind_time = strtotime($endtime)-($reminderday*86400);
            $remind_day = date('Y-m-d',$remind_time);
            $remind_rate = $data['reminder_rate']*86400;
            $end_time = strtotime($endtime);
            //循环添加提醒记录
            while($remind_time < $end_time)
            {
                if($remind_time > time()){
                    $ins['type_id'] = 1;
                    $ins['bianhao'] = $data['bianhao'];
                    $ins['zzid'] = $zzid;
                    $ins['name'] = Db::name('company')->where('id',$data['name'])->value('title');
                    $ins['neirong'] = 'CU资产管理-医疗器械注册证-'.$data['bianhao'].'-即将到期，请及时处理';
                    $ins['remind_time'] = date('Y-m-d',$remind_time);
                    $ins['remind_type'] = 1;
                    $ins['phone'] = $data['exceed_phone'];
                    $ins['create_time'] = time();
                    $ins['update_time'] = time();
                    
                    Db::name('remind')->insert($ins);
                }
                
                $remind_time = $remind_time + $remind_rate;
            }
            
            //循环添加过期记录
            for($i = 1;$i <= 3;$i++){
                $remind_endtime = $end_time + ($i*$data['exceed_rate']*86400);
                if($remind_endtime > time()){
                    $ins['type_id'] = 1;
                    $ins['bianhao'] = $data['bianhao'];
                    $ins['zzid'] = $zzid;
                    $ins['name'] = Db::name('company')->where('id',$data['name'])->value('title');
                    $ins['neirong'] = 'CU资产管理-医疗器械注册证-'.$data['bianhao'].'-已过期，请及时处理';
                    $ins['remind_time'] = date('Y-m-d',$remind_endtime);
                    $ins['remind_type'] = 2;
                    $ins['phone'] = $data['exceed_phone'];
                    $ins['create_time'] = time();
                    $ins['update_time'] = time();
                    
                    Db::name('remind')->insert($ins);
                }
            }
            
            
            //增加操作记录
            if($aptitude_content != ''){
                aptitudelog($type_id = 1, $data['id'],$aptitude_type = 2,$aptitude_content,$aptitude_uid = $this->admin_id);
            }
            
            $rs_arr['status'] = 200;
	        $rs_arr['msg'] = $result['msg'];
    		return json_encode($rs_arr,true);
    		exit;
        }
        
    }


    //商标添加
    public function brand_add(){
        $data = Request::param();
        if(empty($data['name'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请选择注册人';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['brand_name'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入商标名称';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['leibie'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入国际分类';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['bianhao'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入注册证编号';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['principal'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请选择资质负责人';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['brand_status'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入商标状态';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['brand_type'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入业务类型';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['brand_performance'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入完成情况';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['apply_time'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入申请日期';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        
        if(empty($data['register_time'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入注册日期';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['validity_time'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入有效期';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['end_time'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入续展后证书效期';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['reminder_time'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '临期提醒天数';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['reminder_type'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请选择临期提醒时间类型';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['reminder_rate'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入临期提醒频率';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['exceed_rate'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '过期提醒频率';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        if(empty($data['exceed_phone'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入提醒手机号';
    		return json_encode($rs_arr,true);
    		exit;
        } 
        
        $B = new B();
        $result =  $B->addPost($data);
        if($result['error']){
            $rs_arr['status'] = 500;
    		$rs_arr['msg'] = $result['msg'];
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            
            $zzid = Db::name('brand')->order('id desc')->limit(1)->value('id');
            //判断结束日期
            if(!empty($data['end_time'])){
                $endtime = $data['end_time'];
            }else{
                $endtime = $data['validity_time'];
            }
            //根据日期类型去判断天数
            if($data['reminder_type'] == 1){
                $reminderday = $data['reminder_time'];
            }elseif($data['reminder_type'] == 2){
                $reminderday = $data['reminder_time']*30;
            }else{
                $reminderday = $data['reminder_time']*365;
            }
            //判断频率是否合理
            if($reminderday < $data['reminder_rate']){
                $rs_arr['status'] = 201;
        		$rs_arr['msg'] = '临期提醒频率不得大于过期时间';
        		return json_encode($rs_arr,true);
        		exit;
            }
            //计算提醒日期
            $remind_time = strtotime($endtime)-($reminderday*86400);
            $remind_day = date('Y-m-d',$remind_time);
            $remind_rate = $data['reminder_rate']*86400;
            $end_time = strtotime($endtime);
            //循环添加提醒记录
            while($remind_time < $end_time)
            {
                if($remind_time > time()){
                    $ins['type_id'] = 1;
                    $ins['bianhao'] = $data['bianhao'];
                    $ins['zzid'] = 'brand'.$zzid;
                    $ins['name'] = Db::name('company')->where('id',$data['name'])->value('title');
                    $ins['neirong'] = 'CU资产管理-商标-'.$data['brand_name'].$data['bianhao'].'-即将到期，请及时处理';
                    $ins['remind_time'] = date('Y-m-d',$remind_time);
                    $ins['remind_type'] = 1;
                    $ins['phone'] = $data['exceed_phone'];
                    $ins['create_time'] = time();
                    $ins['update_time'] = time();
                    
                    Db::name('remind')->insert($ins);
                }
                
                $remind_time = $remind_time + $remind_rate;
            }
            
            //循环添加过期记录
            for($i = 1;$i <= 3;$i++){
                $remind_endtime = $end_time + ($i*$data['exceed_rate']*86400);
                if($remind_endtime > time()){
                    $ins['type_id'] = 1;
                    $ins['bianhao'] = $data['bianhao'];
                    $ins['zzid'] = 'brand'.$zzid;
                    $ins['name'] = Db::name('company')->where('id',$data['name'])->value('title');
                    $ins['neirong'] = 'CU资产管理-商标-'.$data['brand_name'].$data['bianhao'].'-已过期，请及时处理';
                    $ins['remind_time'] = date('Y-m-d',$remind_endtime);
                    $ins['remind_type'] = 2;
                    $ins['phone'] = $data['exceed_phone'];
                    $ins['create_time'] = time();
                    $ins['update_time'] = time();
                    
                    Db::name('remind')->insert($ins);
                }
            }
            
            //增加操作记录
            aptitudelog($type_id = 2, $zzid, $aptitude_type = 1,$aptitude_content = '新增该数据',$aptitude_uid = $this->admin_id);

            
            $rs_arr['status'] = 200;
	        $rs_arr['msg'] = $result['msg'];
    		return json_encode($rs_arr,true);
    		exit;
        }
    }
    
    //商标修改
    public function brand_upd(){
        $data = Request::param();
        $reminder_type = $data['reminder_type'];
       
        if(empty($data['id'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入id';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            $info = Db::name('brand')->where('id',$data['id'])->find();
        }
        
        $aptitude_content = '';
        if(empty($data['name'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请选择注册人';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['name'] != $info['name']){
                $old = Db::name('company')->where('id',$info['name'])->value('title');
                $new = Db::name('company')->where('id',$data['name'])->value('title');
                $aptitude_content = $aptitude_content.'注册人名称：（原内容） '.$old.' （新内容） '.$new.'^';
            }
        } 
        if(empty($data['brand_name'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入商标名称';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['brand_name'] != $info['brand_name']){
                $aptitude_content = $aptitude_content.'商标名称：（原内容） '.$info['brand_name'].' （新内容） '.$data['brand_name'].'^';
            }
        }
         
        if(empty($data['leibie'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入国际分类';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['leibie'] != $info['leibie']){
                $aptitude_content = $aptitude_content.'国际分类：（原内容） '.$info['leibie'].' （新内容） '.$data['leibie'].'^';
            }
        }
         
        if(empty($data['bianhao'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入注册证编号';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['bianhao'] != $info['bianhao']){
                $aptitude_content = $aptitude_content.'注册证编号：（原内容） '.$info['bianhao'].' （新内容） '.$data['bianhao'].'^';
            }
        }
        if(empty($data['principal'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请选择资质负责人';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['principal'] != $info['principal']){
                $old = Db::name('users')->where('id',$info['principal'])->value('username');
                $new = Db::name('users')->where('id',$data['principal'])->value('username');
                $aptitude_content = $aptitude_content.'资质负责人：（原内容） '.$old.' （新内容） '.$new.'^';
            }
        }  
        if(empty($data['brand_status'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入商标状态';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['brand_status'] != $info['brand_status']){
                $aptitude_content = $aptitude_content.'商标状态：（原内容） '.$info['brand_status'].' （新内容） '.$data['brand_status'].'^';
            }
        }
        if(empty($data['brand_type'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入业务类型';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['brand_type'] != $info['brand_type']){
                $aptitude_content = $aptitude_content.'业务类型：（原内容） '.$info['brand_type'].' （新内容） '.$data['brand_type'].'^';
            }
        } 
        if(empty($data['brand_performance'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入完成情况';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['brand_performance'] != $info['brand_performance']){
                $aptitude_content = $aptitude_content.'完成情况：（原内容） '.$info['brand_performance'].' （新内容） '.$data['brand_performance'].'^';
            }
        } 
        if(empty($data['apply_time'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入申请日期';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['apply_time'] != $info['apply_time']){
                $aptitude_content = $aptitude_content.'申请日期：（原内容） '.$info['apply_time'].' （新内容） '.$data['apply_time'].'^';
            }
        }  
        
        if(empty($data['register_time'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入注册日期';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['register_time'] != $info['register_time']){
                $aptitude_content = $aptitude_content.'注册日期：（原内容） '.$info['register_time'].' （新内容） '.$data['register_time'].'^';
            }
        }   
        if(empty($data['validity_time'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入有效期';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['validity_time'] != $info['validity_time']){
                $aptitude_content = $aptitude_content.'有效期：（原内容） '.$info['validity_time'].' （新内容） '.$data['validity_time'].'^';
            }
        } 
       
        if($data['end_time'] != $info['end_time']){
            $aptitude_content = $aptitude_content.'续展后证书效期：（原内容） '.$info['end_time'].' （新内容） '.$data['end_time'].'^';
        }
    
        if(empty($data['reminder_time'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '临期提醒天数';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['reminder_time'] != $info['reminder_time']){
                $aptitude_content = $aptitude_content.'临期提醒天数：（原内容） '.$info['reminder_time'].' （新内容） '.$data['reminder_time'].'^';
            }
        }  
        if(empty($data['reminder_type'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请选择临期提醒时间类型';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['reminder_type'] != $info['reminder_type']){
                if($info['reminder_type'] == 1){
                    $old = '日';
                }elseif($info['reminder_type'] == 2){
                    $old = '月';
                }else{
                    $old = '年';
                }
                if($reminder_type == 1){
                    $new = '日';
                }elseif($reminder_type == 2){
                    $new = '月';
                }else{
                    $new = '年';
                }
                $aptitude_content = $aptitude_content.'临期提醒时间类型：（原内容） '.$old.' （新内容） '.$new.'^';
            }
        }  
        if(empty($data['reminder_rate'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入临期提醒频率';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['reminder_rate'] != $info['reminder_rate']){
                $aptitude_content = $aptitude_content.'临期提醒频率：（原内容） '.$info['reminder_rate'].'天/次 （新内容） '.$data['reminder_rate'].'天/次^';
            }
        }
        if(empty($data['exceed_rate'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '过期提醒频率';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['exceed_rate'] != $info['exceed_rate']){
                $aptitude_content = $aptitude_content.'过期提醒频率：（原内容） '.$info['exceed_rate'].'天/次 （新内容） '.$data['exceed_rate'].'天/次^';
            }
        } 
        if(empty($data['exceed_phone'])){
            $rs_arr['status'] = 201;
    		$rs_arr['msg'] = '请输入提醒手机号';
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            if($data['exceed_phone'] != $info['exceed_phone']){
                $aptitude_content = $aptitude_content.'提醒手机号：（原内容） '.$info['exceed_phone'].' （新内容） '.$data['exceed_phone'].'^';
            }
        }  
        
        
        if($data['is_scan'] != $info['is_scan']){
            $aptitude_content = $aptitude_content.'是否扫描：（原内容） '.$info['is_scan'].' （新内容） '.$data['is_scan'].'^';
        }
        if($data['storage_place'] != $info['storage_place']){
            $aptitude_content = $aptitude_content.'存放地点：（原内容） '.$info['storage_place'].' （新内容） '.$data['storage_place'].'^';
        }
        if($data['beizhu'] != $info['beizhu']){
            $aptitude_content = $aptitude_content.'备注：（原内容） '.$info['beizhu'].' （新内容） '.$data['beizhu'].'^';
        }
        
        
        $B = new B();
        $result = $B->editPost($data);
        if($result['error']){
            $rs_arr['status'] = 500;
    		$rs_arr['msg'] = $result['msg'];
    		return json_encode($rs_arr,true);
    		exit;
        }else{
            
            $zzid = 'brand'.$data['id'];
            Db::name('remind')->where('zzid',$zzid)->delete();
            
            //判断结束日期
            if(!empty($data['end_time'])){
                $endtime = $data['end_time'];
            }else{
                $endtime = $data['validity_time'];
            }
            //根据日期类型去判断天数
            if($data['reminder_type'] == 1){
                $reminderday = $data['reminder_time'];
            }elseif($data['reminder_type'] == 2){
                $reminderday = $data['reminder_time']*30;
            }else{
                $reminderday = $data['reminder_time']*365;
            }
            //判断频率是否合理
            if($reminderday < $data['reminder_rate']){
                $rs_arr['status'] = 201;
        		$rs_arr['msg'] = '临期提醒频率不得大于过期时间';
        		return json_encode($rs_arr,true);
        		exit;
            }
            //计算提醒日期
            $remind_time = strtotime($endtime)-($reminderday*86400);
            $remind_day = date('Y-m-d',$remind_time);
            $remind_rate = $data['reminder_rate']*86400;
            $end_time = strtotime($endtime);
            //循环添加提醒记录
            while($remind_time < $end_time)
            {
                if($remind_time > time()){
                    $ins['type_id'] = 1;
                    $ins['bianhao'] = $data['bianhao'];
                    $ins['zzid'] = $zzid;
                    $ins['name'] = Db::name('company')->where('id',$data['name'])->value('title');
                    $ins['neirong'] = 'CU资产管理-医疗器械注册证-'.$data['bianhao'].'-即将到期，请及时处理';
                    $ins['remind_time'] = date('Y-m-d',$remind_time);
                    $ins['remind_type'] = 1;
                    $ins['phone'] = $data['exceed_phone'];
                    $ins['create_time'] = time();
                    $ins['update_time'] = time();
                    
                    Db::name('remind')->insert($ins);
                }
                
                $remind_time = $remind_time + $remind_rate;
            }
            
            //循环添加过期记录
            for($i = 1;$i <= 3;$i++){
                $remind_endtime = $end_time + ($i*$data['exceed_rate']*86400);
                if($remind_endtime > time()){
                    $ins['type_id'] = 1;
                    $ins['bianhao'] = $data['bianhao'];
                    $ins['zzid'] = $zzid;
                    $ins['name'] = Db::name('company')->where('id',$data['name'])->value('title');
                    $ins['neirong'] = 'CU资产管理-医疗器械注册证-'.$data['bianhao'].'-已过期，请及时处理';
                    $ins['remind_time'] = date('Y-m-d',$remind_endtime);
                    $ins['remind_type'] = 2;
                    $ins['phone'] = $data['exceed_phone'];
                    $ins['create_time'] = time();
                    $ins['update_time'] = time();
                    
                    Db::name('remind')->insert($ins);
                }
            }
            
            
            if($aptitude_content != ''){
                aptitudelog($type_id = 2, $data['id'], $aptitude_type = 2,$aptitude_content,$aptitude_uid = $this->admin_id);
            }
            
            $rs_arr['status'] = 200;
	        $rs_arr['msg'] = $result['msg'];
    		return json_encode($rs_arr,true);
    		exit;
        }
        
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
    
    
    
    
}
