<?php

namespace app\lib;

class ErrorPool
{
    protected static $pool = [];
    public static function append($title, $content)
    {
        if (!isset(self::$pool[$title])){
            self::$pool[$title] = [];
        }
        self::$pool[$title][] = $content;
    }

    public static function count(){
        return count(self::$pool);
    }

    public static function clear(){
        self::$pool = [];
    }

    public static function getall(){
        $result = self::$pool;
        self::clear();
        return $result;
    }

    public static function getSimpleAll()
    {
        $msgs = [];

        foreach(self::$pool as $title => $entries){
            foreach($entries as $entry){
                $msgs[] = $title . '，' . $entry;
            }
        }

        self::clear();
        return $msgs;
    }
}