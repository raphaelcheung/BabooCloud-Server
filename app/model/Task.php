<?php

namespace app\model;

use think\Model;

class Task extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'mc_tasks';
    
    // 设置当前模型的数据库连接
    //protected $connection = 'db_config';

    protected $readonly = ['task_id', 'task_file_hash'];

    // 设置字段信息
    protected $schema = [
        'task_id'               => 'int',
        'task_type'             => 'int',
        'task_from_path'        => 'string',
        'task_target_path'      => 'string',
        'task_owner'            => 'string',
        'task_state'            => 'int',

        'task_create_time'      => 'int',
        'task_file_hash'        => 'string',
        'task_client_id'        => 'string',
        'task_file_type'        => 'string',
        'task_lastmodified'     => 'int',
        'task_filesize'         => 'int'
    ];

    protected $pk = 'task_id';

}