<?php
namespace app\lib;

use think\exception\Handle;
use think\Response;
use Throwable;
use app\lib\DebugException;
use app\lib\DisplayException;

class MyExceptionHandle extends Handle
{
    public function render($request, Throwable $e): Response
    {
        if ($e instanceof DebugException){
            trace($e->getMessage(), 'warning');
            trace($e->getTraceAsString(), 'warning');
            return json('服务器异常，请稍候再试', $e->getStatusCode());
        }else if ($e instanceof DisplayException){
            trace($e->getMessage(), 'warning');
            trace($e->getTraceAsString(), 'warning');
            return json($e->getMessage(), $e->getStatusCode());
        }

        // 其他错误交给系统处理
        return parent::render($request, $e);
    }
}