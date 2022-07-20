<?php
namespace app\controller;

use app\BaseController;
use app\lib\DebugException;
use app\lib\DisplayException;
use think\Request;

class FileController extends BaseController
{
    private Request $_request;
    public function __construct(Request $request)
    {
        $this->_request = $request;
    }
    public function findfolder($path)
    {
        //trace('File/findfolder: path=' . $path, 'debug');

        $path = $this->checkPath($path);

        $user = $this->_request->user;

        $results = $user->GetSubs($path);

        //trace('File/findfolder: output-results=' . print_r($results, true), 'debug');

        return json($results, 200);
    }

    private function checkPath($path)
    {
        //防止路径非法注入
        if (strcmp($path, '') == 0){
            throw new DisplayException(400, '路径非法');
        }

        if (strpos($path, '..') !== false){
            throw new DisplayException(400, '路径非法');
        }

        if (!preg_match('/[\u4E00-\u9FA5\w\\\.\-\/]+/', $path)){
            throw new DisplayException(400, '路径非法');
        }

        if (strncmp($path, '/', 1) != 0){
            throw new DisplayException(400, '路径非法');
        }

        if (strncmp($path, '//', 2) == 0){
            $path = substr($path, 2);
        }

        if (strncmp($path, '/', 1) == 0){
            $path = substr($path, 1);
        }

        if (strlen($path) > 1){
            //去掉末尾的 /
            if (substr_compare($path, '/', -1) == 0){
                $path = substr($path, 0, strlen($path) - 1);
            }
        }

        trace('File/checkPath: output=' . $path, 'debug');
        return $path;
    }

    public function createfolder($path)
    {
        trace('File/createfolder: path=' . $path, 'debug');
        $path = $this->checkPath($path);

        if (strcmp($path, '') == 0){
            throw new DebugException(400, '不能创建根目录');
        }

        $user = $this->_request->user;

        $result = $user->createFolder($path);

        trace('File/createfolder: output-result=' . print_r($result, true), 'debug');

        return json($result, 200);
    }

    public function deletefolder($path)
    {
        trace('File/deletefolder: path=' . $path, 'debug');
        $path = $this->checkPath($path);

        if (strcmp($path, '') == 0){
            throw new DebugException(400, '不能删除根目录');
        }

        $user = $this->_request->user;
        $user->deleteFolder($path);

        return json($path . '删除成功', 200);
    }

    public function getfolder($path)
    {
/*         trace('File/getfolder: path=' . $path, 'debug');

        $path = $this->checkPath($path);

        $user = $this->_request->user;

        $result = $user->createFolder($path);
        trace('File/getfolder: output-result=' . print_r($result, true), 'debug');

        return json([$result], 200); */
        return 'sdafgsadfsdaf';
    }

    public function deletefile($filename)
    {
        trace('File/getfolder: path=' . $path, 'debug');

        $filename = $this->checkPath($filename);

        $user = $this->_request->user;
        $user->deleteFile($filename);

        return json($filename . '已成功删除', 200);
    }
}