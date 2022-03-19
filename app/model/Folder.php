<?php

namespace app\model;

use think\Model;

class Folder extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'mc_folders';
    
    // 设置当前模型的数据库连接
    //protected $connection = 'db_config';

    protected $readonly = ['folder_id'];

    // 设置字段信息
    protected $schema = [
        'folder_id'                 => 'int',
        'folder_name'               => 'string',
        'folder_type'               => 'int',
        'folder_parent_path'        => 'string',
        'folder_owner'              => 'string',
        'folder_size'               => 'int',
        'folder_modified'           => 'int',
        'folder_upload_time'        => 'int',
        'folder_status'             => 'int',
    ];

    protected $pk = 'folder_id';

    public function joinfiles()
    {
        return $this->hasMany(File::class, 'file_parent', 'folder_id');
    }
}