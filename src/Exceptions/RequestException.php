<?php namespace Crunch\Salesforce\Exceptions;

class RequestException extends \Exception {

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
     * @param        $requestBody
     */
    public function __construct($message, $requestBody)
    {
        $this->requestBody = $requestBody;
        $error = json_decode($requestBody, true);
        $this->errorCode = $error['errorCode'];
        parent::__construct($error['message']);
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
