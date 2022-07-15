<?php

namespace app\model;

use think\Model;

class Task extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'mc_tasks';
    
    // 设置当前模型的数据库连接
    //protected $connection = 'db_config';

    protected $readonly = ['task_id'];

    // 设置字段信息
    protected $schema = [
        'task_id'               => 'int',
        'task_type'             => 'int',
        'task_display_text'     => 'string',
        'task_from_path'        => 'string',
        'task_target_path'      => 'string',
        'task_owner'            => 'string',
        'task_total'            => 'int',
        'task_value'            => 'int',
        'task_state'            => 'int',
    ];

    protected $pk = 'task_id';

}