<?php declare(strict_types=1);

namespace VysokeSkoly\UtilsBundle\Service;

use Assert\Assertion;
use VysokeSkoly\UtilsBundle\Entity\LetterItem;

class ArrayHelper
{
    public const INDEX_LAST_BEFORE_ONE = 2;

    /**
     * @return LetterItem[]
     */
    public static function getAlphabeticalList(array $items, string $column): array
    {
        /** @var LetterItem[] $letterItems */
        $letterItems = [];

        foreach ($items as $item) {
            $letter = mb_strtolower(StringUtils::getFirstLetter($item[$column]));

            if (!array_key_exists($letter, $letterItems)) {
                $letterItems[$letter] = new LetterItem($letter);
            }

            $letterItems[$letter]->addItem($item);
        }

        $letters = StringUtils::sortArrayAlphabetically(array_keys($letterItems));

        $alphabetically = [];
        foreach ($letters as $letter) {
            $alphabetically[$letter] = $letterItems[$letter];
        }

        return $alphabetically;
    }

    public static function getLastButOne(array $array): mixed
    {
        $count = count($array);

        Assertion::greaterOrEqualThan($count, self::INDEX_LAST_BEFORE_ONE);

        return $array[$count - self::INDEX_LAST_BEFORE_ONE];
    }
}
