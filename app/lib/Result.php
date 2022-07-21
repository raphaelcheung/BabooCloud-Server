<?php
namespace app\lib;

class Result
{
    public function __construct(int $code, string $msg)
    {
        $this->_code = $code;
        $this->_msg = $msg;
    }

    public function code()
    {
        return $this->_code;
    }

    public function msg()
    {
        return $this->_msg;
    }

    private int $_code;
    private string $_msg;
}