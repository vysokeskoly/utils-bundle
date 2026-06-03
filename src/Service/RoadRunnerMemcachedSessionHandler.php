<?php

declare(strict_types=1);

namespace VysokeSkoly\UtilsBundle\Service;

use Symfony\Component\HttpFoundation\Session\Storage\Handler\MemcachedSessionHandler;

/**
 * Extends the default MemcachedSessionHandler for RoadRunner's long-lived worker process.
 *
 * Overrides:
 *  - close(): Prevents $memcached->quit() which tears down the persistent connection.
 *  - open(): Suppresses the native Cache-Control header (useless in RoadRunner, goes to void).
 *  - write(): Skips the parent's "destroy session on empty data" logic. In RoadRunner,
 *    destroy() calls setcookie() which is a no-op and can cause subtle issues.
 *    Empty sessions are simply not persisted (return true without writing).
 */
class RoadRunnerMemcachedSessionHandler extends MemcachedSessionHandler
{
    public function open(string $savePath, string $sessionName): bool
    {
        // Skip parent's header() call — in RoadRunner there's no SAPI output buffer.
        // The parent sends Cache-Control via header() which goes to void in RR.
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    /**
     * Override to prevent session destruction when data is empty.
     *
     * The parent's write() calls destroy() when session data is empty (igbinary edge case).
     * In RoadRunner, destroy() triggers setcookie() which is useless (no SAPI).
     * Instead, we simply skip writing empty sessions — they'll expire naturally via TTL.
     */
    public function write(string $sessionId, string $data): bool
    {
        // Don't persist empty sessions, but don't destroy them either
        if ($data === '' || $data === (\function_exists('igbinary_serialize') ? igbinary_serialize([]) : '')) {
            return true;
        }

        return $this->doWrite($sessionId, $data);
    }
}
