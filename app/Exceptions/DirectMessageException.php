<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

/**
 * A direct message action the user is not allowed to take, or asked for in a
 * way that cannot work. The message is safe to show and the code is the HTTP
 * status it renders as.
 */
class DirectMessageException extends Exception
{
    public function __construct(string $message, protected int $status = 422)
    {
        parent::__construct($message, $status);
    }

    public function status(): int
    {
        return $this->status;
    }

    public function render(): JsonResponse
    {
        return response()->json(['error' => $this->getMessage()], $this->status);
    }

    /**
     * Expected user errors, not worth a log line.
     */
    public function report(): bool
    {
        return true;
    }
}
