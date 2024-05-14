<?php

namespace OneTribe\Upper;

use Craft;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class TwigExtension extends AbstractExtension implements GlobalsInterface
{
    /**
     * Returns a list of global variables to add to the existing list.
     *
     * @throws \yii\base\InvalidConfigException
     */
    public function getGlobals(): array
    {
        return [
            'upper' => [
                'cache' => Craft::createObject(CacheResponse::class, [Craft::$app->getResponse()]),
            ],
        ];
    }
}
