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
    private const UPLOAD_CHUNKSIZE = 5 * 1024 * 1024;
    private const UPLOAD_WAITINDEX_DELAY = 100000;
    private const UPLOAD_WAITINDEX_RETRY = 100;


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
        $path_nodes = Base::explode('/', $path);

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

    private static function _retryFileLocker($func)
    {
        $retry = 0;
        do{
            if ($retry > 0){
                usleep(self::UPLOAD_WAITINDEX_DELAY);
            }

            $result = $func();
            $retry ++;
        }while($retry < self::UPLOAD_WAITINDEX_RETRY 
            && $result == false);

        return $result;
    }

    public static function getChunksIndies($task_client_id)
    {
        $chunks_dir = Base::normalizeRelativePath(
            \think\facade\FileSystem::path('upload_chunks/' . $task_client_id));

        if (!is_file($chunks_dir . '/.index')) {
            return [];
        }

        $indies = self::_retryFileLocker(function() use($chunks_dir){
            return @file($chunks_dir . '/.index');
        });

        if ($indies == false){
            return new Result(500, '无法读取文件块索引');
        }

        return $indies;
    }

    public static function saveUploadChunk($params)
    {
        $chunk_path = \think\facade\FileSystem::putFileAs(
            'upload_chunks/' . $params['task_client_id']
            , $params['file'], strval($params['chunk']));

        $chunk_path = \think\facade\FileSystem::path($chunk_path);

        //文件块所在的目录
        $parts = Base::explode('/', $chunk_path);
        array_pop($parts);
        $chunks_dir = implode('/', $parts);

        //将文件块编号写入索引文件
        if (!is_file($chunks_dir . '/.index')){
            $indies = [];
            for($i = 0; $i < $params['chunks']; $i++){
                $indies[] = "0\n";
            }
        }else{
            $indies = self::_retryFileLocker(function() use($chunks_dir){
                return @file($chunks_dir . '/.index');
            });

            if ($indies == false){
                unlink($chunk_path);
                return new Result(500, '无法读取文件块索引');
            }
        }

        $indies[$params['chunk']] = "1\n";

        if(!self::_retryFileLocker(function() use($chunks_dir, $indies){
                return file_put_contents($chunks_dir . '/.index', implode("", $indies), LOCK_EX);
            })){
                self::_deleteFolder($chunks_dir);
                return new Result(1000, '无法写入文件块索引');
        }

        return $indies;
    }

    public static function checkChunksReady($params)
    {
        $indies = self::getChunksIndies($params['task_client_id']);

        if ($indies instanceof Result){
            return $indies;
        }

        if ($params['chunks'] > count($indies)){
            return false;
        }

        //确定文件块是否齐全
        for($i = 0; $i < $params['chunks']; $i++){
            if (!($indies[$i] === "1\n")){
                return false;
            }
        }

        return true;
    }

    public static function tryFinishChunks($params)
    {
        $chunks_dir = Base::normalizeRelativePath(
            \think\facade\FileSystem::path('upload_chunks/' . $params['task_client_id']));


        $target = FileSystem::getRootFolder($params['uid']) . '/' . $params['task_target_path'];
        if (is_file($target)){
            self::_deleteFolder($chunks_dir);
            trace('文件已存在：' . $target, 'debug');
            return new Result(500, '文件已存在');
        }


        //组合文件块
        $tmp_target = fopen($chunks_dir . '/.target', 'wb');
        if ($tmp_target == false){
            self::_deleteFolder($chunks_dir);
            return new Result(500, '无法打开临时文件');
        }

        for($i = 0; $i < $params['chunks']; $i++){
            $tmp_file_from = fopen($chunks_dir . '/' . $i, 'rb');
            $datas = fread($tmp_file_from, self::UPLOAD_CHUNKSIZE);
            fclose($tmp_file_from);
            fwrite($tmp_target, $datas);
        }

        fclose($tmp_target);

        //校验MD5
        if (!($params['task_file_hash'] === md5_file($chunks_dir . '/.target'))){
            self::_deleteFolder($chunks_dir);
            return new Result(500, '文件校验不通过');
        }

        //转存到目标路径
        $path_nodes = Base::explode('/', $target);
        array_pop($path_nodes);
        $target_dir = implode('/', $path_nodes);
        if (!FileSystem::_ensurePathExists($target_dir)){
            self::_deleteFolder($chunks_dir);
            return new Result(500, '无法创建目标路径');
        }

        $filesize = filesize($chunks_dir . '/.target');
        if ($filesize == false){
            self::_deleteFolder($chunks_dir);
            return new Result(500, '无法计算文件大小');
        }

        if (!rename($chunks_dir . '/.target', $target)){
            self::_deleteFolder($chunks_dir);
            return new Result(500, '无法移动上传文件到目标路径');
        }

        self::_deleteFolder($chunks_dir);
        return $filesize;
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
            $path_nodes = Base::explode('/', $path);
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
            trace('文件已存在：' . $target, 'debug');
            return new Result(520, '文件已存在');
        }

        $chunk_path = \think\facade\FileSystem::putFileAs(
            'upload_chunks', $file, $task->task_client_id);

        $chunk_path = \think\facade\FileSystem::path($chunk_path);
        
        //检验MD5
        $md5 = $file->md5();

        if (!($md5 === $task->task_file_hash)){
            unlink($chunk_path);
            return new Result(403, '文件 MD5 不一致');
        }

        $path_nodes = Base::explode('/', $target);
        array_pop($path_nodes);
        $dir = implode('/', $path_nodes);
        if (!FileSystem::_ensurePathExists($dir)){
            unlink($chunk_path);
            return new Result(500, '无法创建目标路径');
        }

        $filesize = filesize($chunk_path);
        if ($filesize == false){
            return new Result(500, '无法计算文件大小');
        }

        if (!rename($chunk_path, $target)){
            return new Result(500, '无法移动上传文件到目标路径');
        }
        return $filesize;
    }
}