<?php
namespace app\lib;

use app\model\Folder;
use app\model\File;
use app\model\Task;
use app\model\Account;
use app\model\Locker;
use app\lib\DebugException;
use app\lib\DisplayException;
use \think\facade\Db;
use \think\facade\PDO;
use app\lib\Base;

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

    public const LOCKER_TYPE_FOLDER = 0;
    public const LOCKER_TYPE_FILE = 1;


    public static function checkAccountExists_ByID($uid)
    {
        return self::findAccount_ByID($uid) != null;
    }

    public static function findAccount_ByID($uid)
    {
        return Account::where([
            'uid' => $uid,
        ])->find();
    }

    public static function createAccount($params, $rewrite = false)
    {
        $account = self::findAccount_ByID($params['uid']);

        try{
            if ($account == null){
                $account = new Account;
            }else if(!$rewrite){
                return new Result(400, '该用户已存在');
            }

            $account->uid = $params['uid'];
            $account->nickname = isset($params['nickname']) ? $params['nickname'] : '';
            $account->telephone = isset($params['telephone']) ? $params['telephone'] : '';
            $account->email = isset($params['email']) ? $params['email'] : '';
            $account->level = $params['level'];
            $account->createtime = time();
            $account->salt = $params['salt'];
            $account->password = $params['password'];
            $account->uid_hash = $params['uid_hash'];
            $account->account_quota = $params['account_quota'];
            $account->account_used = 0;
            
            $account->save();
        }catch(Exception $e){
            return new Result(500, $e->getMessage());
        }

        return $account;
    }

    public static function updateUsed($account, $used)
    {
        try{
            $account->account_used = intval($used);
            $account->save();
        }catch(Exception $e){
            return new Result(500, '更新用户已用空间失败');
        }
    }

    public static function createRootFolder($uid)
    { 
        $folder = self::findFolder($uid, '');

        if ($folder == null){
            $folder = new Folder;
        }

        try{
            $now = time();

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
        }catch(Exception $e){
            return new Result(500, $e->getMessage());
        }
     
        return true;
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
        $path_nodes = Base::explode('/', $path);

        if ($path === ''){
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

    public static function findFile($uid, $filename, $parentid)
    {
        return File::where([
            'file_owner' => $uid,
            'file_parent' => $parentid,
            'file_name' => $filename,
        ])->find();
    }

    public static function deleteFile($uid, $filename)
    {
        $name_nodes = Base::explode('/', $filename);
        $name = array_pop($name_nodes);

        $parent = implode('/', $name_nodes);

        $folder = self::findFolder($uid, $parent);
        if ($folder == null){
            return new Result(404, '没有这个文件');
        }

        $file = self::findFile($uid, $name, $folder->folder_id);
        return self::_deleteFile($file);
    }

    private static function _deleteFile($file)
    {
        if ($file == null){
            return new Result(404, '文件不存在');
        }

        $result = FileSystem::deleteFile($folder->folder_owner
            , $path . '/' . $file->file_name);

        if ($result instanceof Result && $result->code() != 404){
            return new Result($result->code(), $result->msg());
        }

        $file->delete();
        return true;
    }

    public static function deleteFolder($uid, $path)
    {
        $folder = self::findFolder($uid, $path);
        return self::_deleteFolder($uid, $folder);
    }

    private static function _deleteFolder($uid, $folder)
    {
        if ($folder == null){
            return new Result(404, '目录不存在');
        }

        if (self::hasSubFiles($folder->folder_id) 
            || self::hasSubFolders($uid, $folder->folder_parent_path . '/' . $folder->folder_name)){
            return new Result(403, '不能删除非空目录');
        }

        try{
            $folder->delete();
        }catch(Exception $e){
            return new Result(500, $e->getMessage());
        }

        return true;
    }

    public static function hasFolder($uid, $parent_path, $name)
    {
        return !Folder::where([
            'folder_owner' => $uid,
            'folder_parent_path' => $parent_path,
            'folder_name ' => $name,
        ])->findOrEmpty()->isEmpty();
    }

    public static function hasSubFolders($uid, $parent_path){
        return !Folder::where([
            'folder_owner' => $uid,
            'folder_parent_path' => $parent_path,
        ])->findOrEmpty()->isEmpty();
    }

    public static function hasSubFiles($parentid){
        return !File::where([
            'file_parent' => $parentid,
        ])->findOrEmpty()->isEmpty();
    }

    public static function createFolder($uid, $parent_path, $name)
    {
        $folder = self::findFolder($uid, $parent_path . '/' . $name);

        try{
            if ($folder == null){
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
            }
        }catch(Exception $e){
            return new Result(500, $e->getMessage());
        }

        return $folder;
    }

    public static function createFile($uid, $filepath, $filesize, $lastmodified, $md5)
    {
        $parts = Base::explode('/', $filepath);
        $filename = array_pop($parts);
        $parent = implode('/', $parts);

        $folder = self::findFolder($uid, $parent);
        if ($folder == null){
            return new Result(404, '目录不存在');
        }

        return self::createFile_($uid, $folder->folder_id, $filename, $filesize, $lastmodified, $md5);
    }

    public static function createFile_($uid, $parentid, $filename, $filesize, $lastmodified, $md5)
    {
        if (self::checkFileExists_($uid, $parentid, $filename)){
            return new Result(500, '文件已存在');
        }

        try{
            $parts = Base::explode('.', $filename);
            $ext = array_pop($parts);

            $file = new File();
            $file->file_parent = $parentid;
            $file->file_name = $filename;
            $file->file_ext = $ext;
            $file->file_size = $filesize;
            $file->file_status = 0;
            $file->file_owner = $uid;
            $file->file_hash = $md5;
            $file->file_last_scan_time = 0;
            $file->file_upload_time = time();
            $file->file_modified = $lastmodified;
    
            $file->save();
        }catch(Exception $e) {
            return new Result(500, $e->getMessage());
        }

        return true;
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

    public static function checkFolderExists($uid, $path)
    {
        if ($path === ''){
            return self::hasFolder($uid, '*', '');
        }else{
            $parts = Base::explode('/', $path);
            $folder_name = array_pop($parts);
            return self::hasFolder($uid, implode('/', $parts), $folder_name);
        }
    }

    public static function checkFileExists_($uid, $parentid, $filename)
    {
        return self::findFile($uid, $filename, $parentid) != null;
    }

    public static function checkFileExists($uid, $filepath)
    {
        if ($filepath === ''){
            return false;
        }

        $parts = Base::explode('/', $filepath);
        $filename = array_pop($parts);
        $parent = implode('/', $parts);

        $folder = self::findFolder($uid, $parent);
        if ($folder == null){
            return false;
        }

        return self::checkFileExists_($uid, $folder->folder_id, $filename);
    }

    public static function syncTask($params)
    {
        $task = DbSystem::findTaskDb([
            'task_owner' => $params['task_owner'],
            'task_type' => $params['task_type'],
            'task_client_id' => $params['task_client_id'],
        ]);

        if ($task == null) {
            $task = new Task();
            $task->task_type = $params['task_type'];
            $task->task_from_path = $params['task_from_path'];
            $task->task_target_path = $params['task_target_path'];
            $task->task_owner = $params['task_owner'];
            $task->task_state = $params['task_state'];
            $task->task_create_time = $params['task_create_time'];
            $task->task_file_hash = $params['task_file_hash'];
            $task->task_client_id = $params['task_client_id'];
            $task->task_file_type = $params['task_file_type'];
            $task->task_lastmodified = $params['task_lastmodified'];
            $task->task_filesize  = $params['task_filesize'];

            try {
                $task->save();
            } catch(Exception $e) {
                //Log::record('添加上传任务异常，uid:'.$params['task_owner'].'，from:'.$from.'，target:'.$target.'，hash:'.$hash, 'warnning', 'User::appendDownTask');
                //Log::record($e->getMessage(), 'warnning', 'User::appendDownTask');
                return new Result(500, $e->getMessage());
            }
        }

        return $task;
    }
    
    public static function updateTaskState($task, $state)
    {
        Task::update([
            'task_state' => $state
        ], [
            'task_id' => $task->task_id
        ]);
    }

    // 尝试锁定文件
    // 会先检查 mc_lockers 中有没有父目录以及相同文件的锁定记录，如有则返回 false
    // 在 mc_lockers 中创建文件锁定记录，并返回 locker_id
    public static function tryLockFile($uid, $filepath)
    {
        if ($filepath === ''){
            trace('filepath 不能为空', 'debug');
            return false;
        }

        $paths = Base::getAllNodesPath($filepath);
        array_pop($paths);

        $params = '';
        foreach($paths as $path){
            if ($params === ''){
                $params = $path;
            }else{
                $params = $params . '|' . $path;
            }
        }

        if ($params === ''){
            $result = Locker::whereOr([
                ['locker_by' , '=', $uid],
                ['locker_for', '=', $filepath],
                ['locker_type', '=', self::LOCKER_TYPE_FILE]
            ])->find();
        }else{
            $result = Locker::whereOr([
                [
                    ['locker_by' , '=', $uid],
                    ['locker_for', '=', $params],
                    ['locker_type', '=', self::LOCKER_TYPE_FOLDER]
                ],
                [
                    ['locker_by' , '=', $uid],
                    ['locker_for', '=', $filepath],
                    ['locker_type', '=', self::LOCKER_TYPE_FILE]
                ]
            ])->find();
        }

        if ($result != null){
            return false;
        }

        //启动事务
        //Db::startTrans();

        try{

            $now = time();

            $locker = new Locker();
            $locker->locker_by = $uid;
            $locker->locker_for = $filepath;
            $locker->locker_type = self::LOCKER_TYPE_FILE;
            $locker->locker_time = $now;

            $locker->save();

            //Db::commit();
            return $locker->locker_id;
        }catch(Exception $e){
            // 回滚事务 
            //Db::rollback();
            trace('tryLockFile 出错：' . $filepath, 'debug');
            trace(print_r($e, true), 'debug');
            return false;
        }
    }

    public static function unlockFile($locker_id)
    {
        Locker::destroy($locker_id);
    }

    // 尝试锁定文件
    // 会先检查 mc_lockers 中有没有各级目录、子文件夹、子文件的锁定记录，如有则返回 false
    // 在 mc_lockers 中创建文件锁定记录，并返回 locker_id
    public static function tryLockFolder($uid, $folder_path)
    {
        if ($folder_path === ''){
            return false;
        }

        $paths = Base::getAllNodesPath($folder_path);

        $params = '';
        foreach($paths as $path){
            if ($params === ''){
                $params = $path;
            }else{
                $params = $params . '|' . $path;
            }
        }

        $result = Locker::whereOr([
            [
                ['locker_by' , '=', $uid],
                ['locker_for', '=', $params],
                ['locker_type', '=', self::LOCKER_TYPE_FOLDER]
            ],
            [
                ['locker_by' , '=', $uid],
                ['locker_for', 'like', $folder_path . '/%'],
                ['locker_type', '=', self::LOCKER_TYPE_FOLDER]
            ],
            [
                ['locker_by' , '=', $uid],
                ['locker_for', 'like', $folder_path . '/%'],
                ['locker_type', '=', self::LOCKER_TYPE_FILE]
            ]
        ])->find();

        if ($result != null){
            return false;
        }

        //启动事务
        //Db::startTrans();

        try{

            $now = time();

            $locker = new Locker();
            $locker->locker_by = $uid;
            $locker->locker_for = $folder_path;
            $locker->locker_type = self::LOCKER_TYPE_FOLDER;
            $locker->locker_time = $now;

            $locker->save();

            //Db::commit();
            return $locker->locker_id;
        }catch(Exception $e){
            // 回滚事务 
            //Db::rollback();
            trace('tryLockFile 出错：' . $folder_path, 'debug');
            trace(print_r($e, true), 'debug');
            return false;
        }
    }
}