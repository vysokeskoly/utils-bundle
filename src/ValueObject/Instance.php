<?php

declare(strict_types=1);

namespace VysokeSkoly\UtilsBundle\ValueObject;

use Assert\Assertion;

class Instance implements \Stringable
{
    public function __construct(
        private string $domain,
        private string $context,
        private string $purpose,
        private string $version,
    ) {}

    public function __toString(): string
    {
        return $this->concat();
    }

    public function getDomain(): string
    {
        return $this->domain;
    }

    public function getContext(): string
    {
        return $this->context;
    }

    public function getPurpose(): string
    {
        return $this->purpose;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public static function parse(string $value, string $separator = '-'): self
    {
        Assertion::notEmpty($separator, 'Separator must not be empty');
        [$domain, $context, $purpose, $version] = explode($separator, $value, 4) + [null, null, null, null];

        Assertion::notEmpty($domain, 'Domain must not be empty.');
        Assertion::notEmpty($context, 'Context must not be empty.');
        Assertion::notEmpty($purpose, 'Purpose must not be empty.');
        Assertion::notEmpty($version, 'Version must not be empty.');

        return new self($domain, $context, $purpose, $version);
    }

    public function concat(string $separator = '-'): string
    {
        return implode($separator, [
            $this->domain,
            $this->context,
            $this->purpose,
            $this->version,
        ]);
    }

    public function concatLower(string $separator = '-'): string
    {
        return mb_strtolower($this->concat($separator));
    }

    public function toK8sUrl(?int $port = null): string
    {
        $url = sprintf(
            'http://%s-%s-%s.%s.svc.cluster.local%s',
            $this->getContext(),
            $this->getPurpose(),
            $this->getVersion(),
            $this->getDomain(),
            $port ? ':' . $port : '',
        );

        return mb_strtolower($url);
    }
}
