<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Validator\Analytics\Collect\EventData\OrderPurchase;

use Klevu\PhpSDK\Exception\Validation\InvalidDataValidationException;
use Klevu\PhpSDK\Exception\Validation\InvalidTypeValidationException;
use Klevu\PhpSDK\Exception\ValidationException;
use Klevu\PhpSDK\Model\Analytics\Collect\Event;
use Klevu\PhpSDK\Model\Analytics\Collect\EventType;
use Klevu\PhpSDK\Validator\Analytics\Collect\EventData\EventDataValidatorInterface;

/**
 * Validator testing Event payloads for order_purchase events with version 1.0.0
 */
class V1Validator implements EventDataValidatorInterface
{
    /**
     * {@inheritdoc}
     *
     * Event must be of type order_purchase with a version equal to 1.0.0
     *
     * @param Event $event
     *
     * @return bool
     */
    public function appliesToEvent(Event $event): bool
    {
        return EventType::ORDER_PURCHASE === $event->event &&
            0 === version_compare($event->version, '1.0.0');
    }

    /**
     * Validates that the passed data is an array containing valid event data
     *
     * @param mixed $data
     *
     * @return void
     * @throws ValidationException
     * @throws InvalidTypeValidationException Where the passed data is not an array
     * @throws InvalidDataValidationException Where the passed data does not contain a valid collection of items
     */
    public function execute(mixed $data): void
    {
        if (!is_array($data)) {
            throw new InvalidTypeValidationException([
                sprintf(
                    'Data must be array, received "%s"',
                    get_debug_type($data),
                ),
            ]);
        }

        if (!is_array($data['items'] ?? null)) {
            throw new InvalidDataValidationException([
                'Data must contain items array',
            ]);
        }

        if (!$data['items']) {
            throw new InvalidDataValidationException([
                'Data must contain at least one item',
            ]);
        }

        $errors = [];
        foreach ($data['items'] as $key => $item) {
            $errors[] = array_map(
                static fn (string $itemError): string => sprintf('Item #%d: %s', $key, $itemError),
                $this->getErrorsForItem($item),
            );
        }
        $errors = array_filter(array_merge([], ...$errors));

        if ($errors) {
            throw new InvalidDataValidationException($errors);
        }
    }

    /**
     * @param mixed[] $item
     *
     * @return string[]
     */
    private function getErrorsForItem(array $item): array
    {
        $errors = [];

        $requiredKeys = [
            'item_name',
            'item_id',
            'item_group_id',
            'item_variant_id',
            'unit_price',
            'currency',
        ];
        $missingRequiredKeys = array_filter(
            $requiredKeys,
            static fn (string $key): bool => in_array($item[$key] ?? null, [null, ''], true),
        );
        if ($missingRequiredKeys) {
            $errors[] = sprintf(
                'The following required keys are missing or empty: %s',
                implode(', ', $missingRequiredKeys),
            );
        }

        $stringKeys = [
            'order_id',
            'order_line_id',
            'item_name',
            'item_id',
            'item_group_id',
            'item_variant_id',
            'currency',
        ];
        $invalidStringKeys = array_filter(
            $stringKeys,
            static fn (string $key): bool => array_key_exists($key, $item) && !is_string($item[$key]),
        );
        if ($invalidStringKeys) {
            $errors[] = sprintf(
                'The following keys are of an invalid type. Expected string. %s',
                implode(', ', $invalidStringKeys),
            );
        }

        if (
            array_key_exists('unit_price', $item)
            && (!is_numeric($item['unit_price']) || 0 > $item['unit_price'])
        ) {
            $errors[] = 'unit_price must be a positive numeric value';
        }

        if (!empty($item['currency']) && !preg_match('/^[A-Z]{3}$/', $item['currency'])) {
            $errors[] = 'currency must be a valid ISO 3 character currency code';
        }

        if (
            array_key_exists('units', $item)
            && (!is_int($item['units']) || 0 > $item['units'])
        ) {
            $errors[] = sprintf(
                'units must be a positive integer. Received (%s) %s',
                get_debug_type($item['units']),
                is_scalar($item['units']) ? $item['units'] : '',
            );
        }

        return $errors;
    }
}
