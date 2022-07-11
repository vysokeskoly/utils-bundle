<?php declare(strict_types=1);

namespace VysokeSkoly\UtilsBundle\Service;

use Assert\Assertion;
use function Safe\preg_match;
use function Safe\preg_replace;

class RegexHelper
{
    public static function parse(string $string, string $pattern): ?string
    {
        Assertion::notEmpty($pattern);

        if (empty($string)) {
            return null;
        }

        preg_match($pattern, $string, $matches);

        return array_pop($matches);
    }

    public static function pregReplace(string $pattern, string $replacement, string $content, int $limit = -1): string
    {
        $result = preg_replace($pattern, $replacement, $content, $limit);
        [$replacedContent] = is_string($result) ? [$result] : $result;

        return $replacedContent;
    }
}
