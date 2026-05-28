<?php

namespace App\Exception;

final class BinPackingApiServerException extends BinPackingApiException implements RecoverablePackingException
{
    public function __construct(int $statusCode)
    {
        parent::__construct('Packing API server error.', $statusCode);
    }
}
