<?php

declare(strict_types=1);

namespace VysokeSkoly\UtilsBundle\ValueObject;

class Environment implements \Stringable, \JsonSerializable
{
    private function __construct(
        private readonly Tier $tier,
        private readonly ?int $number,
        private readonly string $space,
    ) {}

    public function __toString(): string
    {
        return $this->toAlias();
    }

    /**
     * Parse environment alias string like "dev1-eduroute", "prod", "deploy-services".
     *
     * Supported formats:
     *  - {tier}{number}-{space}  (e.g. "dev1-eduroute")
     *  - {tier}-{space}          (e.g. "deploy-services")
     *  - {tier}{number}          (e.g. "dev21")
     *  - {tier}                  (e.g. "prod")
     */
    public static function parse(string $alias): self
    {
        $tierValues = array_map(fn(Tier $t) => $t->value, Tier::cases());
        // Sort by length descending to match longest first (e.g. "deploy" before "dev")
        usort($tierValues, fn(string $a, string $b) => mb_strlen($b) <=> mb_strlen($a));

        $tierPattern = implode('|', array_map('preg_quote', $tierValues));
        $pattern = sprintf('/^(%s)(\d+)?(?:-(.+))?$/', $tierPattern);

        if (preg_match($pattern, $alias, $matches) !== 1) {
            throw new \InvalidArgumentException(sprintf('Cannot parse environment alias "%s".', $alias));
        }

        $tier = Tier::parse($matches[1]);
        $number = isset($matches[2]) && $matches[2] !== '' ? (int) $matches[2] : null;
        $space = $matches[3] ?? '';

        return new self($tier, $number, $space);
    }

    public static function create(Tier $tier, ?int $number = null, string $space = ''): self
    {
        return new self($tier, $number, $space);
    }

    public function getTier(): Tier
    {
        return $this->tier;
    }

    public function getNumber(): ?int
    {
        return $this->number;
    }

    public function getSpace(): string
    {
        return $this->space;
    }

    public function toAlias(): string
    {
        $alias = $this->tier->value;

        if ($this->number !== null) {
            $alias .= $this->number;
        }

        if ($this->space !== '') {
            $alias .= '-' . $this->space;
        }

        return $alias;
    }

    // Tier convenience methods

    public function isDev(): bool
    {
        return $this->tier->isDev();
    }

    public function isDevel(): bool
    {
        return $this->tier->isDevel();
    }

    public function isDeploy(): bool
    {
        return $this->tier->isDeploy();
    }

    public function isIntegration(): bool
    {
        return $this->tier->isIntegration();
    }

    public function isProd(): bool
    {
        return $this->tier->isProd();
    }

    public function equals(self $other): bool
    {
        return $this->toAlias() === $other->toAlias();
    }

    public function jsonSerialize(): string
    {
        return $this->toAlias();
    }
}
