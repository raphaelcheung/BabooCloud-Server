<?php
namespace app\controller;

use app\BaseController;
use app\lib\ValidateHelper;
use app\lib\Base;
use app\lib\DebugException;
use app\lib\DisplayException;
//use think\facade\Request;
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

        //trace('Task/getdownloadlist', 'debug');
        $user = $this->_request->user;

        $results = $user->getDownloadList($pageid);
        //trace('Task/getdownloadlist: output-result=' . print_r($results, true), 'debug');

        return json($results, 200);
    }

    public function getuploadlist($page)
    {
        trace('Task/getuploadlist 1', 'debug');
        if (!isset($page)){
            throw new DisplayException(400, '参数错误');
        }

        $pageid = intval($page);

        if ($page < 0){
            throw new DisplayException(400, '参数错误');
        }

        trace('Task/getuploadlist 2', 'debug');
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

    public function closetask($id)
    {

    }

    public function appendupload()
    {
        $post = $this->_request->post();
        if (!isset($post) || (\is_array($post) && count($post) <= 0)) {
            throw new DisplayException(400, '参数错误');
        }

        //trace('Task/appendupload: ' . print_r($post, true), 'debug');

        $valid = new ValidateHelper();
        $valid->addMD5('id', true);
        $valid->addMD5('filehash', true);
        $valid->addRelativeFilePath('target', true);
        $valid->addRule('size', 'number|>:0', true);

        if ($valid->check($post) != true){
            throw new DisplayException(400, '参数错误');
        }

        $user = $this->_request->user;

        if (!$user->checkSpace(intval($post['size']))){
            throw new DisplayException(403, '云盘空间不足');
        }


        $params = [
            'task_client_id' => $post['id'],
            'task_from_path' => '',
            'task_target_path' => Base::normalizeRelativePath($post['target']),
            'task_file_hash' => $post['filehash'],
            'task_file_type' => intval($post['type']),
            'task_lastmodified' => intval($post['lastmodified']),
            'task_filesize' => intval($post['size']),
        ];


        $result = $user->appendUploadTask($params);

        for($i = 0; $i < count($result); $i++){
            $result[$i] = intval($result[$i]);
        }

        return json($result, 200);
    }

    public function upload()
    {
        $post = $this->_request->post();
        if (!isset($post) || (\is_array($post) && count($post) <= 0)) {
            throw new DisplayException(400, '参数错误');
        }

        //检查上传操作的合规性

        //trace('upload: ' . print_r($post, true), 'debug');

        $valid = new ValidateHelper();
        $valid->addMD5('uploadid', true);
        $valid->addRule('chunks', 'number|between:0,209716');
        $valid->addRule('chunk', 'number|between:0,209716|<:chunks');
        $valid->addRule('size', 'number|between:0,1099511627776');

        if (!$valid->check($post)){
            throw new DisplayException(400, '参数错误');
        }

        $user = $this->_request->user;

        if (!$user->checkSpace(intval($post['size']))){
            throw new DisplayException(403, '云盘空间不足');
        }

        $params = [
            'task_client_id' => $post['uploadid'],
            'chunks' => isset($post['chunks']) ? intval($post['chunks']) : 0,
            'chunk' => isset($post['chunk']) ? intval($post['chunk']) : 0,
        ];

        $file = $this->_request->file();
        if (count($file) <= 0){
            return json('没有文件数据', 400);
        }

        $params['file'] = $file['file'];


        $result = $user->upload($params);
        if ($result != true){
            return json($result, 400);
        }

        return json('', 200);
    }

    public function uploaddone($id, $chunks)
    {
        if (!$this->_request->isGet()){
            throw new DisplayException(400, '非法请求');
        }

        $params = [
            'task_client_id' => $id,
        ];

        if ($chunks != null && intval($chunks) > 0){
            $params['chunks'] = intval($chunks);
        }

        $valid = new ValidateHelper();
        $valid->addMD5('task_client_id', true);
        $valid->addRule('chunks', 'number|between:0,209716', true);


        if (!$valid->check($params)){
            throw new DisplayException(400, '参数错误');
        }

        $user = $this->_request->user;
        $user->doneUpload($params);
        return json('上传成功', 200);
    }
}