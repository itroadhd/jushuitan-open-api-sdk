<?php

namespace Jushuitan\OpenSDK\Exception;

use Exception;

class JushuitanException extends Exception
{
    /**
     * @param string $message 错误信息
     * @param int $code 错误码
     * @param Exception|null $previous 上一个异常
     */
    public function __construct(string $message = '', int $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}