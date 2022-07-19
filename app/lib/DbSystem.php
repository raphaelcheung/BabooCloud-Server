<?php
namespace app\lib;

use app\model\Folder;
use app\model\File;
use app\model\Task;
use app\lib\DebugException;
use app\lib\DisplayException;
use \think\facade\Db;

class DbSystem
{
    public const PERMISSION_CREATE = 4;
    public const PERMISSION_READ = 1;
    public const PERMISSION_UPDATE = 2;
    public const PERMISSION_DELETE = 8;
    public const PERMISSION_SHARE = 16;
    public const PERMISSION_ALL = 31;

    public const STATUS_NORMAL = 0;
    public const STATUS_MISS = 1;
    public const STATUS_LOCKED = 2;
    public const STATUS_RECYCLE = 3;

    public static function createDefaultFolder($uid)
    { 
        if (!Folder::where([
                'folder_owner' => $uid, 
                'folder_name' => ''
            ])->findOrEmpty()->isEmpty()){

            throw new DisplayException(500, '该用户已有网盘根目录，请联系网站管理员');
        }

        $now = time();

        $folder = new Folder;
        $folder->folder_name = '';
        $folder->folder_type = 0;
        $folder->folder_parent_path = '*';
        $folder->folder_owner = $uid;
        $folder->folder_size = 0;
        $folder->folder_modified = $now;
        $folder->folder_upload_time = $now;
        $folder->folder_status = 0;

        FileSystem::createRootFolder($uid);

        $folder->save();
    }

    public static function getSubFiles($parent_id)
    {
        return File::where([
            'file_parent' => $parent_id
        ])->select();
    }

    public static function getSubFolders($uid, $parent_path)
    {
        return Folder::where([
            'folder_owner' => $uid,
            'folder_parent_path' => $parent_path,
        ])->select();
    }

    public static function findFolder($uid, $path)
    {
        $path_nodes = explode('/', $path);
        if (strcmp($path, '') == 0){
            $folder_name = '';
            $parent_path = '*';
        }else if(count($path_nodes) > 1){
            $folder_name = array_pop($path_nodes);
            $parent_path = implode('/', $path_nodes);
        }else{
            $folder_name = $path;
            $parent_path = '';
        }

        return Folder::where([
            'folder_owner' => $uid,
            'folder_parent_path' => $parent_path,
            'folder_name' => $folder_name,
        ])->find();
    }
    public static function deleteFile($uid, $filename)
    {
        $name_nodes = explode('/', $filename);
        $name = array_pop($name_nodes);

        $parent = implode('/', $name_nodes);

        $folder = Folder::where([
            'folder_parent_path' => $parent,
            'folder_ownder' => $uid,
            ])->find();
        if (!isset($folder)){
            throw new DisplayException(404, $file . '：文件不存在');
        }

        $file = $folder->joinfiles()->where('file_name', $name)->find();
        self::_deleteFile($file);
    }

    private static function _deleteFile($file)
    {
        if (!isset($file)){
            throw new DisplayException(404, $file . '：文件不存在');
        }

        FileSystem::deleteFile($folder->folder_owner
            , $path . '/' . $file->file_name);

        $file->delete();
    }

    public static function deleteFolder($uid, $path)
    {
        $folder = self::findFolder($uid, $path);
        self::_deleteFolder($folder);
    }

    private static function _deleteFolder($folder)
    {
        if (!isset($folder)){
            throw new DisplayException(404, $path . '：文件夹不存在');
        }

        $files = self::getSubFiles($folder->folder_id);
        $path = $folder->folder_name;

        if (strcmp($folder->folder_parent_path, '') != 0){
            $path = $folder->folder_parent_path . '/' . $path;
        }
        

        foreach($files as $file){
            self::_deleteFile($file);
        }

        $subfolders = self::getSubFolders($folder->folder_owner, $path);
        if (isset($subfolders)){
            foreach($subfolders as $subfolder){
                self::_deleteFolder($subfolder);
            }
        }

        FileSystem::deleteFolder($folder->folder_owner, $path);
        $folder->delete();
    }

    public static function hasFolder($uid, $parent_path, $name)
    {
        return !Folder::where([
            'folder_owner' => $uid,
            'folder_parent_path' => $parent_path,
            'folder_name ' => $name,
        ])->findOrEmpty()->isEmpty();
    }

    public static function createFolder($uid, $parent_path, $name)
    {
        if (self::hasFolder($uid, $parent_path, $name)){
            throw new DebugException(500, '数据库中已存在该目录');
        }

        $now = time();
        $folder = new Folder;
        $folder->folder_name = $name;
        $folder->folder_type = 0;
        $folder->folder_parent_path = $parent_path;
        $folder->folder_owner = $uid;
        $folder->folder_size = 0;
        $folder->folder_modified = $now;
        $folder->folder_upload_time = $now;
        $folder->folder_status = 0;

        $folder->save();

        return $folder;
    }

    public static function getTaskList($uid, $type, $page)
    {
        return Task::where([
            'task_owner' => $uid,
            'task_type'  => $type,
        ])->page($page, 20)->select();
    }

    public static function getTask($id)
    {
        return Task::find($id);
    }

    public static function findTaskDb($params)
    {
        return Task::where($params)->find();
    }

    public static function selectTaskDb($params, $count_per_page, $page_id)
    {
        return Task::where($params)->page($page_id, $count_per_page)->select();
    }
}