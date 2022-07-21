<?php
declare (strict_types = 1);

namespace app\validate;

use think\Validate;

class File extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名' =>  ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule =   [
        'file_parent|父目录ID号'    => 'require|number',//字母数字下划线
        'file_name|文件名'     => 'require|regex:[\u4E00-\u9FA5\w]+|length:1,255',
        'file_ext|文件类型'     => 'require|regex:[\u4E00-\u9FA5\w]+|length:0,24',//汉字字母数字下划线
        'file_size|文件大小'        => 'require|number', 
        'file_status|文件状态'  => 'require|number|between:0,50',
        'file_owner|文件属主'        => 'require|regex:[\w]+|length:4,16',    
        'file_hash|文件Hash值'      => '',
        'file_last_scan_time|文件上一次扫描的时间' => '',
        'file_upload_time|文件入库时间'  => 'require|number',
        'file_modified|文件修改时间'    => 'require|number',
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名' =>  '错误信息'
     *
     * @var array
     */
    protected $message  =   [
        'file_parent.require'       => '父目录ID号不能为空',
        'file_parent.number'        => '父目录ID号只能是数字',
        'file_name.length'          => '文件名长度必需在1-255个字符之间',
        'file_name.require'         => '文件名不能为空',
        'file_name.regex'           => '文件名可以是汉字、字母、数字、下划线的组合',
        'file_ext.require'       => '文件扩展名可以是空字符串，但不能为null',
        'file_ext.regex'        => '文件扩展名可以是汉字、字母、数字、下划线的组合',
        'file_ext.length'       => '文件扩展名长度必需在0-24个字符之间',
        'file_size.require'      => '文件大小不能为空',    
        'file_size.number'           => '文件大小必需是数字',    
        'file_status.require'         => '文件状态不能为空',
        'file_status.number'           => '文件状态必需是数字',
        'file_status.between'              => '文件状态必需是介于0-50之间的数字',
        'file_owner.require'         => '文件属主不能为空',
        'file_owner.regex'           => '文件属主对应的是用户的UID，可以是字母、数字、下划线的组合',
        'file_owner.length'              => '文件属主长度应该介于4-16个字符',
    ];
}
