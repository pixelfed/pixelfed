<?php

namespace App\Exceptions;

use InvalidArgumentException;

/**
 * Thrown when an ActivityPub inbox URL fails validation.
 *
 * Kept distinct from other InvalidArgumentExceptions so delivery callers
 * can treat a bad or dead destination as expected fediverse churn rather
 * than a programming error.
 */
class InvalidDeliveryDestinationException extends InvalidArgumentException {}
