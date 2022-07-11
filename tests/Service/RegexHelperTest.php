<?php declare(strict_types=1);

namespace VysokeSkoly\UtilsBundle\Service;

use Assert\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 */
class RegexHelperTest extends TestCase
{
    /** @dataProvider provideToParse */
    public function testShouldParseMatchFromString(string $string, string $pattern, ?string $expected): void
    {
        $this->assertSame($expected, RegexHelper::parse($string, $pattern));
    }

    public function provideToParse(): array
    {
        return [
            'empty' => ['', '/(w+)/', null],
            'id in url' => ['http://host/123/?get=value', '#/(\d+)/#', '123'],
            'no match' => ['word', '/(\d+)/', null],
            'url with id value' => ['/system/index.php?clanek=123&id=666&foo=bar', '/id=(\d+)/', '666'],
            'url without id value' => ['/system/index.php?clanek=123', '/id=(\d+)/', null],
        ];
    }

    public function testShouldThrowExceptionOnEmptyPattern(): void
    {
        $this->expectException(InvalidArgumentException::class);

        RegexHelper::parse('string', '');
    }
}
