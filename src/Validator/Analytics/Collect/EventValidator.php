<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Validator\Analytics\Collect;

use Klevu\PhpSDK\Exception\Validation\InvalidDataValidationException;
use Klevu\PhpSDK\Exception\Validation\InvalidTypeValidationException;
use Klevu\PhpSDK\Exception\ValidationException;
use Klevu\PhpSDK\Model\Analytics\Collect\Event;
use Klevu\PhpSDK\Validator\Analytics\Collect\EventData\EventDataValidatorInterface;
use Klevu\PhpSDK\Validator\Analytics\Collect\EventData\OrderPurchase\V1Validator as OrderPurchaseValidator;
use Klevu\PhpSDK\Validator\JsApiKeyValidator;
use Klevu\PhpSDK\Validator\ValidatorInterface;

/**
 * Composite validator testing type and format of individual /analytics/collect Event
 *
 * @since 1.0.0
 */
class EventValidator implements ValidatorInterface
{
    /**
     * @var ValidatorInterface
     */
    private ValidatorInterface $jsApiKeyValidator;
    /**
     * @var EventDataValidatorInterface[]
     */
    private array $eventDataValidators = [];

    /**
     * @param ValidatorInterface|null $jsApiKeyValidator
     *      If null, a new instance of {@see JsApiKeyValidator} is used
     * @param EventDataValidatorInterface[]|null $eventDataValidators
     *      If null, the following validators are added: {@see OrderPurchaseValidator}
     */
    public function __construct(
        ?ValidatorInterface $jsApiKeyValidator = null,
        ?array $eventDataValidators = null,
    ) {
        $this->jsApiKeyValidator = $jsApiKeyValidator ?: new JsApiKeyValidator();
        if (null === $eventDataValidators) {
            $eventDataValidators = [
                new OrderPurchaseValidator(),
            ];
        }
        array_walk($eventDataValidators, [$this, 'addEventDataValidator']);
    }

    /**
     * Validates that the passed data is an Event object containing valid data
     *
     * {@see Event::$data} is validated using zero or more {@see EventDataValidatorInterface}s registered
     *  during object initialisation
     *
     * @uses JsApiKeyValidator
     * @uses EventDataValidatorInterface
     *
     * @param mixed $data
     *
     * @return void
     * @throws InvalidTypeValidationException Where the passed data is not an instance of {@see Event}
     * @throws InvalidDataValidationException Where the passed Event contains missing or invalid data
     */
    public function execute(mixed $data): void
    {
        if (!($data instanceof Event)) {
            throw new InvalidTypeValidationException([
                sprintf(
                    'Event must be of type "%s", received "%s"',
                    Event::class,
                    get_debug_type($data),
                ),
            ]);
        }

        $errors = [
            'apikey' => $this->getErrorsForEventApikey($data),
            'version' => $this->getErrorsForEventVersion($data),
        ];
        if (!$errors['version']) {
            $errors['data'] = $this->getErrorsForEventData($data);
        }

        $errors = array_filter(
            array_merge([], ...array_values($errors)),
        );

        if ($errors) {
            throw new InvalidDataValidationException($errors);
        }
    }

    /**
     * @param EventDataValidatorInterface $itemsValidator
     *
     * @return void
     */
    private function addEventDataValidator(EventDataValidatorInterface $itemsValidator): void
    {
        $this->eventDataValidators[] = $itemsValidator;
    }

    /**
     * @param Event $event
     *
     * @return string[]
     */
    private function getErrorsForEventApikey(Event $event): array
    {
        $errors = [];
        try {
            $this->jsApiKeyValidator->execute($event->apikey);
        } catch (ValidationException $exception) {
            $errors = $exception->getErrors();
        }

        return $errors;
    }

    /**
     * @param Event $event
     *
     * @return string[]
     */
    private function getErrorsForEventVersion(Event $event): array
    {
        $errors = [];
        switch (true) {
            case !trim($event->version):
                $errors[] = 'Event version must not be empty';
                break;

            case version_compare($event->version, '1.0.0', 'lt'):
                $errors[] = 'Event version must be a valid, stable semantic version';
                break;
        }

        return $errors;
    }

    /**
     * @param Event $event
     *
     * @return string[]
     */
    private function getErrorsForEventData(Event $event): array
    {
        $applicableValidators = array_filter(
            $this->eventDataValidators,
            static fn (EventDataValidatorInterface $validator): bool => $validator->appliesToEvent($event),
        );
        if (!$applicableValidators) {
            return [
                sprintf('Unsupported event version "%s"', $event->version),
            ];
        }

        $errors = [];
        foreach ($applicableValidators as $eventDataValidator) {
            try {
                $eventDataValidator->execute($event->data);
            } catch (ValidationException $exception) {
                $errors[] = $exception->getErrors();
            }
        }

        return array_filter(array_merge([], ...$errors));
    }
}
