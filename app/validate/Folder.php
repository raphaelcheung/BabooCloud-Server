<?php
declare (strict_types = 1);

namespace app\validate;

use think\Validate;

class Folder extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名' =>  ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule =   [
        'folder_name|文件夹名称'                    => 'require|regex:[\u4E00-\u9FA5\w]+|length:1,60',//字母数字下划线
        'folder_type|文件夹类型'                    => 'require|number|between:0,10',
        'folder_parent_path|父目录路径'         => 'require', 
        'folder_owner|文件夹属主'               => 'require|regex:[\w]+|length:4,16',
        'folder_size|文件夹大小'                => 'require|number|between:0,50000000000000',
        'folder_status|文件夹状态'              => 'require|number|between:0,20',
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名' =>  '错误信息'
     *
     * @var array
     */
    protected $message  =   [
        'folder_name.require'       => '文件夹名称不能为空',
        'folder_name.regex'         => '文件夹名可以包含汉字、字母、数字以及下划线的组合',
        'folder_name.length'        => '文件夹名长度必需是1-60字符，汉字占2个字符',
        'folder_type.require'       => '文件夹类型不能为空',
        'folder_type.number'        => '文件夹类型必需是数字',
        'folder_type.between'       => '文件夹类型必需是0-10之间的数值',
        'folder_parent_path.require'       => '父目录路径不能为空',
     
        'folder_owner.require'      => '文件属主不能为空',
        'folder_owner.regex'        => '文件属主对应的是用户的UID，可以是字母、数字、下划线的组合',
        'folder_owner.length'       => '文件属主长度应该介于4-16个字符', 

        'folder_size.require'       => '文件夹大小不能为空',
        'folder_size.number'        => '文件夹大小必需为数字',
        'folder_size.between'       => '文件夹大小必需是0-50T之间',

        'folder_status.require'       => '文件夹状态不能为空',
        'folder_status.number'        => '文件夹状态必需为数字',
        'folder_status.between'       => '文件夹状态必需是0-20之间'
    ];
}
