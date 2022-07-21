<?php
namespace app\controller;

use app\BaseController;
use app\lib\DebugException;
use app\lib\DisplayException;
use think\Request;
use app\lib\Base;
use app\lib\ValidateHelper;

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

        ValidateHelper::checkRelativePath($path);
        $path = Base::normalizeRelativePath($path);

        $user = $this->_request->user;

        $results = $user->GetSubs($path);

        //trace('File/findfolder: output-results=' . print_r($results, true), 'debug');

        return json($results, 200);
    }

    public function createfolder($path)
    {
        trace('File/createfolder: path=' . $path, 'debug');
        ValidateHelper::checkRelativePath($path);
        $path = Base::normalizeRelativePath($path);

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
        ValidateHelper::checkRelativePath($path);
        $path = Base::normalizeRelativePath($path);

        if (strcmp($path, '') == 0){
            throw new DebugException(400, '不能删除根目录');
        }

        $user = $this->_request->user;
        $user->deleteFolder($path);

        return json($path . '删除成功', 200);
    }

    /*public function getfolder($path)
    {
        trace('File/getfolder: path=' . $path, 'debug');

        $path = $this->checkPath($path);

        $user = $this->_request->user;

        $result = $user->createFolder($path);
        trace('File/getfolder: output-result=' . print_r($result, true), 'debug');

        return json([$result], 200); 
        return 'sdafgsadfsdaf';
    }*/


}