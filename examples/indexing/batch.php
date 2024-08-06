<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

$account = new \Klevu\PhpSDK\Model\Account();
$accountCredentials = new \Klevu\PhpSDK\Model\AccountCredentials(
    jsApiKey: 'klevu-1234567890',
    restAuthKey: 'ABCDE1234567890',
);

$recordFactory = new \Klevu\PhpSDK\Model\Indexing\RecordFactory();
$batchService = new \Klevu\PhpSDK\Service\Indexing\BatchService(
    baseUrlsProvider: new \Klevu\PhpSDK\Provider\BaseUrlsProvider(
        account: $account,
    ),
    httpClient: new \Klevu\PhpSDK\Example\Http\Client(
        outputRequestAsText: true,
        outputRequestAsCurl: false,
    ),
    logger: new \Klevu\PhpSDK\Example\Log\Logger(
        outputMessageAsText: true,
    ),
);

try {
    $batchResponse = $batchService->send(
        accountCredentials: $accountCredentials,
        records: new \Klevu\PhpSDK\Model\Indexing\RecordIterator(
            array_map(
                static fn (array $recordData): \Klevu\PhpSDK\Api\Model\Indexing\RecordInterface => $recordFactory->create($recordData),
                json_decode(file_get_contents(__DIR__ . '/payload.json'), true)
            ),
        ),
    );
    print_r($batchResponse);
} catch (\Klevu\PhpSDK\Exception\ValidationException $exception) {
    echo $exception->getMessage() . PHP_EOL;
    foreach ($exception->getErrors() as $error) {
        echo '* ' . $error . PHP_EOL;
    }
} catch (\Exception $exception) {
    echo sprintf(
            '%s [%s] : %s',
            $exception::class,
            $exception->getCode(),
            $exception->getMessage(),
        ) . PHP_EOL;
    print_r($exception->getTrace());
}
