<?php declare(strict_types=1);

namespace VysokeSkoly\UtilsBundle\Service;

use Assert\Assertion;
use function Safe\array_combine;
use function Safe\preg_match_all;
use function Safe\sprintf;
use VysokeSkoly\UtilsBundle\Entity\Html\Image;
use VysokeSkoly\UtilsBundle\Entity\Html\Link;

class HtmlHelper
{
    private const TEMPLATE_SINGLE_TAG = '<%s %s />';
    private const TEMPLATE_PAIR_TAG = '<%s %s>';

    /**
     * @return Image[]
     */
    public function findAllImages(string $content): array
    {
        preg_match_all('/<img(.*?)>/', $content, $matches);

        return $this->mapMatches(array_pop($matches), function (array $parameters) {
            return new Image($parameters);
        });
    }

    /**
     * @return array [match => generatedEntity]
     */
    private function mapMatches(array $matches, callable $generator): array
    {
        $values = array_map(function ($entityString) use ($generator) {
            $parts = explode('" ', $this->normalize($entityString));
            $parameters = [];

            foreach ($parts as $part) {
                if (mb_strpos($part, '="') !== false) {
                    [$key, $value] = explode('=', $part, 2);
                } else {
                    $key = $part;
                    $value = '';
                }

                if ($key === '/') {
                    continue;
                }

                $parameters[$key] = $this->trimParameter($value);
            }

            return call_user_func($generator, $parameters);
        }, $matches);

        return array_combine(array_values($matches), $values);
    }

    private function normalize(string $matchValue): string
    {
        $replaces = [
            "\t" => '',
            "='" => '="',
            "' " => '" ',
        ];

        $normalized = trim(strtr($matchValue, $replaces));

        // strtr replaces only ' with next atribute (field='...' next='...')
        // but to fix the last one, we need to do separately with regex
        return RegexHelper::pregReplace('/\'$/', '"', $normalized);
    }

    private function trimParameter(string $value): string
    {
        return trim($value, '"');
    }

    public function transformToTag(string $tag, array $parameters, bool $isSingleTag): string
    {
        $parametersHtml = [];
        foreach ($parameters as $key => $value) {
            $parametersHtml[] = sprintf('%s="%s"', $key, $value);
        }

        $template = $isSingleTag ? self::TEMPLATE_SINGLE_TAG : self::TEMPLATE_PAIR_TAG;

        return sprintf($template, $tag, implode(' ', $parametersHtml));
    }

    /**
     * @param array $replacements [original => new]
     */
    public function replaceAllInContent(string $content, array $replacements): string
    {
        return strtr($content, $replacements);
    }

    /**
     * @return Link[]
     */
    public function findAllLinks(string $content): array
    {
        preg_match_all('/<a( {1}.*?)>/', $content, $matches);

        return $this->mapMatches(array_pop($matches), function (array $parameters) {
            Assertion::notEmpty($parameters);

            if (!array_key_exists('href', $parameters)) {
                $originalParameters = $parameters;
                $parameters = [];

                foreach ($originalParameters as $key => $value) {
                    if (StringUtils::contains($key, 'href') && StringUtils::contains($key, '=')) {
                        [, $href] = explode('=', $key, 2);

                        $parameters['href'] = $href;
                    } else {
                        $parameters[$key] = $value;
                    }
                }
            }

            return new Link($parameters);
        });
    }

    public function findAllParagraphs(string $content): array
    {
        preg_match_all('/<p.*>.*<\/p>/U', $content, $matches);

        return array_pop($matches);
    }

    public function insertAfterFirst(string $content, string $textToInsert, ?string $search = null): string
    {
        if ($search === null) {
            return $content . $textToInsert;
        }

        $pattern = '/' . preg_quote($search, '/') . '/';

        return RegexHelper::pregReplace($pattern, $search . $textToInsert, $content, 1);
    }

    public function insertAfterLast(string $content, string $textToInsert, string $search): string
    {
        // intentional use of strpos and strlen because of substr_replace is not multi-byte
        $position = strrpos($content, $search);

        if ($search !== null && $position !== false) {
            return substr_replace($content, $search . $textToInsert, $position, strlen($search));
        }

        return $content;
    }
}
