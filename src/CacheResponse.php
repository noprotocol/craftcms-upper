<?php

namespace OneTribe\Upper;

use OneTribe\Upper\Behaviors\CacheControlBehavior;
use yii\base\Response;

class CacheResponse
{
    public Response|CacheControlBehavior $response;

    public function __construct(Response $response)
    {
        $this->response = $response;
    }

    public function never(): void
    {
        if (!$this->isWebResponse()) {
            return;
        }

        $this->response->addCacheControlDirective('private');
        $this->response->addCacheControlDirective('no-cache');
    }

    public function for(string $time): void
    {
        if (!$this->isWebResponse()) {
            return;
        }

        $seconds = strtotime($time) - time();
        $this->response->setSharedMaxAge($seconds);
    }

    public function isWebResponse(): bool
    {
        return $this->response instanceof \craft\web\Response;
    }
}
