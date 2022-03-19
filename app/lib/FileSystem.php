<?php
namespace app\lib;

use app\model\Folder;
use app\model\File;
use app\lib\ErrorPool;
use think\facade\Config;
use app\lib\DebugException;
use app\lib\DisplayException;

class FileSystem
{
    public static function createRootFolder($uid)
    {
        $root_path = Config::get('mycloud.datapath');

        if (!isset($root_path)){
            throw new DisplayException(500, '没有设置存储目录，请正确安装服务器');
        }

        $root_path = $root_path . $uid;

        if (!is_dir($root_path)){
            if (!mkdir($root_path)){
                throw new DisplayException(500, '没有存储权限');
            }
        }
    }

    public static function deleteFile($uid, $filename)
    {
        if (strncmp($filename, '/', 1) == 0){
            $filename = substr($filename, 1);
        }

        $root_path = Config::get('mycloud.datapath');

        if (!isset($root_path)){
            throw new DisplayException(400, '没有设置存储目录，请正确安装服务器');
        }

        $target = $root_path . '/' . $uid . '/' . $filename;
        if (!is_file($target)){
            throw new DisplayException(404, $filename . '：文件不存在');
        }

        if (!is_writable($target)){
            throw new DisplayException(500, $filename . '：服务器没有该文件的操作权限，请找系统管理员协助');
        }

        if (!unlink($target)){
            throw new DisplayException(400, $filename . '：文件');
        }
    }

    public static function deleteFolder($uid, $path)
    {
        if (strncmp($path, '/', 1) == 0){
            $path = substr($path, 1);
        }

        $root_path = Config::get('mycloud.datapath');

        if (!isset($root_path)){
            throw new DisplayException(400, '没有设置存储目录，请正确安装服务器');
        }

        $target = $root_path . '/' . $uid . '/' . $path;

        self::_deleteFolder($target);
    }

    public static function _deleteFolder($full_path)
    {
        if (!is_dir($full_path)){
            return;
        }

        $subs = scandir($full_path);
        foreach($subs as $sub){
            if ($sub == '.' || $sub == '..'){
                continue;
            }

            $filename = $full_path . '/' . $sub;
            if (is_dir($filename)){
                self::_deleteFolder($filename);
            }else{
                unlink($filename);
            }
        }

        @rmdir($full_path . '/');
    }

    public static function createFolder($uid, $path)
    {
        //路径不能是根目录
        $path_nodes = explode('/', $path);

        //去掉第一个空节点
        array_shift($path_nodes);

        if (count($path_nodes) <= 0){
            throw new DisplayException(400, '路径错误');
        }

        //组合父目录完整路径
        array_pop($path_nodes);
        $parent_path = implode('/', $path_nodes);
        $root_path = Config::get('mycloud.datapath');

        if (!isset($root_path)){
            throw new DisplayException(500, '没有设置存储目录，请正确安装服务器');
        }

        $root_path = $root_path . $uid;
        
        //检查根目录是否存在
        if (!is_dir($root_path . '/' . $parent_path)){
            throw new DeubgException(500, '根目录不存在');
        }

        if (is_dir($root_path . $path)){
            throw new DisplayException(400, '文件夹已经存在');
        }

        if (!mkdir($root_path . $path)){
            throw new DisplayException(400, '没有在该路径下创建文件夹的权限');
        }
    }
}