<?php

namespace OneTribe\Upper\Drivers;

use Craft;

class Dummy extends AbstractPurger implements CachePurgeInterface
{
    public bool $logPurgeActions = true;

    public function purgeTag(string $tag): bool
    {
        $this->log("Dummy::purgeTag($tag) was called.");

        if ($this->useLocalTags) {
            $this->purgeUrlsByTag($tag);
        }

        return true;
    }

    public function purgeUrls(array $urls): bool
    {
        $joinedUrls = implode(',', $urls);
        $this->log("Dummy::purgeUrls([$joinedUrls]') was called.");

        return true;
    }

    /**
     * @throws \yii\db\Exception
     */
    public function purgeAll(): bool
    {
        if ($this->useLocalTags) {
            $this->clearLocalCache();
        }

        $this->log("Dummy::purgeAll() was called.");

        return true;
    }

    protected function log(?string $message = null): void
    {
        if (!$this->logPurgeActions) {
            return;
        }

        Craft::warning($message, "upper");
    }
}
