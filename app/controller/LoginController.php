<?php
namespace app\controller;

use app\BaseController;
use app\model\Account;
use think\facade\Request;
use app\lib\Base;
use app\lib\ErrorPool;
use think\facade\Config;
use app\lib\AccountManager;
use app\lib\DebugException;
use app\lib\DisplayException;
use think\facade\View;

class LoginController extends BaseController
{
    public function index()
    {

        if (Request::isPost()){
            return $this->procLogin();
        }



        $params = [];
        $params['loginName'] = '';
        $params['user_autofocus'] = true;
        $params['messages'] = [];
        $params['redirect_url'] = null;
        $params['resetPasswordLink'] = null;
        $params['alt_login'] = [];
        $params['rememberLoginAllowed'] = true;
        $params['rememberLoginState'] = 0;

        $params['accessLink'] = '';
        $params['licenseMessage'] = '';
        $params['strictLoginEnforced'] = false;

        return $this->show($params);
    }
    public function showRedirect()
    {
        $params = [
            'messages' => ['身份验证' => '访问前请先登录']
        ];

        return $this->show($params);
    }

    private function procLogin()
    {
        $post = Request::post();
        if (!Request::isPost() || !isset($post) || (\is_array($post) && count($post) <= 0)) {
            throw new DisplayException(400, '非法调用');
        }

        if (!isset($post['user']) || !isset($post['password'])){
            throw new DisplayException(400, '参数错误');
        }

        if (null == AccountManager::tryLogin($post['user'], $post['password'])){

        }else{
            //登录成功
            return redirect('/Index');
        }
    }

    public function show($_params)
    {
        $params = [
            'loginName' => '',
            'user_autofocus' => true,
            'messages' => [],
            'redirect_url' => null,
            'resetPasswordLink' => null,
            'alt_login' => [],
            'rememberLoginAllowed' => true,
            'rememberLoginState' => false,
            'accessLink' => null,
            'licenseMessage' => '',
            'strictLoginEnforced' => false,
        ];

        $params = \array_merge($params, $_params);

        $this->setFooter();

        View::assign('name','HomeCloud');
        View::assign('email','raphael@HomeServer.com');
        $params['_bodyid'] = 'body-login';

        $additional_js = [
            'vendor/jsTimezoneDetect/jstz.js',
            'js/visitortimezone.js',
            'js/lostpassword.js',
            'js/login.js',
            'js/browser-update.js',
            'vendor/browser-update/browser-update.js',
        ];

        $base_dep = Base::getViewDepend();

        //将附加文件路径结合访问的url组合成绝对网址
        $params['cssfiles'] = [];
        foreach($base_dep['cssfiles'] as $css){
            $params['cssfiles'][] = $css;
        }
        
        $params['jsfiles'] = [];
        foreach($base_dep['jsfiles'] as $js){
            $params['jsfiles'][] = $js;
        }

        foreach($additional_js as $js){
            $params['jsfiles'][] = Base::getJsPath($js);
        }

        foreach($params as $param_key => $param_val){
            View::assign($param_key, $param_val);
        }

        $this->setContent('Index/login');

        // 模板输出
        return View::fetch('Index/LayoutGuest');
    }

    private function setFooter()
    {

        $footer  = '<a href="baidu.com" target="_blank" rel="noreferrer">' . $GLOBALS['baseinfo']['title'] . '</a>';
        $footer .= ' &ndash; 您的私有资源管理专家';

		View::assign('_footer', $footer);
    }

    private function setContent($content_name)
    {
        $content = View::fetch($content_name);
        View::assign('_content', $content);
    }
}