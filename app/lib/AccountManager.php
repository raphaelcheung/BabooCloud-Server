<?php

namespace app\lib;
use app\model\Account;
use app\validate\Account as validate;
use think\facade\Cookie;
use think\facade\Session;
use think\facade\Config;
use app\lib\DebugException;
use app\lib\DisplayException;

class AccountManager
{
    protected static $account_pool = [];

    public static function hasSuperAdminAccount()
    {
        return !Account::where('level', 0)->findOrEmpty()->isEmpty();
    }

    public static function hasAccount($uid){
        return !Account::where('uid', $uid)->findOrEmpty()->isEmpty();
    }

    public static function createAccount($account)
    {
        //获取加密信息
        if (!isset($GLOBALS['salt']) || !isset($GLOBALS['secret'])){
            throw new DebugException('没有设置加密密钥', 500);
        }

        //用户名转换成小写
        $account['uid'] = strtolower($account['uid']);
        $account['account_quota'] = Base::getSizeFromString(Config::get('mycloud.default_quota', '2T'));

        //检测新账号的信息是否有效

        if (self::hasAccount($account['uid'])){
            throw new DisplayException(400, '用户名已被占用');
        }

        $check = new validate;
        if (!$check->check($account)){
            throw new DisplayException(400, $check->getError());
        }

        //对敏感信息加密
        $uid_hash = md5($account['uid']);

        $salt = Base::genRandom(4);
        $password_md5 = md5($account['password']);
        $password_salt = sprintf('%s%s%s', $password_md5, $salt, $GLOBALS['salt']);
        $password = md5($password_salt);

        if (!$password){
            throw new DebugException(500, '密码无法生成散列值');
        }

        $newaccount = new Account;
        $newaccount->uid = $account['uid'];
        $newaccount->nickname = isset($account['nickname']) ? $account['nickname'] : '';
        $newaccount->telephone = isset($account['telephone']) ? $account['telephone'] : '';
        $newaccount->email = isset($account['email']) ? $account['email'] : '';
        $newaccount->level = $account['level'];
        $newaccount->createtime = time();
        $newaccount->salt = $salt;
        $newaccount->password = $password;
        $newaccount->uid_hash = $uid_hash;
        $newaccount->account_quota = $account['account_quota'];

        //在保存数据前先创建硬盘目录，失败后不用回滚数据库
        FileSystem::createRootFolder($account['uid']);


        //保存数据库
        $newaccount->save();

        return $newaccount;
    }
    
    public static function createSuperAdminAccount($account)
    {
        $account['level'] = 0;
        if (!isset($account['nickname'])){
            $account['nickname'] = '超级管理员';
        }

        return self::createAccount($account);
    }

    public static function tryLogin($user, $password)
    {
        //去数据库查找该用户
        $account = Account::where('uid', $user)->find();
        if (isset($account)){
            //组合并生成密码散列串
            $password_salt = $password . $account->salt . $GLOBALS['salt'];
            $password_md5 = md5($password_salt);

            //匹配验证密码散列串后，往session存放登录态，往cookie存放token
            if (strcmp($account->password, $password_md5) == 0){
                $now = time();
                self::$account_pool[$user] = $account;
                $session = [];
                $session['uid'] = $user;
                $session['lastlogintime'] = $now;
                $session['login_expired'] = $now + 3600 * 24 * 7;
                $session['token_expired'] = $now + 3600 * 24 * 1;

                $token = md5($user . strval($now) . Base::genRandom(4));
                Session::set($token, $session);
                Cookie::set('BabooCloud', $token);
                return [
                    'account' => $account,
                    'token' => $token,
                ];
            }else{
                throw new DisplayException(400, '密码错误');
            }
        }else{
            throw new DisplayException(400, '该用户不存在');
        }

        return null;
    }

    public static function logout()
    {
        $token = Cookie::get('BabooCloud');
        Cookie::delete('BabooCloud');

        if (isset($token)){
            $session = Session::pull($token);

            if (isset($session)){
                self::$account_pool[$session['uid']] = null;
            }
        }
    }

    public static function autoApiLogin($token)
    {
        $session = Session::get($token);

        if (isset($session) && \is_array($session)){
            $now = time();
            $uid = $session['uid'];

            $token_expired = &$session['token_expired'];
            $login_expired = &$session['login_expired'];

            //检查登录是否过期
            if ($now < $login_expired){
                //查找账号缓存，如没有则从数据库取
                if (!isset(self::$account_pool[$uid])){
                    $account = Account::where('uid', $uid)->find();

                    if (isset($account)){
                        self::$account_pool[$uid] = $account;
                    }else{
                        Session::delete($token);
                        return null;
                    }
                }

                //如果token过期则更换
                if ($now >= $token_expired){
                    $newtoken = md5($uid . strval($now) . Base::genRandom(4));
                    $token_expired = $now + 3600 * 24;

                    Session::delete($token);
                    Session::set($newtoken, $session);
                    $token = $newtoken;
                }

                //返回token和账号信息
                return [
                    'account' => $account,
                    'token' => $token,
                ];

            }else{//登录已过期
                Session::delete($token);
            }
        }

        return null;
    }

    public static function autoLogin()
    {
        $token = Cookie::get('BabooCloud');

        if (isset($token)){
            $session = Session::get($token);

            if (isset($session) && \is_array($session)){
                $now = time();
                $uid = $session['uid'];
    
                $token_expired = &$session['token_expired'];
                $login_expired = &$session['login_expired'];
    
                //检查登录是否过期
                if ($now < $login_expired){
                    //查找账号缓存，如没有则从数据库取
                    if (!isset(self::$account_pool[$uid])){
                        $account = Account::where('uid', $uid)->find();
    
                        if (isset($account)){
                            self::$account_pool[$uid] = $account;
                        }else{
                            Session::delete($token);
                            Cookie::delete('BabooCloud');
                            return null;
                        }
                    }
    
                    //如果token过期则更换
                    if ($now >= $token_expired){
                        $newtoken = md5($uid . strval($now) . Base::genRandom(4));
                        $token_expired = $now + 3600 * 24;
    
                        Session::delete($token);
                        Session::set($newtoken, $session);
                        Cookie::set('BabooCloud', $newtoken);
                        $token = $newtoken;
                    }
    
                    //返回token和账号信息
                    return [
                        'account' => $account,
                        'token' => $token,
                    ];
    
                }else{//登录已过期
                    Session::delete($token);
                    Cookie::delete('BabooCloud');
                    return null;
                }
    
            }else{
                Cookie::delete('BabooCloud');
            }
        }
 
        return null;
    }
}