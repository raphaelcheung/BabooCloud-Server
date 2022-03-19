<?php
declare (strict_types = 1);

namespace app\validate;

use think\Validate;

class Account extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名' =>  ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule =   [
        'uid|用户名'        => 'require|regex:[\w]+|length:4,16',//字母数字下划线
        'password|密码'     => 'require|alphaDash|length:6,16',
        'nickname|昵称'     => 'chsDash|length:0,24',//汉字字母数字下划线破折号
        'level|权限'        => 'require|regex:[0-2]+|length:1,1', 
        'telephone|手机号'  => 'mobile',
        'email|邮箱'        => 'email',    
        'account_quota|云盘空间配额'   => 'require|number|between:0,50000000000000',
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名' =>  '错误信息'
     *
     * @var array
     */
    protected $message  =   [
        'uid.require'           => '用户名不能为空',
        'uid.regex'             => '用户名可以是字母、数字以及下划线 _ 的组合',
        'uid.length'            => '用户名长度必需是4-16字符',
        'password.require'      => '用户密码不能为空',
        'password.alphaDash'        => '用户密码可以是字母、数字破折号 - ，以及下划线 _ 的组合',
        'password.length'       => '用户密码长度必需是6-16字符',
        'nickname.chsDash'        => '昵称可以是汉字、字母、数字、破折号 - ，以及下划线 _ 的组合',
        'nickname.length'       => '昵称长度必需是0-24字符',
        'telephone.mobile'      => '手机号格式错误',    
        'email.email'           => '邮箱格式错误',    
        'level.require'         => '权限不能为空',
        'level.regex'           => '权限必须是0-2之间数字',
        'account_quota.require' => '云盘空间配额不能为空',
        'account_quota.number'  => '云盘空间配额必需为数字',
        'account_quota.between' => '云盘空间配额必需是0-50T之间的数字'

    ];
}
