<?php declare(strict_types = 1);

namespace App;

use App\Infrastructure\ErrorLogger;
use App\Repository\PackagingRepository;
use App\Repository\PackingCacheRepository;
use App\Service\BinPackingApiClient;
use App\Service\BinPackingRequestMapper;
use App\Service\BinPackingResponseSelector;
use App\Service\FallbackPackingCalculator;
use App\Service\JanedbalBinPackingApi;
use App\Service\PackingCacheKeyGenerator;
use App\Service\PackingService;
use App\Validator\PackingRequestValidator;
use Doctrine\ORM\EntityManager;
use GuzzleHttp\Client;

final class ApplicationFactory
{

    public static function create(EntityManager $entityManager): Application
    {
        $errorLogger = new ErrorLogger();
        $packagingRepository = new PackagingRepository($entityManager);
        $cacheRepository = new PackingCacheRepository(
            $entityManager,
            new PackingCacheKeyGenerator(),
        );
        $httpClient = new Client([
            'timeout' => 2.0,
            'connect_timeout' => 1.0,
            'http_errors' => false,
        ]);
        $apiClient = new BinPackingApiClient(
            new JanedbalBinPackingApi($httpClient),
            new BinPackingRequestMapper(),
            new BinPackingResponseSelector(),
        );
        $fallbackCalculator = new FallbackPackingCalculator();
        $packingService = new PackingService(
            $packagingRepository,
            $cacheRepository,
            $apiClient,
            $fallbackCalculator,
            $errorLogger,
        );

        return new Application(
            new PackingRequestValidator(),
            $packingService,
            $errorLogger,
        );
    }

}
