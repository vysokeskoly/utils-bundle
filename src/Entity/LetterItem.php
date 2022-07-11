<?php declare(strict_types=1);

namespace VysokeSkoly\UtilsBundle\Entity;

class LetterItem
{
    public function __construct(private string $letter, private array $items = [])
    {
    }

    public function addItem(mixed $item): void
    {
        $this->items[] = $item;
    }

    public function getLetter(): string
    {
        return mb_strtolower($this->letter);
    }

    public function getItems(): array
    {
        return $this->items;
    }
}
