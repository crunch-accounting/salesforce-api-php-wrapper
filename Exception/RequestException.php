<?php

namespace Akeneo\SalesForce\Exception;

class RequestException extends \Exception
{
    /**
     * @var string
     */
    protected $errorCode;

    /**
     * @var string
     */
    private $requestBody;

    /**
     * @param string $message
     * @param string $requestBody
     */
    public function __construct($message, $requestBody)
    {
        $this->requestBody = $requestBody;
        $error             = json_decode($requestBody, true);

        //Errors generated during the auth stage are different to those generated during normal requests
        if (isset($error['error']) && isset($error['error_description'])) {
            $this->errorCode = $error['error'];
            parent::__construct($error['error_description']);
        } elseif (isset($error[0]['message'])) {
            $this->errorCode = $error[0]['errorCode'];
            parent::__construct($error[0]['message']);
        } else {
            $this->errorCode = $error['errorCode'];
            parent::__construct($error['message']);
        }
    }

    /**
     * @return mixed
     */
    public function getRequestBody()
    {
        return $this->requestBody;
    }

    /**
     * @return string
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }
}
