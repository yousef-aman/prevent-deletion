<?php

namespace YousefAman\PreventDeletion\Exceptions;

use Exception;

class PreventDeletionException extends Exception
{
    /**
     * Create a new PreventDeletionException instance.
     *
     * @param  string  $message
     * @param  int  $code
     * @param  \Exception|null  $previous
     * @return void
     */
    public function __construct($message = "", $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
