<?php

namespace OneTribe\Upper\Behaviors;

use yii\base\Behavior;

/**
 * @property \yii\web\Response $owner
 */
class TagHeaderBehavior extends Behavior
{
    /**
     * Simply tag
     */
    public function setTagHeader(string $name, array $tags, string $delimiter = null): bool
    {
        $headers = $this->owner->getHeaders();

        // no tags
        if (count($tags) === 0) {
            return false;
        }

        if (is_string($delimiter)) {
            // concatenate with $delimiter
            $headers->add($name, implode($delimiter, $tags));

            return true;
        }

        foreach ($tags as $tag) {
            // add multiple
            $headers->add($name, $tag);
        }

        return true;
    }
}
