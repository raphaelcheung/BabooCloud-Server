<?php
namespace app\lib;

use app\model\Folder;
use app\model\File;
use app\lib\ErrorPool;
use think\facade\Config;
use app\lib\DebugException;
use app\lib\DisplayException;
use app\lib\Base;

class FileSystem
{
    public const UPLOAD_PATH = "../../runtime/upload_chunks";


    public static function getRootFolder($uid)
    {
        $root_path = Base::normalizeRelativePath(Config::get('mycloud.datapath'));

        if (!isset($root_path)){
            throw new DisplayException(500, '没有设置存储目录，请正确安装服务器');
        }

        $root_path = $root_path . '/' . $uid;
        return $root_path;
    }

    public static function createRootFolder($uid)
    {
        $root_path = FileSystem::getRootFolder($uid);

        if (!FileSystem::_ensurePathExists($root_path)){
            throw new DisplayException(500, '没有存储权限');
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

        $root_path = FileSystem::getRootFolder($uid);
        
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

    public static function initUploadFolder($uid)
    {

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
                        return false;
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
        //trace('saveSingleUnload: ' . $target, 'debug');
       
        if (is_file($target)){
            throw new DisplayException(400, '文件已存在');
        }

        $tmp = \think\facade\FileSystem::putFileAs('upload_chunks', $file, $task->task_client_id);
        $tmp = \think\facade\FileSystem::path($tmp);
        
        //trace('tmp file：' . $tmp, 'debug');
        //检验MD5

        $md5 = $file->md5();
        //trace('file->md5: ' . $md5, 'debug');
        //trace('task_file_hash: ' . $task->task_file_hash, 'debug');

        if (!($md5 === $task->task_file_hash)){
            unlink($tmp);
            throw new DisplayException(400, '文件 MD5 不一致');
        }

        $path_nodes = explode('/', $target);
        array_pop($path_nodes);
        $dir = implode('/', $path_nodes);
        if (!FileSystem::_ensurePathExists($dir)){
            unlink($tmp);
            throw new DisplayException(400, '无法创建目标路径');
        }

        rename($tmp, $target);
        return true;
    }
}