<?php
namespace app\lib;

use app\model\Folder;
use app\model\File;
use app\lib\ErrorPool;
use think\facade\Config;
use app\lib\DebugException;
use app\lib\DisplayException;
use app\lib\Base;
use app\lib\Result;

class FileSystem
{
    //public const UPLOAD_PATH = "../../runtime/upload_chunks";


    private static function getRootFolder($uid)
    {
        $root_path = Base::normalizeRelativePath(Config::get('mycloud.datapath'));

        if (!isset($root_path)){
            return new Result(500, '没有设置存储目录，请正确安装服务器');
        }

        $root_path = $root_path . '/' . $uid;
        return $root_path;
    }

    public static function createRootFolder($uid)
    {
        $root_path = FileSystem::getRootFolder($uid);

        if (!FileSystem::_ensurePathExists($root_path)){
            return new Result(500, '没有存储权限');
        }

        return true;
    }

    public static function deleteFile($uid, $filename)
    {
        if (strncmp($filename, '/', 1) == 0){
            $filename = substr($filename, 1);
        }

        $root_path = Config::get('mycloud.datapath');

        if (!isset($root_path)){
            return new Result(500, '没有设置存储目录，请正确安装服务器');
        }

        $target = $root_path . '/' . $uid . '/' . $filename;
        if (!is_file($target)){
            return new Result(404, $filename . '：文件不存在');
        }

        if (!unlink($target)){
            return new Result(500, $filename . '：文件删除失败，没有操作权限或文件被占用');
        }

        return true;
    }

    public static function deleteFolder($uid, $path)
    {
        if (strncmp($path, '/', 1) == 0){
            $path = substr($path, 1);
        }

        $root_path = Config::get('mycloud.datapath');

        if (!isset($root_path)){
            return new Result(500, '没有设置存储目录，请正确安装服务器');
        }

        $target = $root_path . '/' . $uid . '/' . $path;

        return self::_deleteFolder($target);
    }

    private static function _deleteFolder($full_path)
    {
        if (!is_dir($full_path)){
            return new Result(404, '目录不存在');
        }

        $subs = scandir($full_path);
        foreach($subs as $sub){
            if ($sub == '.' || $sub == '..'){
                continue;
            }

            $filename = $full_path . '/' . $sub;
            if (is_dir($filename)){
                $result = self::_deleteFolder($filename);
                if ($result instanceof Result){
                    return $result;
                }
            }else{
                unlink($filename);
            }
        }

        @rmdir($full_path . '/');
        return true;
    }

    public static function createFolder($uid, $path)
    {
        if ($path === ''){
            return new Result(400, '不能创建根目录');
        }

        //路径不能是根目录
        $path_nodes = explode('/', $path);

        //组合父目录完整路径
        array_pop($path_nodes);
        $parent_path = implode('/', $path_nodes);

        $root_path = FileSystem::getRootFolder($uid);
        
        //检查父目录是否存在
        if (!is_dir($root_path . '/' . $parent_path)){
            return new Result(404, '父目录不存在');
        }

        if (is_dir($root_path . '/' . $path)){
            return true;
        }

        if (!mkdir($root_path . '/' . $path)){
            return new Result(500, '没有在该路径下创建文件夹的权限');
        }

        return true;
    }

    public static function saveUploadChunk($taskid, $chunk, $file)
    {
        \think\facade\FileSystem::putFileAs('upload_chunks/' . $taskid, $file, strval($chunk));
        return true;
    }

    public static function ensureUserPathExists($uid, $path)
    {
        $target = FileSystem::getRootFolder($uid) . '/' . $path;
        return FileSystem::_ensurePathExists($target);
    }

    //确保绝对路径有效，会逐级创建缺少的目录
    private static function _ensurePathExists($path)
    {
        if (!is_dir($path)){
            $path_nodes = explode('/', $path);
            $path = '';
            foreach($path_nodes as $node){
                $path = $path . $node . '/';
                if (!is_dir($path)){
                    if (!mkdir($path)){
                        return new Result(500, '没有权限创建目录');
                    }
                }
            }
        }

        return true;
    }

    public static function checkUserFileExists($uid, $filename)
    {
        $filename = FileSystem::getRootFolder($uid) . '/' . $filename;
        return is_dir($filename);
    }

    public static function checkUserPathExists($uid, $path)
    {
        $path = FileSystem::getRootFolder($uid) . '/' . $path;
        return is_dir($path);
    }

    public static function saveSingleUnload($uid, $task, $file)
    {
        $target = FileSystem::getRootFolder($uid) . '/' . $task->task_target_path;
       
        if (is_file($target)){
            return new Result(500, '文件已存在');
        }

        $tmp = \think\facade\FileSystem::putFileAs('upload_chunks', $file, $task->task_client_id);
        $tmp = \think\facade\FileSystem::path($tmp);
        
        //检验MD5
        $md5 = $file->md5();

        if (!($md5 === $task->task_file_hash)){
            unlink($tmp);
            return new Result(403, '文件 MD5 不一致');
        }

        $path_nodes = explode('/', $target);
        array_pop($path_nodes);
        $dir = implode('/', $path_nodes);
        if (!FileSystem::_ensurePathExists($dir)){
            unlink($tmp);
            return new Result(500, '无法创建目标路径');
        }

        $filesize = filesize($tmp);
        if ($filesize == false){
            return new Result(500, '无法计算文件大小');
        }

        if (!rename($tmp, $target)){
            return new Result(500, '无法移动上传文件到目标路径');
        }
        return $filesize;
    }
}