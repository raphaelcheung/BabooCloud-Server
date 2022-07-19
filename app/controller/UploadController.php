<?php
namespace app\controller;

use app\BaseController;
use app\lib\DebugException;
use app\lib\DisplayException;
use think\Request;

class TaskController extends BaseController
{
    private Request $_request;
    public function __construct(Request $request)
    {
        $this->_request = $request;
    }

    
}