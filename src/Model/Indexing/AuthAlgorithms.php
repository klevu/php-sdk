<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Model\Indexing;

/**
 * Enumeration of hashing algorithms supported in generation of authorization headers for indexing API services
 *
 * @since 1.0.0
 */
enum AuthAlgorithms: string
{
    case HMAC_SHA384 = 'HmacSHA384';
}
