<?php
namespace app\lib;

use app\model\Account;
use app\lib\DebugException;
use app\lib\DisplayException;

class User
{
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
}