<?php declare(strict_types=1);

namespace VysokeSkoly\UtilsBundle\Service;

class LinkPlaceholderTransformer
{
    public function transformToPlaceholder(string $content): string
    {
        return sprintf('$$%s$$', $content);
    }

    public function transformFromPlaceholder(string $placeholder): string
    {
        return trim($placeholder, '$');
    }

    public function isPlaceholder(string $href): bool
    {
        return StringUtils::startsWith($href, '$$') && StringUtils::endsWith($href, '$$');
    }
}
