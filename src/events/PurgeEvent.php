<?php

namespace OneTribe\Upper\Events;

use yii\base\Event;

class PurgeEvent extends Event
{
    public string $tag;
}
