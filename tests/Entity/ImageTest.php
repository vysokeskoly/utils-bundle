<?php declare(strict_types=1);

namespace VysokeSkoly\UtilsBundle\Entity;

use PHPUnit\Framework\TestCase;
use VysokeSkoly\UtilsBundle\Entity\Html\Image;

/**
 * @group unit
 */
class ImageTest extends TestCase
{
    /** @dataProvider provideExtension */
    public function testShouldGetMimeType(string $extension, string $expected): void
    {
        $result = Image::getMimeType($extension);

        $this->assertSame($expected, $result);
    }

    public function provideExtension(): array
    {
        return [
            // extension, expected mime type
            'jpg' => ['jpg', 'image/jpeg'],
            'jpeg' => ['jpeg', 'image/jpeg'],
            'png' => ['png', 'image/png'],
            'jpg with size' => ['jpg 992w', 'image/jpeg'],
            'unknown' => ['unknown', 'image/*'],
            'empty' => ['', 'image/*'],
        ];
    }
}
