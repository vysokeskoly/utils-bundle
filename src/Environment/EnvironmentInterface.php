<?php declare(strict_types=1);

namespace VysokeSkoly\UtilsBundle\Environment;

use Symfony\Component\HttpFoundation\Request;
use VysokeSkoly\UtilsBundle\Service\DebugLevel;

interface EnvironmentInterface
{
    public const PROD_ENV = 'prod';

    public const SYMFONY_DEV_ENV = 'dev';
    public const SYMFONY_PROD_ENV = 'prod';

    /**
     * Check if request comes from internal network (including VPN and vagrant).
     */
    public function isInternalRequest(Request $request): bool;

    public function isDevEnvironment(): bool;

    /**
     * Return name of environment as lowercase string (i.e. 'dev3', 'prod' etc.).
     */
    public function getEnvironment(): string;

    public function getSymfonyEnvironment(DebugLevel $debugLevel): string;
}
