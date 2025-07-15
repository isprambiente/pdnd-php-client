<?php
/**
 * @package Pdnd
 * @name PdndException
 * @license MIT
 * @file PdndException.php
 * @brief Custom exception class per la gestione degli errori in the PDND client.
 * @author Francesco Loreti
 * @mailto francesco.loreti@isprambiente.it
 * @first_release 2025-07-13
 */

namespace Pdnd;
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