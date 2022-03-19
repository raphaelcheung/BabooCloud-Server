<?php

namespace app\model;

use think\Model;

class Account extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'mc_accounts';
    
    // 设置当前模型的数据库连接
    //protected $connection = 'db_config';

    // 设置字段信息
    protected $schema = [
        'uid'           => 'string',
        'password'      => 'string',
        'nickname'      => 'string',
        'level'         => 'int',
        'telephone'     => 'string',
        'email'         => 'string',
        'salt'          => 'string',
        'lastlogintime' => 'int',
        'createtime'    => 'int',
        'logintoken'    => 'string',
        'tokenexpiretime' =>'int',
        'uid_hash'    =>'string',
        'account_quota' => 'int',
    ];

    protected $pk = 'uid';

    // 模型初始化
    protected static function init()
    {
        //TODO:初始化内容
    }

/*     public function getLevelAttr($value)
    {
        $level = [ 0 => '超级管理员', 1 => '管理员', 2 =>'用户'];
        return isset($level[$value]) ? $level[$value] : '用户';
    }
    public function setLevelAttr($value)
    {
        $level = [ '超级管理员' => 0, '管理员' => 1, '用户' => 2 ];

        if (isset($level[$value]))
        {
            return $level[$value];
        }
        else
        {
            return 2;
        }
    } */
}