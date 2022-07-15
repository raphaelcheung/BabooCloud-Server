<?php
namespace app\controller;

use app\BaseController;
use think\Request;
use app\lib\Base;
use think\facade\View;
use app\lib\DebugException;
use app\lib\DisplayException;

class IndexController extends BaseController
{
    public function index(Request $request)
    {

        return $this->show($request);
    }


    private function show(Request $request, $input = null)
    {

        $cssfiles = [
            //base
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
            "vendor/select2/select2.css",            
            
            "jquery.ocdialog.css",

            //files view
            "files/files.css",
            "files/upload.css",
            "files/mobile.css",
            "files/detailsView.css",    
            "webuploader.css",            
        ];

        $jsfiles = [
            //base
            //"../../jsconfig.php",
            
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

            'js/baseinfo.js',

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
            //"js/backgroundjobs.js",


            "js/user.js",
            "js/jquery.colorbox.js",
            "js/firstrunwizard.js",

            "files/fileinfo.js",
            "files/client.js",

            //files view
            "files/app.js",
            "files/file-upload.js",
            "files/newfilemenu.js",
            "files/jquery.fileupload.js",
            "files/jquery-visibility.js",
            "files/fileinfomodel.js",
            "files/filesummary.js",
            "files/breadcrumb.js",
            "files/filelist.js",
            //"files/search.js",
            "files/favoritesfilelist.js",
            "files/tagsplugin.js",
            "files/favoritesplugin.js",
            
            "files/detailfileinfoview.js",
            "files/detailtabview.js",
            "files/mainfileinfodetailview.js",
            "files/detailsview.js",
            
            "vendor/handlebars/handlebars.js",
            "files/fileactions.js",
            "files/fileactionsmenu.js",
            "files/fileactionsapplicationselectmenu.js",
            "files/files.js",
            "files/keyboardshortcuts.js",
            "files/navigation.js", 

            "files/taskdownlist.js",
            "files/taskuplist.js",
            "webuploader/webuploader.js"
        ];


        $account = $request->account;
        $user = $request->user;
        if (isset($user)){
            $used_info = $user->getInfo();
        }

        $params = [
            'cssfiles' => [], 
            'jsfiles' => [],
            'name' => 'HomeCloud',
            'email' => 'a@daf.com',
            'bodyid' => 'body-user',
            'appid' => 'files',
            'application' => '文件',
            'user_uid' => isset($account) ? $account->uid : '',
            'user_displayname' => isset($account) ? (empty($account->nickname) ? $account->uid : $account->nickname ) : '',
            'settingsnavigation' => [],
            'baseinfo' => Base::getBaseInfo($account),
            'token' => $request->token,

            'freeSpace' => isset($used_info) ? $used_info['quota'] - $used_info['used'] : 0,
            'usedSpacePercent' => isset($used_info) ? $used_info['percent'] : 0,
            'owner' =>  isset($account) ? $account->uid : '',
            'ownerDisplayName' => isset($account) ? (empty($account->nickname) ? $account->uid : $account->nickname ) : '',
            'fileNotFound' => 0,
            'mailNotificationEnabled' => 'no',
            'mailPublicNotificationEnabled' => 'no',
            'socialShareEnabled' => 'yes',
            'allowShareWithLink' => 'yes',
            'defaultFileSorting' => 'name',
            'defaultFileSortingDirection' => 'asc',
            'showHiddenFiles' => 1,
            'isPublic' => false,

            'navigation' => [
                [
                    'id' => 'files',
                    'name' => '文件',
                    'href' => '',
                    'icon' => Base::getImagePath('app.svg'),
                ],
                [
                    'id' => 'activity',
                    'name' => '动态',
                    'href' => '',
                    'icon' => Base::getImagePath('activity.svg'),
                ],
                [
                    'id' => 'market',
                    'name' => '市场',
                    'href' => '',
                    'icon' => Base::getImagePath('market.svg'),
                ],
            ],
        ];
        
        $params['settingsnavigation'][] = [
            'id' => 'setting',
            'order' => 1,
            'href' => Base::getRoute(''),
            'name' => '个人设置',
            'icon' => Base::getImagePath('admin.svg'),
        ];

        if (isset($account) && $account->level <= 1){
            $params['settingsnavigation'][] = [
                'id' => 'core_users',
                'order' => 2,
                'href' => Base::getRoute(''),
                'name' => '用户管理',
                'icon' => Base::getImagePath('users.svg'),
            ];
        }

        if (isset($input)){
            $params = array_merge($params, $input);
        }

        foreach($cssfiles as $css){
            $params['cssfiles'][] = Base::getCssPath($css);
        }

        foreach($jsfiles as $js){
            $params['jsfiles'][] = Base::getJsPath($js);
        }

        foreach($params as $param_key => $param_val){
            View::assign($param_key, $param_val);
        }

        $content = View::fetch('Index/main');
        View::assign('content', $content);
        return View::fetch('Index/LayoutUser');
    }
}