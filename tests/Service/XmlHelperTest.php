<?php declare(strict_types=1);

namespace VysokeSkoly\UtilsBundle\Service;

use PHPUnit\Framework\TestCase;
use VysokeSkoly\UtilsBundle\Exception\DecodingException;

/**
 * @group unit
 */
class XmlHelperTest extends TestCase
{
    private string $xmlContent = <<<XML
        <list totalCount="123">
            <item>
                <id>1</id>
                <name>One</name>
            </item>
            <item>
                <id>2</id>
                <name>Two</name>
            </item>
        </list>
        XML;

    private \SimpleXMLElement $xml;

    protected function setUp(): void
    {
        $this->xml = new \SimpleXMLElement($this->xmlContent);
    }

    /**
     * @dataProvider provideAttributes
     */
    public function testShouldGetAttributeValueFromXml(string $attributeName, ?string $expected): void
    {
        $this->assertSame($expected, XmlHelper::getXmlAttributeValue($this->xml, $attributeName));
    }

    public static function provideAttributes(): array
    {
        return [
            'existing' => ['totalCount', '123'],
            'not-existing' => ['invalid', null],
        ];
    }

    public function testShouldConvertXmlToArray(): void
    {
        $expected = [
            '@attributes' => [
                'totalCount' => 123,
            ],
            'item' => [
                [
                    'id' => 1,
                    'name' => 'One',
                ],
                [
                    'id' => 2,
                    'name' => 'Two',
                ],
            ],
        ];

        $this->assertEquals($expected, XmlHelper::convertXmlToArray($this->xml));
    }

    public function testShouldHaveSameResultForDifferentXmlStrings(): void
    {
        $xmlString = '<list totalCount="123">
                <item>
                    <id>1</id>
                    <name>One</name>
                </item>
                <item>
                    <id>2</id>
                    <name>Two</name>
                </item>
            </list>';
        $xmlArray = XmlHelper::convertXmlToArray($this->xml);
        $xmlStringArray = XmlHelper::convertXmlToArray(new \SimpleXMLElement($xmlString));

        $this->assertEquals($xmlArray, $xmlStringArray);
    }

    public function testShouldFailWhenParsingCorruptedXml(): void
    {
        $corruptedXml = <<<XML
            <?xml version="1.0" encoding="utf-8"?>
            <tests>
                <foo></bar>
            </tests>
            XML;

        $this->expectException(DecodingException::class);

        XmlHelper::stringToXml($corruptedXml);
    }

    public function testShouldParseXml(): void
    {
        $data = <<<XML
            <?xml version="1.0" encoding="utf-8"?>
            <tests>
                <foo></foo>
            </tests>
            XML;
        $xml = XmlHelper::stringToXml($data);
        $this->assertInstanceOf(\SimpleXMLElement::class, $xml);
    }

    /**
     * @dataProvider provideXmlData
     */
    public function testShouldSanitizeXml(string $inputXmlPart, string $expectedOutputPart): void
    {
        $input = '<?xml version="1.0" encoding="utf-8"?><test>' . $inputXmlPart . '</test>';
        $expectedOutput = '<?xml version="1.0" encoding="utf-8"?><test>' . $expectedOutputPart . '</test>';

        $this->assertSame($expectedOutput, XmlHelper::sanitizeXml($input));
    }

    public static function provideXmlData(): array
    {
        return [
            ['Text with completely valid characters ĎŤŇŠŘÜÚ', 'Text with completely valid characters ĎŤŇŠŘÜÚ'],
            ['Invalidcharactersare replaced with space', ' Invalid characters are replaced with space '],
        ];
    }
}
