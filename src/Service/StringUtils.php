<?php declare(strict_types=1);

namespace VysokeSkoly\UtilsBundle\Service;

use function Safe\preg_match;
use function Safe\usort;

class StringUtils
{
    protected static array $diacriticMap = [
        'á' => 'a',
        'Á' => 'A',
        'č' => 'c',
        'Č' => 'C',
        'ď' => 'd',
        'Ď' => 'D',
        'é' => 'e',
        'É' => 'E',
        'ě' => 'e',
        'Ě' => 'E',
        'í' => 'i',
        'Í' => 'I',
        'ň' => 'n',
        'Ň' => 'N',
        'ó' => 'o',
        'Ó' => 'O',
        'ř' => 'r',
        'Ř' => 'R',
        'š' => 's',
        'Š' => 'S',
        'ť' => 't',
        'Ť' => 'T',
        'ú' => 'u',
        'Ú' => 'U',
        'ů' => 'u',
        'Ů' => 'U',
        'ý' => 'y',
        'Ý' => 'Y',
        'ž' => 'z',
        'Ž' => 'Z',
    ];

    /**
     * Gets the first line of the content taking whole words into account
     */
    public static function truncateWords(string $content, int $maxLength = 200): string
    {
        $content = strip_tags($content);

        // Remove multiple consecutive spaces and line breaks with a single space
        $content = trim(RegexHelper::pregReplace('/\s+/', ' ', $content));
        if (mb_strlen($content) <= $maxLength) {
            return $content;
        }

        // Cut after whole words only
        return RegexHelper::pregReplace('/\s+?(\S+)?$/', '', mb_substr($content, 0, $maxLength, 'utf-8'));
    }

    public static function removeDiacritic(string $str): string
    {
        return strtr($str, self::$diacriticMap);
    }

    public static function formatPhoneNumber(string $number): string
    {
        $number = RegexHelper::pregReplace('/\s+/', '', $number);

        $result = '';
        if (self::startsWith($number, '+420')) {
            $result .= '+420 ';
            $number = mb_substr($number, 4);
        }
        $result .= chunk_split($number, 3, ' ');

        return trim($result);
    }

    /**
     * Converts an XPath result to string
     * Assumes only one result item is returned, otherwise it will return an empty string as on failure
     *
     * @param \SimpleXMLElement[] $result Result of XPath SimpleXML call
     */
    public static function xpathResultToString(array $result): string
    {
        if (!$result || count($result) !== 1) {
            return '';
        }

        return (string) current($result);
    }

    public static function getEmailPattern(): string
    {
        return "^[-!#$%&'*+\/0-9=?A-Z^_a-z`{|}~](\.?[-!#$%&'*+\/0-9=?A-Z^_a-z`{|}~])*@"
            . "(([a-zA-Z0-9]\.)|([a-zA-Z0-9]([-a-zA-Z0-9]{0,61}[a-zA-Z0-9])\.))+"
            . '[a-zA-Z0-9]([-a-zA-Z0-9]{0,61}[a-zA-Z0-9])$';
    }

    public static function validateEmail(string $email): bool
    {
        return (bool) preg_match('/' . self::getEmailPattern() . '/', $email);
    }

    /**
     * @param string $index If not passed array keys are kept
     */
    public static function sortArrayAlphabetically(array $toSort, string $index = '', string $locale = 'cs_CZ'): array
    {
        $collator = new \Collator($locale);

        if ($index === '') {
            $collator->sort($toSort);
        } else {
            usort($toSort, function ($a, $b) use ($collator, $index) {
                return $collator->compare($a[$index], $b[$index]);
            });
        }

        return $toSort;
    }

    public static function removeNonNumerical(string $str): string
    {
        return RegexHelper::pregReplace('/[^0-9]/', '', $str);
    }

    public static function startsWith(string $haystack, string $needle): bool
    {
        $needleLength = mb_strlen($needle);

        if ($needleLength == 0) {
            return true;
        }

        return mb_substr($haystack, 0, $needleLength) == $needle;
    }

    public static function endsWith(string $haystack, string $needle): bool
    {
        $needleLength = mb_strlen($needle);

        if ($needleLength == 0) {
            return true;
        }

        return mb_substr($haystack, -$needleLength) == $needle;
    }

    public static function contains(string $heystack, string $needle): bool
    {
        return mb_strpos($heystack, $needle) !== false;
    }

    public static function getFirstLetter(string $string): string
    {
        return mb_substr($string, 0, 1, 'UTF8');
    }
}
