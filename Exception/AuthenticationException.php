<?php

namespace Akeneo\SalesForce\Exception;

class AuthenticationException extends \Exception
{
    /**
     * @var string
     */
    private $errorCode;

    /**
     * @param string $errorCode
     * @param string $message
     */
    public function __construct($errorCode, $message)
    {
        $this->errorCode = $errorCode;

        parent::__construct($message);
    }

    /**
     * @return string
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }
}
