<?php
namespace app\lib;

use think\exception\HttpException;

class DisplayException extends HttpException
{
    public function __construct(int $statusCode, string $message = '', Exception $previous = null, array $headers = [], $code = 0)
    {
        parent::__construct($statusCode, $message, $previous, $headers, $code);
    }
}