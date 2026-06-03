<?php

declare(strict_types=1);

namespace VysokeSkoly\UtilsBundle\Service;

use Symfony\Component\HttpFoundation\Request;
use VysokeSkoly\UtilsBundle\Environment\EnvironmentInterface;

/**
 * Resolves the debug level for the current request.
 * Implementations typically check CF Access tokens, internal IPs, cookies, query params, etc.
 */
interface DebugLevelResolverInterface
{
    /**
     * Resolve debug level from the request context.
     * Return null if the resolver does not want to override the current debug level.
     */
    public function resolve(Request $request, EnvironmentInterface $environment): ?DebugLevel;
}
