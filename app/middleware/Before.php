<?php

namespace app\middleware;
use app\lib\AccountManager;
use think\Request;
use think\facade\Config;
use app\lib\Base;
use app\lib\User;
use app\lib\DebugException;
use app\lib\DisplayException;

class Before
{
    public function __construct()
    {
        Base::preBaseInfo();
    }

    public function handle(Request $request, \Closure $next)
    {
        $url = $request->url(false);

        if (strncmp($url, '/static/', 8) == 0){
            return $next($request);
        }

        if ($request->isAjax()){
            $isInited = Config::get('mycloud.isinited', false);
            if (!$isInited){
                return json('服务器未经初始化', 503);
            }

            $token = $request->header('logintoken');

            if (isset($token)){
                $login_result = AccountManager::autoApiLogin($token);
                if (!isset($login_result)){
                    return json('身份验证失败', 403);
                }

                $request->account = $login_result['account'];
                $request->token = $login_result['token'];

                $user = User::getUser($login_result['account']);
                if (isset($user)){
                    $request->user = $user;
                }
            }else{
                return json('未验证身份', 401);
            }

        }else{
            //首次运行要做安装
            $isInited = Config::get('mycloud.isinited', false);
            if (!$isInited && strcmp($url, '/setup') != 0){
                return redirect('/setup');
            }

            if ($isInited){
                //尝试免登录
                $login_result = AccountManager::autoLogin();
                $isLogon = false;
                if (isset($login_result)){
                    $request->account = $login_result['account'];
                    $request->token = $login_result['token'];

                    $user = User::getUser($login_result['account']);
                    if (isset($user)){
                        $request->user = $user;
                    }

                    $isLogon = true;
                }
        
                $url = $request->url(false);
                if (!$isLogon && strncmp($url, '/login', 6) != 0){
                    return redirect('/login/showRedirect');
                }
            }
        }

        return $next($request);
    }
}