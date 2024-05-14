<?php

declare(strict_types=1);

namespace OneTribe\Upper\Drivers;

interface CachePurgeInterface
{
    /**
     * Purge cache by tag
     */
    public function purgeTag(string $tag): bool;

    /**
     * Purge cache by urls
     */
    public function purgeUrls(array $urls): bool;

    /**
     * Purge entire cache
     */
    public function purgeAll(): mixed;
}
