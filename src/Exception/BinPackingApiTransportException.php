<?php declare(strict_types = 1);

namespace App\Exception;

use Throwable;

final class BinPackingApiTransportException extends BinPackingApiException implements RecoverablePackingException
{

    public function __construct(
        string $message,
        ?Throwable $previous = null,
    )
    {
        parent::__construct($message, null, $previous);
    }

}
