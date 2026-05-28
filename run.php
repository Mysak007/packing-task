<?php

use App\ApplicationFactory;
use Doctrine\ORM\EntityManager;
use GuzzleHttp\Psr7\Message;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;

/** @var EntityManager $entityManager */
$entityManager = require __DIR__ . '/src/bootstrap.php';

if (!isset($argv[1])) {
    fwrite(STDERR, "Usage: php run.php '<json>'\n");
    exit(1);
}

$request = new Request('POST', new Uri('http://localhost/pack'), ['Content-Type' => 'application/json'], $argv[1]);

$application = ApplicationFactory::create($entityManager);
$response = $application->run($request);

echo "<<< In:\n" . Message::toString($request) . "\n\n";
echo ">>> Out:\n" . Message::toString($response) . "\n\n";
