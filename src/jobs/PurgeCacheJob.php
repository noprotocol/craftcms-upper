<?php

namespace OneTribe\Upper\Jobs;

use Craft;
use craft\queue\BaseJob;
use OneTribe\Upper\Plugin;

class PurgeCacheJob extends BaseJob
{
    public string $tag;

    /**
     * @throws \yii\base\InvalidConfigException
     */
    public function execute($queue): void
    {
        if (! $this->tag) {
            return;
        }

        // Get registered purger
        $purger = Plugin::getInstance()->getPurger();
        $purger->purgeTag($this->tag);
    }

    protected function defaultDescription(): string
    {
        return Craft::t('upper', 'Upper Purge: {tag}', ['tag' => $this->tag]);
    }
}
