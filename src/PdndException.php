<?php
// filepath: src/PdndException.php
namespace Pdnd\Client;

use Exception;

class PdndException extends Exception
{
    private $errorCode;

    public function __construct($message, $errorCode = 0)
    {
        parent::__construct($message, $errorCode);
        $this->errorCode = $errorCode;
    }

    public function getErrorCode()
    {
        return $this->errorCode;
    }
}