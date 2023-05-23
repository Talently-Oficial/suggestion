<?php

namespace Suggestion\Exceptions;

use Exception;

class SuggestionServiceException extends \Exception
{
    protected $errorCode;

    public function __construct(string $message, string $errorCode, int $code = 0, Exception $previous = null) {
        parent::__construct($message, $code, $previous);
        $this->errorCode = $errorCode;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }
}