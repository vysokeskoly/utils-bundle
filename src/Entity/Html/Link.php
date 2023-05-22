<?php declare(strict_types=1);

namespace VysokeSkoly\UtilsBundle\Entity\Html;

use Assert\Assertion;
use MF\Collection\Immutable\Generic\KVPair;
use MF\Collection\Immutable\Generic\ListCollection;
use MF\Collection\Immutable\Generic\Map;

class Link
{
    private array $parameters;

    public static function fromDomElement(\DOMElement $element): self
    {
        $parameters = Map::fromPairs(
            ListCollection::create($element->attributes, fn (\DOMAttr $attr) => new KVPair($attr->name, $attr->value)),
        );

        return new self($parameters->toArray());
    }

    public function __construct(array $parameters)
    {
        Assertion::keyExists($parameters, 'href');
        $this->setParameters($parameters);
    }

    private function setParameters(array $parameters): void
    {
        $this->parameters = $parameters;
    }

    public function getHref(): string
    {
        return $this->parameters['href'];
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function setHref(string $href): void
    {
        $parameters = $this->getParameters();
        $parameters['href'] = $href;

        $this->setParameters($parameters);
    }
}
