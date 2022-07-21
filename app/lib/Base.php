<?php
namespace app\lib;

use think\facade\Request;
use think\facade\Config;

class Base
{
    protected static $OrignalTable = ['A','B','C','D','E','F','G','H','I',
    'J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z',
    'a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q',
    'r','s','t','u','v','w','x','y','z','0','1','2','3','4','5','6','7',
    '8','9','!','\'','"','#','$','%','&','\\','\'','(',')','*','+',',','-',
    '.','/',':',';','<','=','>','?','@','[',']','^','_','`','{','|','}',
    '~'];

    protected static $isInit = false;

    public static function genRandom($length)
    {
        if (!self::$isInit){
            shuffle(self::$OrignalTable);
            array_reverse(self::$OrignalTable);
            shuffle(self::$OrignalTable);
            self::$isInit = true;
        }

        if ($length <= 0) {return null;}

        $result = '';
        $len = count(self::$OrignalTable) - 1;

        for($i = 0; $i < $length; $i++){
            $result .= self::$OrignalTable[mt_rand(0, $len)]; 
        }

        return $result;
    }

    public static function getFilesViewDepend()
    {
        $tmp = [];
        $tmp["cssfiles"] = [
            "tooltip.css",
            "jquery-ui-fixes.css",
            "vendor/jquery-ui/themes/base/jquery-ui.css",
            "mobile.css",
            "multiselect.css",
            "fixes.css",
            "global.css",
            "apps.css",
            "fonts.css",
            "icons.css",
            "header.css",
            "inputs.css",
            "styles.css",
            "jquery.ocdialog.css",
            "files/files.css",
            "files/upload.css",
            "files/mobile.css",
            "files/detailsView.css",
        ];

        $tmp["jsfiles"] = [
            "vendor/jquery/dist/jquery.min.js",
            "vendor/jquery-migrate/jquery-migrate.min.js",
            "vendor/jquery-ui/ui/jquery-ui.custom.js",
            "vendor/underscore/underscore.js",
            "vendor/moment/min/moment-with-locales.js",
            "vendor/handlebars/handlebars.js",
            "vendor/blueimp-md5/js/md5.js",
            "vendor/bootstrap/js/tooltip.js",
            "vendor/backbone/backbone.js",
            "vendor/es6-promise/es6-promise.auto.js",
            "vendor/davclient.js/lib/client.js",
            "vendor/clipboard/dist/clipboard.js",
            "vendor/bowser/src/bowser.js",
            "js/jquery.ocdialog.js",
            "js/oc-dialogs.js",
            "js/js.js",
            "js/octemplate.js",
            "js/eventsource.js",
            "js/config.js",
            "search/js/search.js",
            "js/oc-requesttoken.js",
            "js/apps.js",
            "js/mimetype.js",
            "js/mimetypelist.js",
            "vendor/snapjs/dist/latest/snap.js",
            "vendor/select2/select2.js",
            "vendor/backbone/backbone.js",
            "js/oc-backbone.js",
            "js/oc-backbone-webdav.js",
            "js/placeholder.js",
            "js/jquery.avatar.js",
            "vendor/strengthify/jquery.strengthify.js",
            "js/setup.js",
            "files/fileinfo.js",
            "files/client.js"
        ];

        return self::buildDependPath($tmp);
    }

    public static function getImagePath($input)
    {
        return Request::root(true) . '/static/img/' . $input;
    }

    public static function getCssPath($input)
    {
        return Request::root(true) . '/static/css/' . $input . '?v=' . $GLOBALS['version']['hash'];
    }

    public static function normalizeRelativePath($path)
    {
        $path = str_replace('\\', '/', $path);
        if (strpos($path, '/') == 0){
            $path = substr($path, 1);
        }

        if (strpos($path, '/') == strlen($path) - 1){
            $path = substr($path, 0, strlen($path) - 1);
        }

        if ($path === ''){
            return $path;
        }

        $parts = explode('/' , $path);
        $result = [];
        foreach($parts as $part){
            $result[] = trim($part);
        }

        return implode('/', $result);
    }

    public static function getJsPath($input)
    {
        return Request::root(true) . '/static/js/' . $input . '?v=' . $GLOBALS['version']['hash'];
    }

    public static function getRoute($input)
    {
        return Request::root(true) . $input;
    }

    public static function buildDependPath($input)
    {

        //用版本号将CSS、JS等文件名关联起来，以免客户端缓存导致改动不生效
        $result = [];
        $result['cssfiles'] = [];

        foreach($input['cssfiles'] as $css) {
            $newPath = self::getCssPath($css);
            $result['cssfiles'][] = $newPath;
        }
        
        $result['jsfiles'] = [];

        foreach($input['jsfiles'] as $js) {
            $newPath = self::getJsPath($js);
            $result['jsfiles'][] = $newPath;
        }

        return $result;
    }

    public static function getSizeFromString($input)
    {
        $base = intval($input);
        if (substr_compare($input, 'K', -1) === 0){
            return $base * 1024;
        }else if(substr_compare($input, 'M', -1) === 0){
            return $base * 1024 * 1024;
        }else if(substr_compare($input, 'G', -1) === 0){
            return $base * 1024 * 1024 * 1024;
        }else if(substr_compare($input, 'T', -1) === 0){
            return $base * 1024 * 1024 * 1024 * 1024;
        }else{ return $base; }
    }

    public static function getViewDepend()
    {
        $tmp = [];
        $tmp["cssfiles"] = [
            "select2.css",
            "styles.css",
            "inputs.css",
            "header.css",
            "icons.css",
            "fonts.css",
            "apps.css",
            "global.css",
            "fixes.css",
            "multiselect.css",
            "mobile.css",
            "jquery-ui.css",
            "jquery-ui-fixes.css",
            "tooltip.css",
            "strengthify.css",
            "jquery.ocdialog.css"
        ];

        $tmp["jsfiles"] = [
            "vendor/jquery/dist/jquery.min.js",
            "vendor/jquery-migrate/jquery-migrate.min.js",
            "vendor/jquery-ui/ui/jquery-ui.custom.js",
            "vendor/underscore/underscore.js",
            "vendor/moment/min/moment-with-locales.js",
            "vendor/handlebars/handlebars.js",
            "vendor/blueimp-md5/js/md5.js",
            "vendor/bootstrap/js/tooltip.js",
            "vendor/backbone/backbone.js",
            "vendor/es6-promise/es6-promise.auto.js",
            "vendor/davclient.js/lib/client.js",
            "vendor/clipboard/dist/clipboard.js",
            "vendor/bowser/src/bowser.js",
            "js/jquery.ocdialog.js",
            "js/oc-dialogs.js",
            "js/js.js",
            "js/octemplate.js",
            "js/eventsource.js",
            "js/config.js",
            "search/js/search.js",
            "js/oc-requesttoken.js",
            "js/apps.js",
            "js/mimetype.js",
            "js/mimetypelist.js",
            "vendor/snapjs/dist/latest/snap.js",
            "vendor/select2/select2.js",
            "vendor/backbone/backbone.js",
            "js/oc-backbone.js",
            "js/oc-backbone-webdav.js",
            "js/placeholder.js",
            "js/jquery.avatar.js",
            "vendor/strengthify/jquery.strengthify.js",
            "js/setup.js",
            "files/fileinfo.js",
            "files/client.js"
        ];

        return self::buildDependPath($tmp);
    }

    public static function getBaseInfo($account)
    {
        $tmp = [
            'cloudbase' => [
                'name' => $GLOBALS['baseinfo']['name'],
                'title' => $GLOBALS['baseinfo']['title'],
                'version' => $GLOBALS['version']['text'],
                'slogan' => $GLOBALS['baseinfo']['slogan'],
                'webroot' => Request::root(true),
            ],
            'user' => [
                'uid' => $account->uid,
                'nickname' => $account->nickname,
                'level' => $account->level,
            ],
        ];
        return \htmlentities(\json_encode($tmp));
    }

    public static function getBase()
    {
        self::preBaseInfo();
        return $GLOBALS['baseinfo'];
    }

    public static function preBaseInfo()
    {
        //准备一些全局需要的基础信息

        if (!isset($GLOBALS['baseinfo'])){
            $GLOBALS['baseinfo'] = Config::get('mycloud.base', [
                'name' => 'BabooCloud',
                'title' => 'BabooCloud',
                'slogan' => '您的私有资源管理专家'
            ]);
        }

        //$info = [];
        if (!isset($GLOBALS['database'])) {
            $GLOBALS['database'] = [				
                'type' => 'pdo',
                'call' => 'mysql',
                'name' => 'MySQL/MariaDB',
                'suport' => false];

            $working = false;
            $type = $GLOBALS['database']['type'];
            $call = $GLOBALS['database']['call'];

            if ($type === 'class') {
                $working = \class_exists($call);
            } elseif ($type === 'function') {
                $working = \is_callable($call);
            } elseif ($type === 'pdo') {
                $working = \in_array($call, \PDO::getAvailableDrivers(), true);
            }

            if ($working) {
                $GLOBALS['database']['suport'] = true;
            }
        }

        //版本相关信息
        if (!isset($GLOBALS['version']) || !isset($GLOBALS['version'][0])) {
            $GLOBALS['version'] = ['0' => Config::get('mycloud.version.0', 0),
                '1' => Config::get('mycloud.version.1', 0),
                '2' => Config::get('mycloud.version.2', 0)];
        }

        if (!isset($GLOBALS['version']['text'])) {  
            $GLOBALS['version']['text'] = $GLOBALS['version']['0'] . '.' . $GLOBALS['version']['1'] . '.' . $GLOBALS['version']['2'];
        }

        if (!isset($GLOBALS['version']['hash'])) {
            $GLOBALS['version']['hash'] = hash('ripemd160', $GLOBALS['version']['text']);
        }

        if (!isset($GLOBALS['serverpath']))
        {
            if (!(substr_compare($_SERVER['DOCUMENT_ROOT'], '/', -1) === 0 || substr_compare($_SERVER['DOCUMENT_ROOT'], '\\', -1) === 0))
            { $GLOBALS['serverpath'] = $_SERVER['DOCUMENT_ROOT'] . '/'; }
            else
            { $GLOBALS['serverpath'] = $_SERVER['DOCUMENT_ROOT']; }

            $GLOBALS['directory'] =  Config::get('mycloud.datapath', $GLOBALS['serverpath'] . 'data/');
        }
            
        if (!isset($GLOBALS['directory'])) {
            $GLOBALS['directory'] =  Config::get('mycloud.datapath', $GLOBALS['serverpath'] . 'data/');
        }

        if (!isset($GLOBALS['salt'])){
            $GLOBALS['salt'] = Config::get('mycloud.salt', null);
        }

        if (!isset($GLOBALS['secret'])){
            $GLOBALS['secret'] = Config::get('mycloud.secret', null);
        }
    }
}       