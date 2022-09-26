<?php
/**
 * +----------------------------------------------------------------------
 * | 模板控制器
 * +----------------------------------------------------------------------
 */
namespace app\admin\controller;
use think\Db;
use think\facade\Request;

class Template extends Base
{
    protected $template_path,$template_html,$template_css,$template_js,$upload_path;
    function initialize()
    {
        parent::initialize();
        $system = Db::name('system')
            ->where('id',1)
            ->find();

        $this->template_path = './template/home/'.$system['template'].'/';
        $this->template_html = $system['html'];
        $this->template_css = 'css';
        $this->template_js = 'js';
        $this->template_img = 'img';

        $this->assign('html', $this->template_html);//自定义html目录
        $this->assign('css' , $this->template_css); //自定义css目录
        $this->assign('js'  , $this->template_js);  //自定义js目录
        $this->assign('img' , $this->template_img);  //自定义媒体文件目录
    }

    //列表
    public function index(){
        $type = Request::param('type') ? Request::param('type') : 'html';
        if($type=='html'){
            $path=$this->template_path.$this->template_html.'/';
        }else{
            $path=$this->template_path.$type.'/';
        }
        $files = dir_list($path,$type);
        $templates = array();
        foreach ($files as $key=>$file){
            $filename = basename($file);
            $templates[$key]['value'] =  substr($filename,0,strrpos($filename, '.'));
            $templates[$key]['filename'] = $filename;
            $templates[$key]['filepath'] = $file;
            $templates[$key]['filesize']=format_bytes(filesize($file));
            $templates[$key]['filemtime']=filemtime($file);
            $templates[$key]['ext'] = strtolower(substr($filename,strrpos($filename, '.')-strlen($filename)));
        }

        $this->view->assign('type' , $type);         //当前显示的类型
        $this->view->assign('empty', empty_list(4)); //空数据提示
        $this->view->assign('list' , $templates);    //加载数据
        return $this->view->fetch();
    }

    //添加
    public function add(){
        $type=  Request::param('type') ? Request::param('type') : 'html';
        if($type=='html'){
            $path=$this->template_path.$this->template_html.'/';
        }else{
            $path=$this->template_path.$type.'/';
        }
        $this->view->assign('type', $type);//当前显示的类型
        $this->view->assign('info',null);
        return $this->view->fetch();
    }

    //添加保存
    public function addPost(){
        if(Request::isPost()){
            $filename = $this->checkFilename(Request::post('filename'));
            $type     = $this->checkFiletype(Request::param('type', 'html'));
            if($type=='html'){
                $path=$this->template_path.$this->template_html.'/';
            }else{
                $path=$this->template_path.$type.'/';
            }
            $file = $path.$filename.'.'.$type;
            if(file_exists($file)){
                $this->error('文件已经存在!');
            }else{
                file_put_contents($file,stripslashes(input('post.content')));
                if($type=='html'){
                    $this->success('添加成功!',url('index', ['type' => 'html']));
                }else{
                    $this->success('添加成功!',url('index', ['type' => $type]));
                }

            }
        }
    }

    //修改
    public function edit(){
        $filename = $this->checkFilename(Request::param('file'));
        $type     = Request::param('type') ? Request::param('type') : 'html';
        if($type=='html'){
            $path=$this->template_path.$this->template_html.'/';
        }else{
            $path=$this->template_path.$type.'/';
        }
        $file = $path.$filename;
        if(file_exists($file)){
            $file=iconv('gb2312','utf-8',$file);
            $content = file_get_contents($file);
            $info=[
                'filename'=>$filename,
                'file'=>$file,
                'content'=>$content,
                'type'=>$type
            ];
            $this->view->assign('info',$info);
        }else{
            $this->error('文件不存在！');
        }
        $this->view->assign('type', $type);//当前显示的类型
        return $this->view->fetch('add');
    }

    //修改保存
    public function editPost(){
        if(Request::isPost()) {
            $filename = $this->checkFilename(Request::post('filename'));
            $type     = $this->checkFiletype(Request::param('type', 'html'));
            if($type=='html'){
                $path=$this->template_path.$this->template_html.'/';
            }else{
                $path=$this->template_path.$type.'/';
            }
            $file = $path.$filename;
            if(file_exists($file)){
                file_put_contents($file,stripslashes(input('content')));
                if($type=='html'){
                    $this->success('修改成功!',url('index', ['type' => 'html']));
                }else{
                    $this->success('修改成功!',url('index', ['type' => $type]));
                }

            }else{
                $this->error('文件不存在!');
            }
        }
    }

    //删除
    public function del(){
        if(Request::isPost()) {
            $id = $this->checkFilename(Request::param('id'));
            if (empty($id)) {
                return ['error' => 1, 'msg' => '传输错误'];
            }
            //删除文件
            $filename = $id;
            $type = Request::param('type') ? Request::param('type') : 'html';
            if ($type == 'html') {
                $path = $this->template_path . $this->template_html . '/';
            } else {
                $path = $this->template_path . $type . '/';
            }
            $file = $path . $filename;
            if (file_exists($file)) {
                unlink($file);
                return ['error' => 0, 'msg' => '删除成功!'];
            } else {
                return ['error' => 1, 'msg' => '删除失败!'];
            }

        }
    }

    //媒体文件
    public function img(){
        $path = $this->template_path.$this->template_img.'/'.Request::param('folder');
        $folder = Request::param('folder') ? Request::param('folder') : '';
        $this->view->assign('folder',$folder);

        $uppath = explode('/',Request::param('folder'));
        $leve = count($uppath)-1;
        unset($uppath[$leve]);
        if($leve>1){
            unset($uppath[$leve-1]);
            $uppath = implode('/',$uppath).'/';
        }else{
            $uppath = '';
        }
        $this->view->assign('leve',$leve);
        $this->view->assign('uppath',$uppath);

        $files = glob($path.'*');
        $folders = $templates = array();
        foreach($files as $key => $file) {
            $filename = basename($file);
            if(is_dir($file)){
                $folders[$key]['filename'] = $filename;
                $folders[$key]['filepath'] = $file;
                $folders[$key]['ext'] = 'folder';
            }else{
                $templates[$key]['filename'] = $filename;
                $templates[$key]['filepath'] = ltrim($file,'.') ;
                $templates[$key]['ext'] = strtolower(substr($filename,strrpos($filename, '.')-strlen($filename)+1));
                if(!in_array($templates[$key]['ext'],array('gif','jpg','png','bmp'))) $templates[$key]['ico'] =1;
            }
        }
        $this->view->assign('path',$path);                //路径
        $this->view->assign('folders',$folders );         //文件夹
        $this->view->assign('files',$templates );         //文件
        $this->view->assign('type', $this->template_img); //当前显示的类型
        return $this->view->fetch();
    }

    //媒体文件删除
    public function imgDel(){
        $folder = str_replace("..", "", Request::post('folder'));
        $path = $this->template_path . $this->template_img . '/' . $folder;
        $file = $path . $this->checkFilename(Request::post('filename'));

        if(file_exists($file)){
            is_dir($file) ? dir_delete($file) : unlink($file);
            return ['error'=>0,'msg'=>'删除成功!'];
        }else{
            return ['error'=>1,'msg'=>'文件不存在!'];
        }
    }

    //过滤文件名
    private function checkFilename($fileName)
    {
        $fileName = str_replace("/", "", $fileName);
        $fileName = str_replace("..", "", $fileName);
        $fileName = str_ireplace(".php", ".html", $fileName);
        $fileName = str_ireplace(".asp", ".html", $fileName);
        return $fileName;
    }

    // 过滤类型
    private function checkFiletype(string $fileType)
    {
        $arr = ['html', 'css', 'js'];
        if (!in_array($fileType, $arr)) {
            return 'html';
        } else {
            return $fileType;
        }
    }
}
