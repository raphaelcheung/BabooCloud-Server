<?php
namespace app\lib;

use think\Validate;
use think\facade\Config;

class ValidateHelper
{
    private const RULE_UID = 'regex:[\w]+|length:4,16';
    private const RULE_PASSWORD = 'alphaDash|length:6,16';
    private const RULE_MD5 = 'regex:^[a-z0-9]{32}$';
    private const RULE_MOBILE = 'mobile';
    private const RULE_EMAIL = 'email';

    private Validate $_Validator;
    private $_ExtRules = [];

    public function __construct()
    {
        $this->_Validator = new Validate();
    }

    //是否包含无法打印的字符和无效的unicode字符
    public static function checkFunkyWhiteSpace($path)
    {
        if (preg_match('#\p{C}+#u', $path)) {
            return false;
        }

        return true;
    }

    public static function checkRelativePath_($data, $key, $require = false)
    {
        if (!isset($data[$key])){
            if ($require == true){
                return '不能为空';
            }

            return true;
        }

        return self::checkRelativePath($data[$key]);
    }

    public static function checkRelativePath($path)
    {
        if ($path === '/' || $path === ''){
            return true;
        }

        if (!static::checkFunkyWhiteSpace($path)){
            return '不能包含特殊字符';
        }

        if (strpos($path, '\\') != false ||
            strpos($path, ':') != false ||
            strpos($path, '*') != false ||
            strpos($path, '?') != false ||
            strpos($path, '<') != false ||
            strpos($path, '>') != false ||
            strpos($path, '|') != false ||
            strpos($path, '//') != false) {
            return '不能包含特殊字符';
        }

        $parts = explode('/', $path);
        foreach($parts as $part){
            if ($part === '.' || $part === '..' || ctype_space($part)){
                return false;
            }
        }

        return true;
    }

    public static function checkRelativeFilePath_($data, $key, $require = false)
    {
        if (!isset($data[$key])){
            if ($require == true){
                return '不能为空';
            }

            return true;
        }

        $path = $data[$key];

        if ($path === '/' || $path === ''){
            return false;
        }

        if (!static::checkFunkyWhiteSpace($path)){
            return '不能包含特殊字符';
        }

        if (strpos($path, '\\') != false ||
            strpos($path, ':') != false ||
            strpos($path, '*') != false ||
            strpos($path, '?') != false ||
            strpos($path, '<') != false ||
            strpos($path, '>') != false ||
            strpos($path, '|') != false ||
            strpos($path, '//') != false) {
            return '不能包含特殊字符';
        }

        $parts = explode('/', $path);
        $index = 0;
        foreach($parts as $part){
            if ($part === '.' || $part === '..'){
                return false;
            }

            if ($index > 0 && ctype_space($part)){
                return false;
            }

            $index ++;
        }

        return true;
    }

    public function addRelativePath($key, $require = false)
    {
        $this->_ExtRules[] = function($data) use($key, $require) {
            return ValidateHelper::checkRelativePath_($data, $key, $require);
        };
    }

    public function addRelativeFilePath($key, $require = false)
    {
        $this->_ExtRules[] = function($data) use($key, $require) {
            return ValidateHelper::checkRelativeFilePath_($data, $key, $require);
        };
    }

    public function addRule($key, $rule, $require = false)
    {
        if ($require == true){
            $rule = $rule . '|require';
        }

        $this->_Validator->rule($key, $rule);
    }

    public function addUID($key, $require = false)
    {
        $this->addRule($key, static::RULE_UID, $require);
    }

    public function addPassword($key, $require = false)
    {
        $this->addRule($key, static::RULE_PASSWORD, $require);
    }

    public function addMobile($key, $require = false)
    {
        $this->addRule($key, static::RULE_MOBILE, $require);
    }

    public function addMD5($key, $require = false)
    {
        $this->addRule($key, static::RULE_MD5, $require);
    }

    public function addEmail($key, $require = false)
    {
        $this->addRule($key, static::RULE_EMAIL, $require);
    }

    public function check($data)
    {
        $result = $this->_Validator->check($data);
        if(!$result){
            return $this->_Validator->getError();
        }

        foreach($this->_ExtRules as $rule){
            if (!$rule($data)){
                return false;
            }
        }

        return true;
    }
}
