<?php declare(strict_types=1);

namespace VysokeSkoly\UtilsBundle\Exception;

class FindHtmlTagException extends \RuntimeException
{
    public function __construct(
        string $message,
        \Throwable $previous,
        private array $keys,
        private array $values,
        private bool $tryingWithMatchingAttributes,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getKeys(): array
    {
        return $this->keys;
    }

    public function getValues(): array
    {
        return $this->values;
    }

    public function isTryingWithMatchingAttributes(): bool
    {
        return $this->tryingWithMatchingAttributes;
    }
}
