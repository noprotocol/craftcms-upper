<?php

namespace OneTribe\Upper;

class TagCollection
{
    protected array $tags = [];
    protected string $keyPrefix = '';

    public function add(string $tag): void
    {
        $this->tags[] = $this->prepareTag($tag);
    }

    public function getAll(): array
    {
        return $this->tags;
    }

    public function getUntilMaxBytes(int $maxBytes = null): array
    {
        if ($maxBytes === null) {
            return $this->tags;
        }

        $tags = [];
        $runningSize = 0;

        foreach ($this->tags as $tag) {
            $thisSize = mb_strlen($tag.' ', '8bit');

            if ($runningSize + $thisSize > $maxBytes) {
                break;
            }

            $runningSize += $thisSize;
            $tags[] = $tag;
        }

        return $tags;
    }

    public function addTagsFromElement(array $elementRawQueryResult = null): void
    {
        if (!is_array($elementRawQueryResult)) {
            return;
        }

        foreach ($this->extractTags($elementRawQueryResult) as $tag) {
            $this->add($tag);
        }

        $this->unique();
    }

    public function setKeyPrefix(string $keyPrefix): void
    {
        $this->keyPrefix = $keyPrefix;
    }

    /**
     * Prepends tag with configured prefix.
     * To prevent key collision if you use the same
     * cache server for several Craft installations.
     */
    public function prepareTag(string $tag): string
    {
        return $this->keyPrefix . $tag;
    }

    protected function extractTags(array $elementRawQueryResult = null): array
    {
        $tags = [];
        $properties = array_keys(Plugin::ELEMENT_PROPERTY_MAP);

        foreach ($properties as $prop) {
            if (isset($elementRawQueryResult[$prop]) && ! is_null($elementRawQueryResult[$prop])) {
                $tags[] = Plugin::ELEMENT_PROPERTY_MAP[$prop] . $elementRawQueryResult[$prop];
            }
        }

        return $tags;
    }

    protected function unique(): static
    {
        $this->tags = array_unique($this->tags);

        return $this;
    }
}
