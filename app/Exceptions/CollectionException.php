<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class CollectionException extends Exception
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

    public function report(): bool
    {
        return true;
    }
}
