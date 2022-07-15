<?php

namespace app\model;

use think\Model;

class Favority extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'mc_favorites';
    
    // 设置当前模型的数据库连接
    //protected $connection = 'db_config';

    protected $readonly = ['favority_id'];

    // 设置字段信息
    protected $schema = [
        'favority_id'                   => 'int',
        'favority_isdir'                 => 'int',
        'favority_relation_id'         => 'string',
        'favority_created_time'           => 'int',
        'favority_owner'         => 'string',
    ];

    protected $pk = 'favority_id';

}