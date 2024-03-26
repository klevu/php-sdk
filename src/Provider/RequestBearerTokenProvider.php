<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Provider;

use Klevu\PhpSDK\Exception\Validation\InvalidDataValidationException;
use Klevu\PhpSDK\Exception\ValidationException;
use Klevu\PhpSDK\Model\AccountCredentials;
use Klevu\PhpSDK\Validator\AccountCredentialsValidator;
use Klevu\PhpSDK\Validator\RequestBearerToken\RequestHeadersValidator;
use Klevu\PhpSDK\Validator\ValidatorInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Generates bearer tokens used to authenticate requests to Klevu's JSON indexing APIs
 *
 * @link https://docs.klevu.com/indexing-apis/authentication
 * @since 1.0.0
 */
class RequestBearerTokenProvider implements RequestBearerTokenProviderInterface
{
    /**
     * @var LoggerInterface|null
     */
    private readonly ?LoggerInterface $logger;
    /**
     * @var ValidatorInterface
     */
    private readonly ValidatorInterface $accountCredentialsValidator;
    /**
     * @var ValidatorInterface
     */
    private readonly ValidatorInterface $requestHeadersValidator;

    /**
     * @param LoggerInterface|null $logger
     * @param ValidatorInterface|null $accountCredentialsValidator
     *      If null, a new instance of {@see AccountCredentialsValidator} will be used
     * @param ValidatorInterface|null $requestHeadersValidator
     *      If null, a new instance of {@see RequestHeadersValidator} will be used
     */
    public function __construct(
        ?LoggerInterface $logger = null,
        ?ValidatorInterface $accountCredentialsValidator = null,
        ?ValidatorInterface $requestHeadersValidator = null,
    ) {
        $this->logger = $logger;
        $this->accountCredentialsValidator = $accountCredentialsValidator ?: new AccountCredentialsValidator();
        $this->requestHeadersValidator = $requestHeadersValidator ?: new RequestHeadersValidator();
    }

    /**
     * @param AccountCredentials $accountCredentials
     * @param RequestInterface $request
     *
     * @return string
     * @throws ValidationException When account credentials or request headers are found invalid
     * @throws InvalidDataValidationException When account credentials and request headers contain different API keys
     */
    public function getForRequest(
        AccountCredentials $accountCredentials,
        RequestInterface $request,
    ): string {
        $this->accountCredentialsValidator->execute(
            data: $accountCredentials,
        );
        $this->requestHeadersValidator->execute(
            data: $request->getHeaders(),
        );
        $this->validateAccountCredentialsMatchRequest(
            accountCredentials: $accountCredentials,
            request: $request,
        );

        $algorithm = $this->convertAuthAlgorithmHeaderValue(
            authAlgorithmHeaderValue: $request->getHeaderLine(self::API_HEADER_KEY_AUTH_ALGO),
        );
        $requestString = $this->generatePlainTextRequestString(
            request: $request,
        );
        $secretKey = $accountCredentials->restAuthKey;
        $this->logger?->debug('Generating bearer token for request', [
            'algorithm' => $algorithm,
            'requestString' => substr(
                string: $requestString,
                offset: 0,
                length: 1000,
            ),
            'secretKey' => substr(
                string: $secretKey,
                offset: 0,
                length: 3,
            ) . '*******',
        ]);

        return base64_encode(
            hash_hmac(
                algo: $algorithm,
                data: $requestString,
                key: $secretKey,
                binary: true,
            ),
        );
    }

    /**
     * @param AccountCredentials $accountCredentials
     * @param RequestInterface $request
     *
     * @return void
     * @throws InvalidDataValidationException When account credentials and request headers contain different API keys
     */
    private function validateAccountCredentialsMatchRequest(
        AccountCredentials $accountCredentials,
        RequestInterface $request,
    ): void {
        $headerApiKey = $request->getHeaderLine(self::API_HEADER_KEY_APIKEY);
        if ($accountCredentials->jsApiKey !== $headerApiKey) {
            throw new InvalidDataValidationException(
                errors: [
                    sprintf(
                        'Account Credentials API Key (%s) does not match header value (%s)',
                        $accountCredentials->jsApiKey,
                        $headerApiKey,
                    ),
                ],
            );
        }
    }

    /**
     * @param string $authAlgorithmHeaderValue
     *
     * @return string
     */
    private function convertAuthAlgorithmHeaderValue(
        string $authAlgorithmHeaderValue,
    ): string {
        $authAlgorithmPrefix = 'Hmac';
        if (str_starts_with($authAlgorithmHeaderValue, $authAlgorithmPrefix)) {
            $authAlgorithmHeaderValue = substr(
                string: $authAlgorithmHeaderValue,
                offset: strlen($authAlgorithmPrefix),
                length: null,
            );
        }

        return strtolower($authAlgorithmHeaderValue);
    }

    /**
     * @param RequestInterface $request
     *
     * @return string
     */
    private function generatePlainTextRequestString(
        RequestInterface $request,
    ): string {
        $requestUri = $request->getUri();
        $requestBody = $request->getBody();

        $requestBodyContents = $requestBody->getContents();
        $requestBody->rewind();

        return implode(PHP_EOL, [
            // <Method>
            strtoupper($request->getMethod()),
            // <URL path>
            rtrim($requestUri->getPath(), ' /'),
            // <Query String>
            $requestUri->getQuery()
                ? '?' . trim($requestUri->getQuery())
                : '',
            // <Secured Headers>
            self::API_HEADER_KEY_TIMESTAMP . '=' . $request->getHeaderLine(self::API_HEADER_KEY_TIMESTAMP),
            self::API_HEADER_KEY_APIKEY . '=' . $request->getHeaderLine(self::API_HEADER_KEY_APIKEY),
            self::API_HEADER_KEY_AUTH_ALGO . '=' . $request->getHeaderLine(self::API_HEADER_KEY_AUTH_ALGO),
            self::API_HEADER_KEY_CONTENT_TYPE . '=' . $request->getHeaderLine(self::API_HEADER_KEY_CONTENT_TYPE),
            // <Request Body>
            $requestBodyContents,
        ]);
    }
}
