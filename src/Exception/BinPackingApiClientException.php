<?php declare(strict_types = 1);

namespace App\Exception;

final class BinPackingApiClientException extends BinPackingApiException
{

    public function __construct(int $statusCode)
    {
        parent::__construct('Packing API rejected the request.', $statusCode);
    }

}
