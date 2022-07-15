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

    public function getdownloadlist($page)
    {
        if (!isset($page)){
            throw new DisplayException(400, '参数错误');
        }

        $pageid = intval($page);

        if ($page < 0){
            throw new DisplayException(400, '参数错误');
        }

        trace('Task/getdownloadlist', 'debug');
        $user = $this->_request->user;

        $results = $user->getDownloadList($pageid);
        trace('Task/getdownloadlist: output-result=' . print_r($results, true), 'debug');

        return json($results, 200);
    }

    public function getuploadlist($page)
    {
        if (!isset($page)){
            throw new DisplayException(400, '参数错误');
        }

        $pageid = intval($page);

        if ($page < 0){
            throw new DisplayException(400, '参数错误');
        }

        trace('Task/getuploadlist', 'debug');
        $user = $this->_request->user;

        $results = $user->getUploadList($pageid);
        trace('Task/getuploadlist: output-result=' . print_r($results, true), 'debug');

        return json($results, 200);
    }

    public function appenddownload($from, $target, $hash)
    {
        // 检查输入参数
        $from = trim($from);
        $target = trim($target);
        $hash = trim($hash);

        $user = $this->_request->user;

        $result = $user->appenddownload($from, $target, $hash);
        if ($result != true){
            return json($result, 400);
        }

        return json('', 200);
    }
}