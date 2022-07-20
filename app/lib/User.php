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
    private const TASK_STATE_PROCESSING = 1;
    private const TASK_STATE_COMPLETED = 2;


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

        if (!isset($folder)){
            throw new DisplayException(404, '目录不存在');
        }

        $folders = DbSystem::getSubFolders($this->Account->uid, $path);
        $files = $folder->joinfiles()->select();

        $result = [];
        $result[] = $this->fillFolderResult($folder);

        if (isset($folders)){
            foreach($folders as $folder){
                $result[] = $this->fillFolderResult($folder);
            }
        }

        if (is_array($files)){
            foreach($files as $file){
                $result[] = $this->fillFileResult($file);
            }
        }

        return $result;
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
            'upload_time' => $file->file_putin_time,
            'size' => $file->file_size,
            'status' => $file->file_status,
            'id' => $file->file_id,
            'parent_path' => $parent['folder']->folder_parent_path,
            'mimetype' => 'file'
        ]);
    }

    public function createFolder($path)
    {
        $path_nodes = explode('/', $path);

        $name = array_pop($path_nodes);

        if (!isset($name)){
            throw new DisplayException(400, '路径不正确');
        }

        FileSystem::createFolder($this->Account->uid, $path);

        $parent_path = implode('/', $path_nodes);
        $folder = DbSystem::createFolder($this->Account->uid, $parent_path, $name);

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
        $path_nodes = explode('/', $path);
        array_shift($path_nodes);
        $current = $this->Folders;

        foreach($path_nodes as $node){
            if (strcmp($node, '') == 0){
                throw new DisplayException(404, '路径不正确');
            }

            if (!isset($current['subs'][$node])){
                throw new DebugException(404, '父目录找不到');
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

    /*public function appendDownTask($from, $target, $hash)
    {
        if (!DbSystem::checkDownListTask($this->Account->uid, $from, $target, $hash)){
            return '任务已存在';
        }

        $task = new Task();
        $task->task_type = 1;
        $task->task_display_text = '';
        $task->task_from_path = $from;
        $task->task_target_path = $target;
        $task->task_owner = $this->Account->uid;
        $task->task_total = 0;
        $task->task_value = 0;
        $task->task_state = 0;

        try {
            $task->save();
            return true;
        } catch(Exception $e) {
            Log::record('添加下载任务异常，uid:'.$this->Account->uid.'，from:'.$from.'，target:'.$target.'，hash:'.$hash, 'warnning', 'User::appendDownTask');
            Log::record($e->getMessage(), 'warnning', 'User::appendDownTask');
            return '添加任务异常';
        }
    }*/

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
        if (FileSystem::checkUserFileExists($this->Account->uid, $params['task_target_path'])){
            throw new DisplayException(400, '文件已存在');
        }

        $task = DbSystem::findTaskDb([
            'task_owner' => $this->Account->uid,
            'task_type' => 0,
            'task_client_id' => $params['task_client_id'],
        ]);

        if ($task == null) {
            $task = new Task();
            $task->task_type = 0;
            $task->task_from_path = $params['task_from_path'];
            $task->task_target_path = $params['task_target_path'];
            $task->task_owner = $this->Account->uid;
            $task->task_state = self::TASK_STATE_INITED;
            $task->task_create_time = time();
            $task->task_file_hash = $params['task_file_hash'];
            $task->task_client_id = $params['task_client_id'];
            $task->task_file_type = $params['task_file_type'];
            $task->task_lastmodified = $params['task_lastmodified'];

            try {
                $task->save();
            } catch(Exception $e) {
                Log::record('添加上传任务异常，uid:'.$this->Account->uid.'，from:'.$from.'，target:'.$target.'，hash:'.$hash, 'warnning', 'User::appendDownTask');
                Log::record($e->getMessage(), 'warnning', 'User::appendDownTask');
                return '添加任务异常';
            }
        }

        return true;
    }

    public function upload($params)
    {
        $task = DbSystem::findTaskDb([
            'task_owner' => $this->Account->uid,
            'task_type' => 0,
            'task_client_id' => $params['task_client_id'],
        ]);

        if ($task == null){
            throw new DisplayException(404, '没有找到对应的上传任务');
        }

        if ($params['chunks'] > 0){
            FileSystem::saveUploadChunk($params['task_client_id'], $params['chunk'], $params['file']);
        }else if (FileSystem::saveSingleUnload($this->Account->uid, $task, $params['file'])){

        }

        return true;
    }
}