<?php declare(strict_types=1);

namespace VysokeSkoly\UtilsBundle\Service;

use PHPUnit\Framework\TestCase;

/**
 * @group unit
 */
class StringUtilsTest extends TestCase
{
    /** @dataProvider provideWordsForTruncate */
    public function testShouldTruncateWords(string $expected, string $source, int $length): void
    {
        $this->assertEquals($expected, StringUtils::truncateWords($source, $length));
    }

    public static function provideWordsForTruncate(): array
    {
        return [
            ['test', 'test', 100],
            ['te', 'test', 2],
            ['test', 'test test', 4],
            ['test', 'test test', 6],
            ['test test', 'test test', 9],
            ['ěščřžýáíé', 'ěščřžýáíé', 9],
            ['ěščřžýáíé', 'ěščřžýáíé ěšč ěěšč', 12],
            ['ěščřžýáíé ěšč', 'ěščřžýáíé ěšč ěěšč', 14],
            ['aaa bbb ccc', "aaa\nbbb\nccc", 11],
        ];
    }

    public function testShouldRemoveDiacritic(): void
    {
        $this->assertEquals('acdeeinorstuuyz', StringUtils::removeDiacritic('áčďéěíňóřšťúůýž'));
    }

    /**
     * @dataProvider provideEmail
     */
    public function testShouldValidateEmails(string $email, bool $isValid): void
    {
        if ($isValid) {
            $this->assertTrue(StringUtils::validateEmail($email), 'E-mail ' . $email . ' should be valid.');
        } else {
            $this->assertFalse(StringUtils::validateEmail($email), 'E-mail ' . $email . ' should be invalid.');
        }
    }

    public function provideEmail(): array
    {
        return [
            ['email@domain.com', true],
            ['firstname.lastname@domain.com', true],
            ['email@subdomain.domain.com', true],
            ['firstname+lastname@domain.com', true],
            ['email@domain-one.com', true],
            ['1234567890@domain.com', true],
            ['email@domain.name', true],
            ['email@domain.museum', true],
            ['email@domain.co.jp', true],
            ['firstname-lastname@domain.com', true],
            ['jobsui@testing.eu', true],
            ['english@testing.eu', true],
            ['long@tld.museum', true],
            ['skoda@xn--koda-f6a.eu', true],
            ['foo@nic.dev', true],
            ['var@docs.google', true],

            ['plainaddress', false],
            ['#@%^%#$@#$@#.com', false],
            ['@domain.com', false],
            ['Joe Smith <email@domain.com>', false],
            ['email.domain.com', false],
            ['email@domain@domain.com', false],
            ['.email@domain.com', false],
            ['email.@domain.com', false],
            ['email..email@domain.com', false],
            ['email@-domain.com', false],
            ['email@domain..com', false],
        ];
    }

    /**
     * @dataProvider providePhoneNumbers
     */
    public function testShouldFormatPhoneNumber(string $expected, string $given): void
    {
        $this->assertEquals($expected, StringUtils::formatPhoneNumber($given));
    }

    public function providePhoneNumbers(): array
    {
        return [
            ['+420 123 456 789', '+420123456789'],
            ['+420 123 456 789', ' +420123456789'],
            ['123 456 789', '123456789'],
            ['123 456 789', ' 123456789'],
            ['foo', 'foo'],
            ['123', '123'],
        ];
    }

    public function testShouldSortArrayAlphabetically(): void
    {
        $unorderedArray = ['Martina', 'Jiří', 'xylofon', 'řeřicha', 'akát', 'Adam', 'Červenáček', 'cervenacek'];
        $orderedArray = ['Adam', 'akát', 'cervenacek', 'Červenáček', 'Jiří', 'Martina', 'řeřicha', 'xylofon'];

        $newOrderedArray = StringUtils::sortArrayAlphabetically($unorderedArray);

        $this->assertSame(array_values($orderedArray), array_values($newOrderedArray));
    }

    public function testShouldSortAssocArrayAlphabetically(): void
    {
        $unorderedArray = [
            ['name' => 'Martina'],
            ['name' => 'Jiří'],
            ['name' => 'xylofon'],
            ['name' => 'řeřicha'],
            ['name' => 'akát'],
            ['name' => 'Adam'],
            ['name' => 'Červenáček'],
            ['name' => 'cervenacek'],
        ];
        $orderedArray = [
            ['name' => 'Adam'],
            ['name' => 'akát'],
            ['name' => 'cervenacek'],
            ['name' => 'Červenáček'],
            ['name' => 'Jiří'],
            ['name' => 'Martina'],
            ['name' => 'řeřicha'],
            ['name' => 'xylofon'],
        ];

        $this->assertEquals($orderedArray, StringUtils::sortArrayAlphabetically($unorderedArray, 'name'));
    }

    public function testShouldRemoveNonNumericalCharacters(): void
    {
        $this->assertEquals('123', StringUtils::removeNonNumerical('@ABC-123 def#'));
    }

    /**
     * @dataProvider provideForStartsWith
     */
    public function testShouldDetectIfStringStartsWith(string $haystack, string $needle, bool $expectedResult): void
    {
        $this->assertSame($expectedResult, StringUtils::startsWith($haystack, $needle));
    }

    public function provideForStartsWith(): array
    {
        return [
            // $haystack, $needle, $expectedResult
            ['somestring', 'some', true],
            ['ďťň unicode string', 'ďť', true],
            ['  spaces', '  ', true],
            ['SomeCaseSensitiveString', 'some', false],
            ['othersomestring', 'some', false],
            ['foobar', 'foobars', false],
            ['123456', '123', true],
            ['', 'foo', false],
            ['foo', '', true],
            ['', '', true],
        ];
    }

    /**
     * @dataProvider provideForEndsWith
     */
    public function testShouldDetectIfStringEndsWith(string $haystack, string $needle, bool $expectedResult): void
    {
        $this->assertSame($expectedResult, StringUtils::endsWith($haystack, $needle));
    }

    public function provideForEndsWith(): array
    {
        return [
            // $haystack, $needle, $expectedResult
            ['foobar', 'bar', true],
            ['unicode string čžř', 'žř', true],
            ['spaces  ', '  ', true],
            ['SomeCaseSensitiveString', 'string', false],
            ['barfoo', 'bar', false],
            ['foobar', 'foobars', false],
            ['123456', '456', true],
            ['', 'foo', false],
            ['foo', '', true],
            ['', '', true],
        ];
    }

    /**
     * @dataProvider provideFirstLetter
     */
    public function testShouldGetFirstLetter(string $string, string $expected): void
    {
        $this->assertEquals($expected, StringUtils::getFirstLetter($string));
    }

    public function provideFirstLetter(): array
    {
        return [
            'empty' => ['', ''],
            'Česká' => ['Česká', 'Č'],
            'česká' => ['česká', 'č'],
            'Řapík' => ['Řapík', 'Ř'],
            'abeceda' => ['abeceda', 'a'],
            'Xylofón' => ['Xylofón', 'X'],
            'S' => ['S', 'S'],
        ];
    }

    /**
     * @dataProvider provideForContains
     */
    public function testShouldContainsAString(string $heystack, string $needle, bool $expected): void
    {
        $this->assertSame($expected, StringUtils::contains($heystack, $needle));
    }

    public function provideForContains(): array
    {
        return [
            // heystack, needle, expected
            'something in empty' => ['', 'foo', false],
            'substring in string' => ['fooBar', 'foo', true],
            'string in string' => ['string', 'foo', false],
            'multi-substring in string' => ['foo, fooBar, fooo', 'foo', true],
        ];
    }
}
