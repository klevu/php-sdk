<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Example\Http;

use Http\Discovery\Psr18ClientDiscovery;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class Client implements ClientInterface
{
    /**
     * @var ClientInterface
     */
    private readonly ClientInterface $client;
    /**
     * @var bool
     */
    private readonly bool $outputRequestAsText;
    /**
     * @var bool
     */
    private readonly bool $outputRequestAsCurl;

    /**
     * @param bool $outputRequestAsText
     * @param bool $outputRequestAsCurl
     */
    public function __construct(
        bool $outputRequestAsText = false,
        bool $outputRequestAsCurl = false,
    ) {
        $this->client = Psr18ClientDiscovery::find();
        $this->outputRequestAsText = $outputRequestAsText;
        $this->outputRequestAsCurl = $outputRequestAsCurl;
    }

    /**
     * @param RequestInterface $request
     * @return ResponseInterface
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        if ($this->outputRequestAsText) {
            echo PHP_EOL . '>>> REQUEST >>' . PHP_EOL;
            $this->outputRequestAsText($request);
            echo PHP_EOL . '<<<' . PHP_EOL;
        }

        if ($this->outputRequestAsCurl) {
            echo PHP_EOL . '>>> CURL >>' . PHP_EOL;
            $this->outputRequestAsCurl($request);
            echo PHP_EOL . '<<<' . PHP_EOL;
        }

        return $this->client->sendRequest($request);
    }

    /**
     * @param RequestInterface $request
     * @return void
     */
    private function outputRequestAsText(RequestInterface $request): void
    {
        echo $request->getMethod() . ' ' . $request->getUri() . PHP_EOL;
        foreach ($request->getHeaders() as $headerKey => $headerValues) {
            foreach ($headerValues as $headerValue) {
                echo $headerKey . ': ' . $headerValue . PHP_EOL;
            }
        }
        $requestBody = $request->getBody();
        $requestBodyContents = $requestBody->getContents();
        $requestBody->rewind();
        print_r($requestBodyContents);
    }

    /**
     * @param RequestInterface $request
     * @return void
     */
    private function outputRequestAsCurl(RequestInterface $request): void
    {
        $curlLines = [];
        $curlLines[] = sprintf(
            "curl --location --request %s '%s'",
            $request->getMethod(),
            $request->getUri(),
        );
        foreach ($request->getHeaders() as $headerKey => $headerValues) {
            foreach ($headerValues as $headerValue) {
                $curlLines[] = sprintf("--header '%s: %s'", $headerKey, $headerValue);
            }
        }
        if ('GET' !== $request->getMethod()) {
            $requestBody = $request->getBody();
            $requestBodyContents = $requestBody->getContents();
            $requestBody->rewind();

            $curlLines[] = sprintf("--data-raw '%s'", $requestBodyContents);
        }

        echo implode(' \\' . PHP_EOL, $curlLines);
    }
}
