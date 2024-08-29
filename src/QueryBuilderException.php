<?php

namespace mvbsoft\queryManager;

use Exception;

/**
 * Class QueryBuilderException
 *
 * This exception is used to report errors that occur in the QueryBuilder class.
 */
class QueryBuilderException extends Exception
{
    /**
     * Adds the ability to log the error or perform other actions when the exception is thrown.
     *
     * @param string $message The error message
     * @param int $code The error code
     * @param Exception|null $previous The previous exception for exception chaining (if any)
     */
    public function __construct($message = "", $code = 0, Exception $previous = null)
    {
        // Call the parent constructor
        parent::__construct($message, $code, $previous);
    }

    /**
     * Override the method to get the string representation of the exception.
     *
     * @return string String representation of the exception
     */
    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}
