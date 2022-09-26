<?php

Hook::add('admin_log','app\\admin\\behavior\\AdminLog');

//生成页码跳转
function page_size($page_size=0){
    $str  = '<select class="form-control page_size">';
    for ($i=10;$i<=100;$i+=10){
        $selected = $page_size==$i? 'selected':'';
        $str .= '<option value="'.$i.'" '.$selected.' >'.$i.' 条/页</option>';
    }
    $str .= '</select>';
    return $str;
}

//空数据提示
function empty_list($num=10){
    $empty="<tr><td colspan='".$num."' align='center'>暂无数据</td></tr>";
    return $empty;
}

// 无限分类-权限
function tree($cate , $lefthtml = '|— ' , $pid=0 , $lvl=0 ){
    $arr=array();
    foreach ($cate as $v){
        if($v['pid']==$pid){
            $v['lvl']=$lvl + 1;
            $v['lefthtml']=str_repeat($lefthtml,$lvl);
            $v['ltitle']=$v['lefthtml'].$v['title'];
            $arr[]=$v;
            $arr= array_merge($arr,tree($cate,$lefthtml,$v['id'], $lvl+1 ));
        }
    }
    return $arr;
}

//权限设置选中状态
function auth($cate,$pid=0,$rules){
    $arr=array();
    $rulesArr = explode(',',$rules);
    foreach ($cate as $v){
        if($v['pid']==$pid){
            if(in_array($v['id'],$rulesArr)){
                $v['checked']=true;
            }
            $v['open']=true;
            $arr[]=$v;
            $arr= array_merge($arr,auth($cate, $v['id'],$rules));
        }
    }
    return $arr;
}

/**
 * PHP格式化字节大小
 * @param  number $size      字节数
 * @param  string $delimiter 数字和单位分隔符
 * @return string            格式化后的带单位的大小
 */
function format_bytes($size, $delimiter = '') {
    $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
    for ($i = 0; $size >= 1024 && $i < 5; $i++) $size /= 1024;
    return round($size, 2) . $delimiter . $units[$i];
}

function get_file_list(){
    $file_list = [];
    $file_path = resource_path('views\home');
    if (is_dir($file_path)){
        $handler = opendir($file_path);
        while( ($filename = readdir($handler)) !== false ) {
            if($filename != "." && $filename != ".."){
                $file_list[] = $filename;
            }
        }
        closedir($handler);
        return $file_list;
    }
}

/**
 * 获取目录里的文件，不包括下级文件夹
 * @param string $dir  路径
 * @return array
 */
function get_dir($dir){
    $file = @ scandir($dir);
    foreach($file as $key){
        if ( $key != ".." && $key != "." ){
            $files[] = $key;
        }
    }
    return $files;
}
//获取文件夹中的文件,含目录
function dir_list($path, $exts = '', $list= array()) {
    $path = dir_path($path);
    $files = glob($path.'*');
    foreach($files as $v) {
        $fileext = fileext($v);
        if (!$exts || preg_match("/\.($exts)/i", $v)) {
            $list[] = $v;
            if (is_dir($v)) {
                $list = dir_list($v, $exts, $list);
            }
        }
    }
    return $list;
}
/**
 * 补齐目录后的/
 * @param $path 目录
 * @return string
 */
function dir_path($path) {
    $path = str_replace('\\', '/', $path);
    if(substr($path, -1) != '/') $path = $path.'/';
    return $path;
}
/**
 * 查找文件后缀
 * @param $filename 文件名称
 * @return string 后缀名称（如：html）
 */
function fileext($filename) {
    return strtolower(trim(substr(strrchr($filename, '.'), 1, 10)));
}
//删除目录及文件
function dir_delete($dir) {
    $dir = dir_path($dir);
    if (!is_dir($dir)) return FALSE;
    $list = glob($dir.'*');
    foreach($list as $v) {
        is_dir($v) ? dir_delete($v) : @unlink($v);
    }
    return @rmdir($dir);
}


function auths(){
    
    $pid = 0;
    
    $list = Db::name('auth_rule')->where(['pid'=>$pid])->order('sort asc')->select();
    
    $lists = array();
    
    foreach ($list as $key => $val){
        
        $lists[$key]['id'] = $val['id'];
        $lists[$key]['label'] = $val['title'];
        
        $num = Db::name('auth_rule')->where(['pid'=>$val['id']])->count();
        if($num > 0){
            $lists[$key]['children'] = get_tree($val['id']);
        }else{
            $lists[$key]['children'] = '';
        }
        
    }
    
    return $lists;
    exit;
    
}

function get_tree($pid = 0){
  
    $list = Db::name('auth_rule')->where(['pid'=>$pid])->order('sort asc')->select();
    
    $lists = array();
    
    foreach ($list as $key => $val){
        
        $lists[$key]['id'] = $val['id'];
        $lists[$key]['label'] = $val['title'];
        
        $num = Db::name('auth_rule')->where(['pid'=>$val['id']])->count();
        
        if($num > 0){
            $lists[$key]['children'] = get_tree($val['id']);
        }else{
            $lists[$key]['children'] = '';
        }
        
    }
    
    return $lists;
}



/*
 * base64格式图片转图片文件
 */
function base64_imgs($base64url,$bool = false)
{
    //匹配出图片的格式
    $base64url = str_replace (' ' , '+' , $base64url);
    if ( preg_match ('/^(data:\s*image\/(\w+);base64,)/' , $base64url , $result) ) {
        $type = $result[ 2 ];
        $new_file = ROOT_PATH . 'public' . DS . 'uploads/cate/' . date ('Ymd' , time ()) . "/";
        if ( !file_exists ($new_file) ) {
            //检查是否有该文件夹，如果没有就创建，并给予最高权限
            mkdir ($new_file , 0700,true);
        }
        $new = md5 (time ().uuid());
        $new_file = $new_file . $new . ".{$type}";
        if ( file_put_contents ($new_file , base64_decode (str_replace ($result[ 1 ] , '' , $base64url))) ) {
            $url = http_type();
            $file_name =  "/uploads/cate/" . date ('Ymd' , time ()) . "/" . $new . ".{$type}";
            return ['code'=>200,'msg'=>$file_name];
        } else {
            return ['code'=>100,'msg'=>'图片不是base64格式！'];
        }
    }
}
