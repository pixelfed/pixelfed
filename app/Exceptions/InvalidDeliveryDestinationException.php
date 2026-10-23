<?php

namespace App\Exceptions;

use App\Util\ActivityPub\Helpers;
use Exception;

/**
 * An ActivityPub inbox URL was rejected before any request was made.
 *
 * Carries the Helpers::URL_* reason for the rejection. Callers need it to
 * decide whether the failure says anything about the remote host: a
 * malformed or banned inbox url is a fact about that one row, while an
 * unresolvable host is a fact about the whole domain. Charging the first
 * kind to DeliveryHostService marks a healthy domain unavailable and
 * suppresses delivery to every other valid inbox on it.
 */
class InvalidDeliveryDestinationException extends Exception
{
    public function __construct(
        string $message,
        public readonly string $reason = Helpers::URL_MALFORMED
    ) {
        parent::__construct($message);
    }
}
