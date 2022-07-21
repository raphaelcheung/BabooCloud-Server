<?php
namespace app\controller;

use app\BaseController;
use app\model\Account;
use think\facade\Request;
use app\lib\Base;
use app\lib\DbSystem;
use app\lib\ErrorPool;
use think\facade\Config;
use app\lib\AccountManager;
use think\facade\View;
use app\lib\DebugException;
use app\lib\DisplayException;

class SetupController extends BaseController
{
    private function procSetup()
    {
        $post = Request::post();
        if (!isset($post) || (\is_array($post) && count($post) <= 0)) {
            throw new DisplayException(400, '非法调用');
        }

        if (isset($post['install']) and $post['install']=='true') {
            if (empty($post['directory'])) {
                throw new DisplayException(400, '参数错误');
            }

            if (!isset($post['dbtype']) || strcmp($post['dbtype'], 'mysql') !== 0){
                throw new DisplayException(400, '目前数据库只支持mysql/mariadb');
            }

            $username = \htmlspecialchars_decode($post['adminlogin']);
            $password = \htmlspecialchars_decode($post['adminpass']);
            $dataDir = \htmlspecialchars_decode($post['directory']);

            //检查存储路径
            $dataDir = \str_replace('\\', '/', $dataDir);
            if (
                (!\is_dir($dataDir) and !\mkdir($dataDir)) or
                !\is_writable($dataDir)
            ) {
                throw new DisplayException(400, "指定的数据目录不可创建或没有写权限 " . $dataDir);
            }

            if (substr_compare($dataDir, '/', -1) != 0){
                $dataDir .= '/';
            }

            $GLOBALS['directory'] =  $dataDir;

            //生成服务器密钥串
            $salt = Base::genRandom(32);
            $GLOBALS['salt'] = $salt;

            $secret = Base::genRandom(48);
            $GLOBALS['secret'] = $secret;

            Config::setValue($secret, 'mycloud.secret');
            Config::setValue($salt, 'mycloud.salt');
            Config::setValue($dataDir, 'mycloud.datapath');
            Config::saveJSON('mycloud');

            //数据库信息写入配置
            Config::setValue($post['dbuser'], 'database.connections.mysql.username');
            Config::setValue($post['dbpass'], 'database.connections.mysql.password');
            Config::setValue($post['dbname'], 'database.connections.mysql.database');
            Config::setValue($post['dbhost'], 'database.connections.mysql.hostname');
            Config::setValue($post['dbport'], 'database.connections.mysql.hostport');

            Config::saveJSON('database');

            $new_account = [];
            $new_account['uid'] = trim($username);
            $new_account['password'] = $password;

            //创建管理员账号
            AccountManager::createSuperAdminAccount($new_account);
            
            //初始化网盘目录
            $result = DbSystem::createRootFolder($new_account['uid']);
            if ($result != true){

            }


            Config::setValue(true, 'mycloud.isinited');
            Config::saveJSON('mycloud');

            return redirect('/login');
        }
    }
    
    public function index()
    {

        if (Request::isPost()){
            return $this->procSetup();
        }


        // 模板变量赋值
        $params = [
            'adminlogin' => 'admin',
            'dbuser' => Config::get('database.connections.mysql.username'),
            'dbpass' => Config::get('database.connections.mysql.password'),
            'dbname' => Config::get('database.connections.mysql.database'),
            'dbhost' => Config::get('database.connections.mysql.hostname'),
            'dbport' => Config::get('database.connections.mysql.hostport'),
        ];

        return $this->show($params);

    }

    public function showErrors($_params, $msgs)
    {
        $params = [
            'adminlogin' => $_params['adminlogin'],
            'dbuser' => $_params['dbuser'],
            'dbname' => $_params['dbname'],
            'dbhost' => $_params['dbhost'],
            'dbport' => $_params['dbport'],
            'messages' => $msgs,
        ];
        return $this->show($params);
    }

    public function show($_params)
    {
        $params = [
			'adminlogin' => '',
			'adminpass' => '',
			'dbuser' => '',
			'dbpass' => '',
			'dbname' => '',
			'dbhost' => 'localhost',
            'dbport' => '3306',
            'messages' => [],
            'directory' => '',
		];

        $params = \array_merge($params, $_params);

        $this->setFooter();

        View::assign('name','HomeServer');
        View::assign('email','raphael@HomeServer.com');
        $params['directory'] = $GLOBALS['directory'];
        $params['database'] = $GLOBALS['database'];


        $additional_js = [
            'js/jquery-showpassword.js',
            'js/installation.js',
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

        View::assign('_bodyid', 'body-login');

        $this->setContent('Index/setup');

        // 模板输出
        return View::fetch('Index/LayoutGuest');
    }

    private function setFooter()
    {

        $footer  = '<a href="baidu.com" target="_blank" rel="noreferrer">' . $GLOBALS['baseinfo']['title'] . '</a>';
        $footer .= ' &ndash; 您的私有资源管理专家';

/*         if ($this->getImprintUrl() !== '') {
            $footer .= '<span class="nowrap"> | <a href="baidu.com" target="_blank">' . $this->l->t('Imprint') . '</a></span>';
        }

        if ($this->getPrivacyPolicyUrl() !== '') {
            $footer .= '<span class="nowrap"> | <a href="'. $this->getPrivacyPolicyUrl() .'" target="_blank">'. $this->l->t('Privacy Policy') .'</a></span>';
        } */

		View::assign('_footer', $footer);
    }

    private function setContent($content_name)
    {
        $content = View::fetch($content_name);
        View::assign('_content', $content);
    }
}