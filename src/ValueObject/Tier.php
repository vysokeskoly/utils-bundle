<?php

declare(strict_types=1);

namespace VysokeSkoly\UtilsBundle\ValueObject;

enum Tier: string
{
    case Dev = 'dev';
    case Devel = 'devel';
    case Deploy = 'deploy';
    case Integration = 'int';
    case Prod = 'prod';

    public static function parse(string $value): self
    {
        return match ($value) {
            'dev' => self::Dev,
            'devel' => self::Devel,
            'deploy' => self::Deploy,
            'int' => self::Integration,
            'prod' => self::Prod,
            default => throw new \InvalidArgumentException(sprintf('Unknown tier "%s".', $value)),
        };
    }

    public function isDev(): bool
    {
        return $this === self::Dev;
    }

    public function isDevel(): bool
    {
        return $this === self::Devel;
    }

    public function isDeploy(): bool
    {
        return $this === self::Deploy;
    }

    public function isIntegration(): bool
    {
        return $this === self::Integration;
    }

    public function isProd(): bool
    {
        return $this === self::Prod;
    }
}
