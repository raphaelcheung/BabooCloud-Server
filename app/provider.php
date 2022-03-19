<?php
use app\Request;
use app\lib\MyExceptionHandle;

// 容器Provider定义文件
return [
    'think\Request'          => Request::class,
    'think\exception\Handle' => MyExceptionHandle::class,
];
