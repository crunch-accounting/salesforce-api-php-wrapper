<?php

namespace Akeneo\SalesForce\Exception;

class DuplicateDetectedException extends \Exception
{
    /**
     * @param array  $error
     * @param string $uri
     */
    public function __construct(array $error, $uri)
    {
        parent::__construct(sprintf('%s %s', $error[0]['message'], $uri));
    }
}
