<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Service\Account;

use Http\Discovery\Exception\NotFoundException;
use Http\Discovery\Psr18ClientDiscovery;
use Klevu\PhpSDK\Api\Model\AccountInterface;
use Klevu\PhpSDK\Api\Service\Account\AccountLookupServiceInterface;
use Klevu\PhpSDK\Exception\AccountNotFoundException;
use Klevu\PhpSDK\Exception\Api\BadRequestException;
use Klevu\PhpSDK\Exception\Api\BadResponseException;
use Klevu\PhpSDK\Exception\ApiExceptionInterface;
use Klevu\PhpSDK\Exception\ValidationException;
use Klevu\PhpSDK\Model\Account;
use Klevu\PhpSDK\Model\AccountCredentials;
use Klevu\PhpSDK\Model\AccountFactory;
use Klevu\PhpSDK\Provider\BaseUrlsProvider;
use Klevu\PhpSDK\Provider\BaseUrlsProviderInterface;
use Klevu\PhpSDK\Provider\UserAgentProvider;
use Klevu\PhpSDK\Provider\UserAgentProviderInterface;
use Klevu\PhpSDK\Service\CreateEndpointTrait;
use Klevu\PhpSDK\Traits\MaskSensitiveDataTrait;
use Klevu\PhpSDK\Traits\Psr17FactoryTrait;
use Klevu\PhpSDK\Validator\AccountCredentialsValidator;
use Klevu\PhpSDK\Validator\JsApiKeyValidator;
use Klevu\PhpSDK\Validator\RestAuthKeyValidator;
use Klevu\PhpSDK\Validator\ValidatorInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Client\RequestExceptionInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Service class handling look-ups of Klevu account information via API
 *
 * @since 1.0.0
 */
class AccountLookupService implements AccountLookupServiceInterface
{
    use CreateEndpointTrait;
    use MaskSensitiveDataTrait;
    use Psr17FactoryTrait;

    /**
     * Header key used to provide the JavaScript API Key (public key) in requests to account lookup API
     *
     * @var string
     */
    public const API_HEADER_KEY_JSAPIKEY = 'X-KLEVU-JSAPIKEY';
    /**
     * Header key used to provide the REST AUTH Key (secret key) in requests to account lookup API
     *
     * @var string
     */
    public const API_HEADER_KEY_RESTAPIKEY = 'X-KLEVU-RESTAPIKEY';

    /**
     * @var BaseUrlsProviderInterface
     */
    private readonly BaseUrlsProviderInterface $baseUrlsProvider;
    /**
     * @var ClientInterface
     */
    private readonly ClientInterface $httpClient;
    /**
     * @var LoggerInterface|null
     */
    private readonly ?LoggerInterface $logger;
    /**
     * @var ValidatorInterface
     */
    private readonly ValidatorInterface $accountCredentialsValidator;
    /**
     * @var AccountFactory
     */
    private readonly AccountFactory $accountFactory;
    /**
     * @var UserAgentProviderInterface
     */
    private readonly UserAgentProviderInterface $userAgentProvider;

    /**
     * @uses Psr18ClientDiscovery::find()
     *
     * @param BaseUrlsProviderInterface|null $baseUrlsProvider
     *      If null, a new instance of {@see BaseUrlsProvider} is used
     * @param ClientInterface|null $httpClient
     *      If null, discovery of compatible clients will be attempted
     * @param LoggerInterface|null $logger
     * @param ValidatorInterface|null $accountCredentialsValidator
     *      If null, a new instance of {@see AccountCredentialsValidator} is used
     * @param RequestFactoryInterface|null $requestFactory
     * @param ResponseFactoryInterface|null $responseFactory
     * @param AccountFactory|null $accountFactory
     *      If null, a new instance of {@see AccountFactory} will be used
     * @param UserAgentProviderInterface|null $userAgentProvider
     *      If null, a new instance of {@see UserAgentProvider} is used
     *
     * @throws NotFoundException Where httpClient is not provided and no PSR-18 compatible ClientInterface
     *       can be automagically discovered
     */
    public function __construct(
        ?BaseUrlsProviderInterface $baseUrlsProvider = null,
        ?ClientInterface $httpClient = null,
        ?LoggerInterface $logger = null,
        ?ValidatorInterface $accountCredentialsValidator = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?ResponseFactoryInterface $responseFactory = null,
        ?AccountFactory $accountFactory = null,
        ?UserAgentProviderInterface $userAgentProvider = null,
    ) {
        $this->baseUrlsProvider = $baseUrlsProvider ?: new BaseUrlsProvider();
        $this->httpClient = $httpClient ?: Psr18ClientDiscovery::find();
        $this->logger = $logger;
        $this->accountCredentialsValidator = $accountCredentialsValidator ?: new AccountCredentialsValidator(
            new JsApiKeyValidator(),
            new RestAuthKeyValidator(),
        );
        $this->requestFactory = $requestFactory;
        $this->responseFactory = $responseFactory;
        $this->accountFactory = $accountFactory ?: new AccountFactory();
        $this->userAgentProvider = $userAgentProvider ?: new UserAgentProvider();
    }

    /**
     * Returns the full endpoint for requests made by this service class
     *
     * @example https://api.ksearchnet.com/user-account/public/platform/account/details
     *
     * @return string
     * @throws \LogicException On internal errors encountered by the application, such as incorrectly
     *      configured base URLs information
     * @uses BaseUrlsProviderInterface::getApiUrl()
     */
    public function getEndpoint(): string
    {
        return $this->createEndpoint(
            baseUrl: $this->baseUrlsProvider->getApiUrl(),
            path: '/user-account/public/platform/account/details',
        );
    }

    /**
     * Returns the object responsible for handling User-Agent provision for this service
     *
     * Method provided to allow entry point for injecting and modifying user agent strings
     *
     * @return UserAgentProviderInterface|null
     */
    public function getUserAgentProvider(): ?UserAgentProviderInterface
    {
        return $this->userAgentProvider;
    }

    /**
     * @param AccountCredentials $accountCredentials
     *
     * @return AccountInterface
     * @throws ValidationException Where provided credentials fail internal validation. API request is NOT sent
     * @throws ApiExceptionInterface
     * @throws BadRequestException Where the Klevu service rejects the request as invalid (4xx response code)
     * @throws BadResponseException Where the Klevu service does not return a valid response (timeouts, 5xx response)
     * @throws AccountNotFoundException Where no account is found for the provided credentials
     */
    public function execute(AccountCredentials $accountCredentials): AccountInterface
    {
        $this->accountCredentialsValidator->execute($accountCredentials);

        $request = $this->buildRequest($accountCredentials);

        $requestBody = $request->getBody();
        $requestBodyContents = $requestBody->getContents();
        $requestBody->rewind();

        $this->logger?->debug('Request for Klevu account lookup', [
            'js_api_key' => $accountCredentials->jsApiKey,
            'headers' => $this->maskHttpHeaders($request->getHeaders()),
            'body' => $requestBodyContents,
        ]);

        try {
            $startTime = microtime(true);
            $response = $this->httpClient->sendRequest($request);
            $endTime = microtime(true);

            $responseBody = $response->getBody();
            $responseBodyContents = $responseBody->getContents();
            $responseBody->rewind();

            $this->logger?->debug('Response from Klevu account lookup', [
                'js_api_key' => $accountCredentials->jsApiKey,
                'status_code' => $response->getStatusCode(),
                'response_time' => $endTime - $startTime,
                'headers' => $this->maskHttpHeaders($response->getHeaders()),
                'body' => $responseBodyContents,
            ]);
        } catch (RequestExceptionInterface $exception) {
            throw new BadRequestException(
                message: $exception->getMessage(),
                code: $exception->getCode(),
                previous: $exception,
            );
        } catch (ClientExceptionInterface | NetworkExceptionInterface $exception) {
            throw new BadResponseException(
                message: $exception->getMessage(),
                code: $exception->getCode(),
                previous: $exception,
            );
        }

        $parsedResponseBody = $this->parseResponseBody($responseBodyContents);
        $this->checkResponse(
            responseCode: $response->getStatusCode(),
            parsedResponseBody: $parsedResponseBody,
            accountCredentials: $accountCredentials,
        );

        return $this->accountFactory->create([
            Account::FIELD_JS_API_KEY => $accountCredentials->jsApiKey,
            Account::FIELD_REST_AUTH_KEY => $accountCredentials->restAuthKey,
            Account::FIELD_PLATFORM => $parsedResponseBody['platform'] ?? null,
            Account::FIELD_ACTIVE => (bool)($parsedResponseBody['active'] ?? false),
            Account::FIELD_COMPANY_NAME => $parsedResponseBody['companyName'] ?? null,
            Account::FIELD_EMAIL => $parsedResponseBody['email'] ?? null,
            Account::FIELD_INDEXING_URL => $parsedResponseBody['indexingUrl']
                ?? $this->baseUrlsProvider->getIndexingUrl(),
            Account::FIELD_SEARCH_URL => $parsedResponseBody['searchUrl']
                ?? $this->baseUrlsProvider->getSearchUrl(),
            Account::FIELD_SMART_CATEGORY_MERCHANDISING_URL => $parsedResponseBody['catNavUrl']
                ?? $this->baseUrlsProvider->getSmartCategoryMerchandisingUrl(),
            Account::FIELD_ANALYTICS_URL => $parsedResponseBody['analyticsUrl']
                ?? $this->baseUrlsProvider->getAnalyticsUrl(),
            Account::FIELD_JS_URL => $parsedResponseBody['jsUrl']
                ?? $this->baseUrlsProvider->getJsUrl(),
            Account::FIELD_TIERS_URL => $parsedResponseBody['tiersUrl']
                ?? $this->baseUrlsProvider->getTiersUrl(),
            Account::FIELD_INDEXING_VERSION => $parsedResponseBody['indexingVersion'] ?? '',
            Account::FIELD_DEFAULT_CURRENCY => $parsedResponseBody['defaultCurrency'] ?? '',
        ]);
    }

    /**
     * @param AccountCredentials $accountCredentials
     *
     * @return RequestInterface
     */
    private function buildRequest(AccountCredentials $accountCredentials): RequestInterface
    {
        $psr17Factory = $this->getPsr17Factory();
        $request = $psr17Factory->createRequest('GET', $this->getEndpoint());
        $request = $request->withHeader('Content-Type', 'application/json');
        $request = $request->withHeader('User-Agent', $this->userAgentProvider->execute());
        $request = $request->withHeader(static::API_HEADER_KEY_JSAPIKEY, $accountCredentials->jsApiKey);
        /** @noinspection PhpUnnecessaryLocalVariableInspection */
        $request = $request->withHeader(static::API_HEADER_KEY_RESTAPIKEY, $accountCredentials->restAuthKey);

        return $request;
    }

    /**
     * @param string|null $responseBody
     *
     * @return mixed[]
     */
    private function parseResponseBody(?string $responseBody): array
    {
        if (!$responseBody) {
            throw new BadResponseException(
                message: 'No response body',
                code: 0,
            );
        }

        $parsedResponseBody = json_decode($responseBody, true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new BadResponseException(
                message: 'Could not decode response body as valid JSON',
                code: json_last_error(),
                errors: [
                    json_last_error_msg(),
                ],
            );
        }

        if (!is_array($parsedResponseBody)) {
            throw new BadResponseException(
                message: 'Unexpected response body data',
                code: 0,
            );
        }

        return $parsedResponseBody;
    }

    /**
     * @param int $responseCode
     * @param mixed[] $parsedResponseBody
     * @param AccountCredentials $accountCredentials
     *
     * @return void
     * @throws BadRequestException
     * @throws BadResponseException
     * @throws AccountNotFoundException
     */
    private function checkResponse(
        int $responseCode,
        array $parsedResponseBody,
        AccountCredentials $accountCredentials,
    ): void {
        $errors = [];
        if (!empty($parsedResponseBody['error'])) {
            $errors[] = sprintf(
                '[%s] %s',
                is_scalar($parsedResponseBody['error'])
                    ? (string)$parsedResponseBody['error']
                    : 'n/a',
                $parsedResponseBody['message'] ?? '',
            );
        }

        switch (true) {
            case 400 === $responseCode: // Bad Request - missing or invalid parameters
            case 403 === $responseCode:
            case 405 === $responseCode:
            case 200 === $responseCode && $errors:
                throw new BadRequestException(
                    message: 'Illegal API payload rejected by Klevu API.',
                    code: $responseCode,
                    errors: $errors,
                );

            case 401 === $responseCode: // Invalid credentials - no matching account found
                throw new AccountNotFoundException(
                    jsApiKey: $accountCredentials->jsApiKey,
                );

            case 200 !== $responseCode: // Route not found, timeouts, server errors
                throw new BadResponseException(
                    message: sprintf(
                        'Unexpected Response Code %s.',
                        $responseCode,
                    ),
                    code: $responseCode,
                    errors: $errors,
                );
        }
    }
}
