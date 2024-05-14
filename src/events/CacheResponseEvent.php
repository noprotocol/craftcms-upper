<?php

namespace OneTribe\Upper\Events;

use yii\base\Event;

class CacheResponseEvent extends Event
{
    public array $tags = [];
    public string $requestUrl;
    public int $maxAge = 0;
    public string $output;
    public array $headers = [];

}
