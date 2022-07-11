<?php declare(strict_types=1);

namespace VysokeSkoly\UtilsBundle\Entity;

class LetterItem
{
    private string $letter;
    private array $items;

    public function __construct(string $letter, array $items = [])
    {
        $this->letter = $letter;
        $this->items = $items;
    }

    /**
     * @param mixed $item
     */
    public function addItem($item): void
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
