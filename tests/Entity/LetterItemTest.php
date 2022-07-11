<?php declare(strict_types=1);

namespace VysokeSkoly\UtilsBundle\Entity;

use PHPUnit\Framework\TestCase;

/**
 * @group unit
 */
class LetterItemTest extends TestCase
{
    /**
     * @dataProvider provideLetter
     */
    public function testShouldGetLetterInUpperCase(string $letter, string $expected): void
    {
        $letter = new LetterItem($letter);

        $this->assertSame($expected, $letter->getLetter());
    }

    public function provideLetter(): array
    {
        return [
            // letter, expected
            'lower' => ['a', 'a'],
            'upper' => ['A', 'a'],
        ];
    }

    public function testShouldAddItemsToLetter(): void
    {
        $letter = new LetterItem('i', ['item1']);
        $letter->addItem('item2');

        $this->assertEquals(['item1', 'item2'], $letter->getItems());
    }
}
