<?php

namespace app\model;

use think\Model;

class File extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'mc_admin_files';
    
    // 设置当前模型的数据库连接
    //protected $connection = 'db_config';

    protected $readonly = ['file_id'];

    // 设置字段信息
    protected $schema = [
        'file_id'               => 'int',
        'file_parent'           => 'int',
        'file_name'             => 'string',
        'file_ext'             => 'string',
        'file_size'             => 'int',
        'file_status'           => 'int',
        'file_owner'            => 'string',
        'file_hash'             => 'string',
        'file_last_scan_time'   => 'int',
        'file_putin_time'       => 'int',
        'file_modified'         => 'int',
    ];

    public function setTable($uid)
    {
        $this->$table = 'oc_' . $uid . '_files';
    }

    protected $pk = 'file_id';
}