<?php
namespace app\lib;

use app\model\Account;
use app\model\Task;
use app\lib\DebugException;
use app\lib\DisplayException;
use think\facade\Log;

class User
{
    private const TASK_STATE_INITED = 0;
    //private const TASK_STATE_PROCESSING = 1;
    private const TASK_STATE_COMPLETED = 2;
    private const TASK_STATE_BREAKDOWN = 3;

    private const TASK_TYPE_UPLOAD = 0;
    //private const TASK_TYPE_DOWNLOAD = 1;
    private const TASK_TYPE_ADD = 2;
    private const TASK_TYPE_DEL = 3;
    private const TASK_TYPE_UPDATE = 4;



    protected const FILERESULT_TEMPLATE = [
        'name' => '',
        'ext' => '',
        'isdir' => true,
        'modified' => 0,
        'upload_time' => 0,
        'size' => 0,
        'status' => 0,
        'id' => -1,
        'permissions' => DbSystem::PERMISSION_ALL,
        'parent_path' => '',
        'mimetype'  => 'dir',
    ];

    protected const TASKRESULT_TEMPLATE = [
        'id' => 0,
        'title' => '',

        'from'  => '',
        'target' => '',
        'total' => 1,
        'value' => 0,
        'state' => 0,
    ];

    protected $Account;
    protected $Info = [];

    public static function getUser(Account $account)
    {
        $user = new User($account);
        return $user;
    }

    protected function __construct(Account $account)
    {
        $this->Account = $account;
    }

    public function getInfo()
    {
        $quota = isset($this->Account) ? $this->Account->account_quota : 0;
        $used = isset($this->RootFolder) ? $this->Folders['folder']->folder_size : 0;

        $result = [
            'quota' => $quota,
            'used' => $used,
            'percent' => $quota <= 0 ? 0 : $used * 100 / $quota,
        ];

        return $result;
    }

    public function GetSubs($path)
    {
        $folder = DbSystem::findFolder($this->Account->uid, $path);

        if ($folder == null){
            throw new DisplayException(404, '目录不存在');
        }

        $folders = DbSystem::getSubFolders($this->Account->uid, $path);
        $files = $folder->joinfiles()->select();

        $result = [];
        $result[] = $this->fillFolderResult($folder);

        foreach($folders as $folder){
            $result[] = $this->fillFolderResult($folder);
        }

        foreach($files as $file){
            $result[] = $this->fillFileResult($file);
        }

        return $result;
    }

    public function checkSpace($add)
    {
        return $this->Account->account_quota > ($this->Account->account_used + $add);
    }

    private function fillFolderResult($folder)
    {
        return \array_merge(self::FILERESULT_TEMPLATE, [
            'name' => $folder->folder_name,
            'modified' => $folder->folder_modified,
            'upload_time' => $folder->folder_upload_time,
            'size' => $folder->folder_size,
            'status' => $folder->folder_status,
            'id' => $folder->folder_id,
            'parent_path' => $folder->folder_parent_path,
        ]);
    }

    private function fillFileResult($file)
    {
        return \array_merge(self::FILERESULT_TEMPLATE, [
            'name' => $file->file_name,
            'ext' => $file->file_ext,
            'isdir' => false,
            'modified' => $file->file_modified,
            'upload_time' => $file->file_upload_time,
            'size' => $file->file_size,
            'status' => $file->file_status,
            'id' => $file->file_id,
            'parent_path' => $file->folder_parent_path,
            'mimetype' => 'file'
        ]);
    }

    public function createFolder($path)
    {
        if ($path === ''){
            throw new DisplayException(403, '不能创建根目录');
        }

        $path_nodes = Base::explode('/', $path);

        $name = array_pop($path_nodes);

        if ($name === ''){
            throw new DisplayException(400, '文件夹名称不合规');
        }

        $result = FileSystem::createFolder($this->Account->uid, $path);
        if ($result instanceof Result){
            throw new DisplayException($result->code(), $result->msg());
        }

        $parent_path = implode('/', $path_nodes);
        $folder = DbSystem::createFolder($this->Account->uid, $parent_path, $name);
        if ($result instanceof Result){
            throw new DisplayException($result->code(), $result->msg());
        }

        return array_merge(self::FILERESULT_TEMPLATE, [
            'name' => $folder->folder_name,
            'modified' => $folder->folder_modified,
            'upload_time' => $folder->folder_upload_time,
            'size' => $folder->folder_size,
            'status' => $folder->folder_status,
            'id' => $folder->folder_id,
            'parent_path' => $parent_path,
        ]);
    }

    public function getFolder($path)
    {
        $path_nodes = Base::explode('/', $path);
        //array_shift($path_nodes);
        $current = $this->Folders;

        foreach($path_nodes as $node){
            if (!isset($current['subs'][$node])){
                return new Result(404, '目录不存在');
            }

            $current = $current['subs'][$node];
        }

        return array_merge(self::FILERESULT_TEMPLATE, [
            'name' => $current['folder']->folder_name,
            'modified' => $current['folder']->folder_modified,
            'upload_time' => $current['folder']->folder_upload_time,
            'size' => $current['folder']->folder_size,
            'status' => $current['folder']->folder_status,
            'id' => $current['folder']->folder_id,
            'parent_path' => $current['folder']->folder_parent_path,
        ]);
    }

    public function deleteFolder($path)
    {
        DbSystem::deleteFolder($this->Account->uid, $path);
    }

    public function deleteFile($filename)
    {
        DbSystem::deleteFile($this->Account->uid, $filename);
    }

    public function getDownloadList($page)
    {
        $results = [];
        $tasks = DbSystem::getTaskList($this->Account->uid, 1, $page);

        if (isset($tasks)){
            foreach($tasks as $task){
                $results[] = array_merge(self::TASKRESULT_TEMPLATE, [
                    'id' => $task->task_id,
                    'title' => $task->task_display_text,
                    'from' => $task->task_from_path,
                    'target' => $task->task_target_path,
                    'total' => $task->task_total,
                    'value' => $task->task_value,
                    'state' => $task->task_state,
                ]);
            }
        }

        if ($page == 0){
            $results[] = [                    
                'id' => 2,
                'title' => '我切封杀了.txt',
                'from' => '/sadfsdf/',
                'target' => '/拉萨的会计分录/123/asdfl',
                'total' => 50,
                'value' => 0,
                'state' => 0,
            ];

            $results[] = [                    
                'id' => 2,
                'title' => 'sdfsdaf起來.1234abcd',
                'from' => '/我卻認爲共分爲日期/12342314/asdfsdf',
                'target' => '/拉萨的会计分录/123/皮尅林',
                'total' => 50,
                'value' => 0,
                'state' => 0,
            ];
        }

        return $results;
    }

    public function getUploadList($page)
    {
        $results = [];
        $tasks = DbSystem::getTaskList($this->Account->uid, 0, $page);

        if (isset($tasks)){
            foreach($tasks as $task){
                $results[] = array_merge(self::TASKRESULT_TEMPLATE, [
                    'id' => $task->task_id,
                    'title' => $task->task_display_text,
                    'from' => $task->task_from_path,
                    'target' => $task->task_target_path,
                    'total' => $task->task_total,
                    'value' => $task->task_value,
                    'state' => $task->task_state,
                ]);
            }
        }

        if ($page == 0){
            $results[] = [                    
                'id' => 1,
                'title' => 'sadfsdakjg;lsajkdfg.txt',
                'from' => '/sadfsdf/',
                'target' => '/拉萨的会计分录/123/asdfl',
                'total' => 50,
                'value' => 0,
                'state' => 0,
            ];

            $results[] = [                    
                'id' => 2,
                'title' => '了破i九五七二问题.1234abcd',
                'from' => '/我卻認爲共分爲日期/12342314/asdfsdf',
                'target' => '/拉萨的会计分录/123/皮尅林',
                'total' => 50,
                'value' => 0,
                'state' => 0,
            ];

            $results[] = [                    
                'id' => 3,
                'title' => '2345312542134234234.1234abcd',
                'from' => '/1234231523153215/12342314/asdfsdf',
                'target' => '/124342134231423142314/123/皮尅林',
                'total' => 50,
                'value' => 0,
                'state' => 0,
            ];
        }

        return $results;
    }

    public function appendDownTaskList($list)
    {
        $has_exception = false;
        foreach($list as $task){
            if (isset($task['from'])
                && isset($task['target'])
                && isset($task['hash'])) {

                $result = $this->appendDownTask($task['from'], $task['target'], $task['hash']);
                if ($result != true) {
                    $has_exception = true;
                }
            } else {
                $has_exception = true;
            }
        }

        return $has_exception == true ? '部分任务添加失败' : true;
    }

    public function appendUploadTask($params)
    {
        if (DbSystem::checkFileExists($this->Account->uid, $params['task_target_path'])){
            throw new DisplayException(400, '文件已存在');
        }

        $params['task_type'] = self::TASK_TYPE_UPLOAD;
        $params['task_owner'] = $this->Account->uid;
        $params['task_state'] = self::TASK_STATE_INITED;
        $params['task_create_time'] = time();
        $params['task_owner'] = $this->Account->uid;

        $result = DbSystem::syncTask($params);
        if ($result instanceof Result){
            throw new DisplayException($result->code(), $result->msg());
        }

        if ($result->task_state != self::TASK_STATE_INITED){
            throw new DisplayException(403, '该任务已失效');
        }

        $result = FileSystem::getChunksIndies($result->task_client_id);
        if ($result instanceof Result){
            return [];
        }

        return $result;
    }

    public function upload($params)
    {
        $task = DbSystem::findTaskDb([
            'task_owner' => $this->Account->uid,
            'task_type' => 0,
            'task_client_id' => $params['task_client_id'],
        ]);

        if ($task == null){
            throw new DisplayException(403, '没有对应的上传任务');
        }

        if ($task->task_state != self::TASK_STATE_INITED){
            throw new DisplayException(403, '任务已失效');
        }

        if ($params['chunks'] > 0){

            $params = array_merge($params, [
                'task_client_id' => $task->task_client_id,
                'task_target_path' => $task->task_target_path,
                'uid' => $this->Account->uid,
                'task_file_hash' => $task->task_file_hash,
            ]);

            $result = FileSystem::saveUploadChunk($params);
            if ($result instanceof Result){
                if ($result->code() == 1000){
                    DbSystem::updateTaskState($task, self::TASK_STATE_BREAKDOWN);
                    throw new DisplayException($result->code(), $result->msg());
                }else{
                    throw new DisplayException($result->code(), $result->msg());
                }
            }
        }else {
            //trace('saveSingleUnload: ' . print_r($params['file'], true), 'debug');
            
            //锁定文件

            $locker_id = DbSystem::tryLockFile($this->Account->uid, $task->task_target_path);
            if ($locker_id == false){
                throw new DisplayException(500, '上传路径被锁定，请重新尝试');
            }

            try{
                $result = FileSystem::saveSingleUnload($this->Account->uid, $task, $params['file']);
            
                if ($result instanceof Result){
    
                    //文件保存出错的时候，如果是存在同名文件，不能删除原有文件
    
                    if ($result->code() == 520) {
                        DbSystem::unlockFile($locker_id);
                        throw new DisplayException(500, $result->msg());
                    }else{
                        FileSystem::deleteFile($this->Account->uid, $task->task_target_path);
                        DbSystem::unlockFile($locker_id);
                        throw new DisplayException($result->code(), $result->msg());
                    }
                }
    
                $result = DbSystem::createFile(
                    $this->Account->uid
                    , $task->task_target_path
                    , $result
                    , $task->task_lastmodified
                    , $task->task_file_hash);
    
                if ($result instanceof Result){
                    FileSystem::deleteFile($this->Account->uid, $task->task_target_path);
                    DbSystem::unlockFile($locker_id);
                    throw new DisplayException($result->code(), $result->msg());
                }
    
                DbSystem::updateTaskState($task, self::TASK_STATE_COMPLETED);
                DbSystem::unlockFile($locker_id);

            }catch(Exception $e){
                DbSystem::unlockFile($locker_id);
                trace('User.upload 出现异常：' . print_r($e, true), 'debug');
                throw new DisplayException(500, '服务器错误');
            }
        }

        return true;
    }

    public function doneUpload($params)
    {
        $task = DbSystem::findTaskDb([
            'task_owner' => $this->Account->uid,
            'task_type' => 0,
            'task_client_id' => $params['task_client_id'],
        ]);

        if ($task == null){
            throw new DisplayException(403, '没有对应的上传任务');
        }

        if ($task->task_state != self::TASK_STATE_INITED &&
            $task->task_state != self::TASK_STATE_COMPLETED){
            throw new DisplayException(403, '任务已失效');
        }

        if ($params['chunks'] > 1){
            $params = array_merge($params, [
                'task_target_path' => $task->task_target_path,
                'uid' => $this->Account->uid,
                'task_file_hash' => $task->task_file_hash,
            ]);

            //检验上传的文件块是否齐全
            if (!FileSystem::checkChunksReady($params)){
                throw new DisplayException(403, '文件上传不全');
            }

            //锁定文件

            $locker_id = DbSystem::tryLockFile($this->Account->uid, $task->task_target_path);
            if ($locker_id == false){
                throw new DisplayException(403, '上传路径被锁定，请重新尝试');
            }

            try{
                //拼合文件块

                $result = FileSystem::tryFinishChunks($params);

                if ($result instanceof Result){

                    DbSystem::updateTaskState($task, self::TASK_STATE_BREAKDOWN);
                    DbSystem::unlockFile($locker_id);
                    throw new DisplayException(400, $result->msg());
                }

                //建立 mysql 索引
                $result = DbSystem::createFile(
                    $this->Account->uid
                    , $task->task_target_path
                    , $result
                    , $task->task_lastmodified
                    , $task->task_file_hash);

                if ($result instanceof Result){
                    FileSystem::deleteFile($this->Account->uid, $task->task_target_path);
                    DbSystem::unlockFile($locker_id);
                    throw new DisplayException($result->code(), $result->msg());
                }

                DbSystem::updateTaskState($task, self::TASK_STATE_COMPLETED);
                
                //解锁
                DbSystem::unlockFile($locker_id);

            }catch(Exception $e){

                DbSystem::unlockFile($locker_id);
                trace('User.doneUpload 出现异常：' . print_r($e, true), 'debug');
                throw new DisplayException(500, '服务器错误');
            }


        }else{
            if ($task->task_state != self::TASK_STATE_COMPLETED){
                throw new DisplayException(400, '上传未完成');
            }
        }
    }
}