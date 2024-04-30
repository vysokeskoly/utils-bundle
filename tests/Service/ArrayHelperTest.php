<?php declare(strict_types=1);

namespace VysokeSkoly\UtilsBundle\Service;

use Assert\InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use VysokeSkoly\UtilsBundle\Entity\LetterItem;

/**
 * @group unit
 */
class ArrayHelperTest extends TestCase
{
    /**
     * @dataProvider provideAplhabeticalList
     */
    public function testShouldCreateAlphabeticalList(array $items, string $column, array $expected): void
    {
        $this->assertEquals($expected, ArrayHelper::getAlphabeticalList($items, $column));
    }

    public static function provideAplhabeticalList(): array
    {
        return [
            // items, column, expected
            'empty' => [[], 'whatever', []],
            '2 letters' => [
                [
                    ['name' => 'one'],
                    ['name' => 'two'],
                    ['name' => 'three'],
                ],
                'name',
                [
                    'o' => new LetterItem('o', [
                        ['name' => 'one'],
                    ]),
                    't' => new LetterItem('t', [
                        ['name' => 'two'],
                        ['name' => 'three'],
                    ]),
                ],
            ],
            'same letters with different size' => [
                [
                    ['name' => 'one'],
                    ['name' => 'two'],
                    ['name' => 'Three'],
                ],
                'name',
                [
                    'o' => new LetterItem('o', [
                        ['name' => 'one'],
                    ]),
                    't' => new LetterItem('t', [
                        ['name' => 'two'],
                        ['name' => 'Three'],
                    ]),
                ],
            ],
            'czech letters with different size' => [
                [
                    ['name' => 'Únor'],
                    ['name' => 'Řepiště'],
                    ['name' => 'Item'],
                    ['name' => 'Uroboros'],
                ],
                'name',
                [
                    'i' => new LetterItem('i', [
                        ['name' => 'Item'],
                    ]),
                    'ř' => new LetterItem('ř', [
                        ['name' => 'Řepiště'],
                    ]),
                    'u' => new LetterItem('u', [
                        ['name' => 'Uroboros'],
                    ]),
                    'ú' => new LetterItem('ú', [
                        ['name' => 'Únor'],
                    ]),
                ],
            ],
        ];
    }

    public function testShouldGetLastBeforeOneItemFromArray(): void
    {
        $this->assertEquals(4, ArrayHelper::getLastButOne([1, 2, 3, 4, 5]));
    }

    public function testShouldCatchExceptionWhileGettingLastBeforeOneItemFromSmallArray(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ArrayHelper::getLastButOne([1]);
    }
}
