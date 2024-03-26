<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Service;

/**
 * @internal
 */
trait CreateEndpointTrait
{
    /**
     * @param string $baseUrl
     * @param string $path
     *
     * @return string
     * @throws \LogicException
     */
    private function createEndpoint(
        string $baseUrl,
        string $path,
    ): string {
        $baseUrl = trim($baseUrl);

        $urlParts = parse_url($baseUrl);
        if (false !== $urlParts && empty($urlParts['scheme'])) {
            $urlParts = parse_url('https://' . $baseUrl);
        }

        if (empty($urlParts['host'])) {
            throw new \LogicException(sprintf(
                'Empty baseUrl host provided to %s',
                __METHOD__,
            ));
        }

        $return = ($urlParts['scheme'] ?? 'https') . '://';
        $return .= $urlParts['host'];
        if (!empty($urlParts['port'])) {
            $return .= ':' . $urlParts['port'];
        }
        if (!empty($urlParts['path'])) {
            $path = '/' . trim(
                string: $urlParts['path'],
                characters: " \n\r\t\v\x00/",
            ) . $path;
        }
        $path = '/' . ltrim(
            string: trim($path),
            characters: '/',
        );
        $return .= $path; // phpcs:ignore SlevomatCodingStandard.Variables.UselessVariable.UselessVariable

        return $return;
    }
}
