<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

$accountCredentials = new \Klevu\PhpSDK\Model\AccountCredentials(
    jsApiKey: 'klevu-1234567890',
    restAuthKey: 'ABCDE1234567890',
);
$attributesService = new \Klevu\PhpSDK\Service\Indexing\AttributesService(
    httpClient: new \Klevu\PhpSDK\Example\Http\Client(
        outputRequestAsText: true,
        outputRequestAsCurl: false,
    ),
    logger: new \Klevu\PhpSDK\Example\Log\Logger(
        outputMessageAsText: true,
    ),
);

const ACTION = 'delete';
switch (ACTION) {
    case 'get':
        try {
            $existingAttributes = $attributesService->get(
                accountCredentials: $accountCredentials,
            );
            print_r($existingAttributes);
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
        }
        break;

    case 'put':
        try {
            $attributeFactory = new \Klevu\PhpSDK\Model\Indexing\AttributeFactory();
            $attribute = $attributeFactory->create([
                'attributeName' => 'new_attribute',
                'datatype' => \Klevu\PhpSDK\Model\Indexing\DataType::STRING->value,
                'label' => [
                    'default' => 'New Attribute',
                    'en_GB' => 'New, innit?',
                ],
                'filterable' => true,
                'returnable' => false,
            ]);

            $putResponse = $attributesService->put(
                accountCredentials: $accountCredentials,
                attribute: $attribute,
            );
            print_r($putResponse);
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
        break;

    case 'delete':
        try {
            $deleteResponse = $attributesService->deleteByName(
                accountCredentials: $accountCredentials,
                attributeName: 'new_attribute',
            );
            print_r($deleteResponse);
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
        break;
}
