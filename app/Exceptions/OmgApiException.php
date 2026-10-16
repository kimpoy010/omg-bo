<?php

namespace App\Exceptions;

use RuntimeException;

// An error reported by (or while reaching) the OMG API. The message is already
// merchant-readable; $omgCode is OMG's numeric code when it sent one.
class OmgApiException extends RuntimeException
{
    public function __construct(string $message, public readonly ?int $omgCode = null, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
