<?php declare(strict_types=1);

namespace VysokeSkoly\UtilsBundle\Service;

class DebugLevel
{
    public const DEBUG_OFF = 0;
    public const DEBUG_ON = 1;
    public const DEBUG_ON_NO_CACHE = 2;

    /** @var array */
    public const DEBUG_MODES = [
        self::DEBUG_OFF,
        self::DEBUG_ON,
        self::DEBUG_ON_NO_CACHE,
    ];

    private static int $level = self::DEBUG_OFF;

    public function setLevel(int $level): void
    {
        if (!in_array($level, self::DEBUG_MODES, true)) {
            $level = self::DEBUG_OFF;
        }

        self::$level = $level;
    }

    public function getDebugLevel(): int
    {
        return self::$level;
    }

    public function isDebug(): bool
    {
        return $this->getDebugLevel() !== self::DEBUG_OFF;
    }

    public function isCacheEnabled(): bool
    {
        return $this->getDebugLevel() !== self::DEBUG_ON_NO_CACHE;
    }

    public function clear(): void
    {
        $this->setLevel(self::DEBUG_OFF);
    }
}
