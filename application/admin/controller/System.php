<?php
/**
 * +----------------------------------------------------------------------
 * | 系统设置控制器
 * +----------------------------------------------------------------------
 */
namespace app\admin\controller;
use app\common\model\Config;
use think\facade\Request;

class System extends Base
{
    //系统设置
    public function index(){
        $data = Request::post();
        
        $language = $data['language'];
        if($language == 1){
            $system = \app\common\model\System::get(1);
        }else{
            $system = \app\common\model\System::get(2);
        }
        $rs_arr['status'] = 200;
		$rs_arr['msg'] = 'success';
		$rs_arr['data'] = $system;
		return json_encode($rs_arr,true);
		exit;
		

    }

    //系统设置保存
    public function systemPost(){
        if(Request::isPost()) {
            $data = Request::except('file');
            $system = new \app\common\model\System();
            $result = $system->allowField(true)->save($data, ['id' => $data['language']]);
            if($result) {
                $rs_arr['status'] = 200;
        		$rs_arr['msg'] = '修改成功';
        		return json_encode($rs_arr,true);
        		exit;
            } else {
                $rs_arr['status'] = 500;
        		$rs_arr['msg'] = '修改失败';
        		return json_encode($rs_arr,true);
        		exit;
            }
        }
    }

    
    //邮箱配置
    public function email(){
        $smtp = Config::where('inc_type','smtp')->select();
        $info = convert_arr_kv($smtp,'name','value');
        $this->view->assign('info', $info);
        return $this->view->fetch();
    }

    //邮箱配置保存
    public function emailPost(){
        if(Request::isPost()) {
            $data = Request::param();
            foreach ($data as $k=>$v){
                Config::where([['name','=',$k],['inc_type','=','smtp']])->update(['value'=>$v]);
            }
            $this->success('修改成功', 'email');
        }
    }

    //邮件发送
    public function trySend(){
        $sender = Request::param('email');
        //检查是否邮箱格式
        if (!is_email($sender)) {
            return ['error' => 1, 'msg' => '测试邮箱码格式有误'];
        }
        $data = Config::where('inc_type','smtp')->select();
        $config = convert_arr_kv($data,'name','value');
        $content = $config['test_eamil_info'];
        //所有项目必须填写
        if(empty($config['smtp_server']) || empty($config['smtp_port']) || empty($config['smtp_user']) || empty($config['smtp_pwd']) ){
            return ['error' => 1, 'msg' => '请完善邮件配置信息！'];
        }

        $send = send_email($sender, '测试邮件',$content);
        if ($send) {
            return ['error' => 0, 'msg' => '邮件发送成功！'];
        } else {
            return ['error' => 1, 'msg' => '邮件发送失败！'];
        }
    }

    //短信配置
    public function sms(){
        $sms = Config::where('inc_type','sms')->select();
        $info = convert_arr_kv($sms,'name','value');
        $this->view->assign('info', $info);
        return $this->view->fetch();
    }

    //短信配置保存
    public function smsPost(){
        if(Request::isPost()) {
            $data = Request::param();
            foreach ($data as $k=>$v){
                Config::where([['name','=',$k],['inc_type','=','sms']])->update(['value'=>$v]);
            }
            $this->success('保存成功', 'sms');
        }
    }

    //短信发送
    public function trySms(){
        $mobile = input('mobile');
        $data = Config::where('inc_type','sms')->select();
        $config = convert_arr_kv($data,'name','value');

        //生成验证码
        $code = rand ( 1000, 9999 );
        //发送短信
        $sms = new \Sms($config);
        //短信验证码
        $status = $sms->send_verify($mobile, $code);
        if (!$status) {
            return json(['error' => 1, 'msg' => $sms->error]);
        }else{
            return json(['error' => 0, 'msg' => '短信发送成功！']);
        }
    }
}
