<?php declare(strict_types=1);

namespace VysokeSkoly\UtilsBundle\Service;

use MF\Collection\Immutable\Generic\ISeq;
use MF\Collection\Immutable\Generic\Map;
use MF\Collection\Immutable\Generic\Seq;
use function Safe\preg_match_all;
use VysokeSkoly\UtilsBundle\Entity\Html\Image;
use VysokeSkoly\UtilsBundle\Entity\Html\Link;
use VysokeSkoly\UtilsBundle\Exception\FindHtmlTagException;
use VysokeSkoly\UtilsBundle\Exception\UnexpectedKeyValueCountInFindHtmlTagException;

class HtmlHelper
{
    private const EOL_PLACEHOLDER = '@*@';
    private const TEMPLATE_SINGLE_TAG = '<%s %s />';
    private const TEMPLATE_PAIR_TAG = '<%s %s>';

    /**
     * @see https://stackoverflow.com/questions/18349130/how-to-parse-html-in-php
     * @example // span[@class='".$class."']
     * @example // img
     *
     * @phpstan-return ISeq<\DOMElement>
     */
    public function xpathHtmlDocument(string $content, string $xpathQuery): ISeq
    {
        return Seq::init(function () use ($xpathQuery, $content) {
            $htmlContent = $this->transformUnsupportedHtml($content);
            $htmlContent = $this->encodeHtml($htmlContent);

            // @see https://www.php.net/manual/en/domdocument.loadhtml.php#95251
            $dom = new \DOMDocument();
            $dom->loadHTML(sprintf('<?xml encoding="UTF-8">%s', $htmlContent));

            $xpath = new \DOMXPath($dom);
            $elements = $xpath->query($xpathQuery);

            if ($elements instanceof \DOMNodeList) {
                yield from $elements;
            }
        });
    }

    /**
     * @see https://stackoverflow.com/questions/1685277/warning-domdocumentloadhtml-htmlparseentityref-expecting-in-entity
     *
     * It is meant to encode html entities, which would otherwise break the DOMDocument::loadHTML() method.
     */
    private function encodeHtml(string $content): string
    {
        return str_replace(
            ['&gt;', '&lt;'],
            ['>', '<'],
            htmlentities($content, ENT_NOQUOTES, 'UTF-8', false),
        );
    }

    private function transformUnsupportedHtml(string $originalContent): string
    {
        $unsupportedTags = Map::from([
            'figure' => [
                'start' => '<div data-tag-type-replacement="figure"',
                'end' => '</div',
            ],
        ]);

        $content = $unsupportedTags
            ->reduce(
                fn (string $acc, array $value, int|string $key = null) => str_replace(
                    ['<' . $key, '</' . $key],
                    [$value['start'], $value['end']],
                    $acc,
                ),
                $originalContent,
            );

        return $content;
    }

    /**
     * @return Image[]
     */
    public function findAllImages(string $content): array
    {
        return $this->findAllTags('img', 'src', $content, Image::fromDomElement(...));
    }

    /**
     * @phpstan-template Tag
     *
     * @phpstan-param callable(\DOMElement): Tag $createTag
     * @phpstan-return Tag[]
     */
    private function findAllTags(string $tag, string $requiredAttr, string $content, callable $createTag): array
    {
        $mappedContent = str_replace("\n", self::EOL_PLACEHOLDER, $content);
        preg_match_all(sprintf('/<%s( .*?)>/', $tag), $mappedContent, $matches);

        return $this->mapMatches(
            $this->mapEoLPlaceholders(array_pop($matches)),
            $this->xpathHtmlDocument($content, sprintf('//%s[@%s]', $tag, $requiredAttr)),
            $createTag,
        );
    }

    /**
     * @phpstan-template T
     *
     * @phpstan-param string[] $matches
     * @phpstan-param ISeq<\DOMElement> $elements
     * @phpstan-param callable(\DOMElement): T $mapper
     *
     * @return array<string, T> [match => T]
     */
    private function mapMatches(
        array $matches,
        ISeq $elements,
        callable $mapper,
        bool $tryingWithMatchingAttributes = false,
    ): array {
        $keys = array_filter(array_values($matches));
        $values = $elements
            ->map($mapper)
            ->toArray();

        // var_dump(['matches' => $matches, 'values' => $values, 'keys' => $keys]); // left for debug

        try {
            if (count($keys) === count($values)) {
                return array_combine($keys, $values);
            } elseif (count($keys) < count($values) && !$tryingWithMatchingAttributes) {
                $isMatchingElement = $this->createFilterWithMatchingAttributes($matches);

                return $this->mapMatches($matches, $elements->filter($isMatchingElement), $mapper, true);
            }

            throw new UnexpectedKeyValueCountInFindHtmlTagException();
        } catch (\Throwable $e) {
            throw new FindHtmlTagException(
                'While mapping matches, there was thrown an exception.',
                $e,
                $keys,
                $values,
                $tryingWithMatchingAttributes,
            );
        }
    }

    /**
     * It will create a filter function, which ensures that elements passing are matching some of the given matches.
     * It will only check the attributes (href|src), which are present in the matches.
     *
     * @phpstan-return callable(\DOMElement): bool
     */
    private function createFilterWithMatchingAttributes(array $matches): callable
    {
        $matchesKeys = Seq::init(function () use ($matches) {
            foreach ($matches as $match) {
                preg_match('/(href|src)=(".*?"|\'.*?\')/', $match, $attribute);

                if (!empty($attribute[1]) && !empty($attribute[2])) {
                    yield ['key' => $attribute[1], 'value' => trim($attribute[2], '"\'')];
                }
            }
        })
            ->toList();

        // var_dump(['matchesKeys' => $matchesKeys->toArray()]); // left for debug

        return fn (\DOMElement $element) => $matchesKeys
            ->reduce(
                fn (bool $acc, array $attribute) => $acc || (
                    $element->hasAttribute($attribute['key']) &&
                    $element->getAttribute($attribute['key']) === $attribute['value']
                ),
                false,
            );
    }

    private function mapEoLPlaceholders(array $values): array
    {
        return array_map($this->mapEoL(...), $values);
    }

    private function mapEoL(mixed $value): mixed
    {
        return is_string($value)
            ? str_replace(self::EOL_PLACEHOLDER, "\n", $value)
            : $value;
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
        return $this->findAllTags('a', 'href', $content, Link::fromDomElement(...));
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
        if (empty($search)) {
            return $content;
        }

        // intentional use of strpos and strlen because of substr_replace is not multi-byte
        $position = strrpos($content, $search);

        if ($search !== null && $position !== false) {
            return substr_replace($content, $search . $textToInsert, $position, strlen($search));
        }

        return $content;
    }
}
