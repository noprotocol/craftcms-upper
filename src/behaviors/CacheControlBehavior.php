<?php

namespace OneTribe\Upper\Behaviors;

use yii\base\Behavior;

/**
 * @property \yii\web\Response $owner
 */
class CacheControlBehavior extends Behavior
{
    protected array $cacheControl = [];

    /**
     * Adds a custom Cache-Control directive.
     */
    public function addCacheControlDirective(string $key, mixed $value = true): void
    {
        $this->cacheControl[$key] = $value;
        $this->owner->getHeaders()->set('Cache-Control', $this->getCacheControlHeader());
    }

    /**
     * Removes a Cache-Control directive.
     */
    public function removeCacheControlDirective(string $key): void
    {
        unset($this->cacheControl[$key]);

        $this->owner->getHeaders()->set('Cache-Control', $this->getCacheControlHeader());
    }

    /**
     * Returns true if the Cache-Control directive is defined.
     */
    public function hasCacheControlDirective(string $key): bool
    {
        return array_key_exists($key, $this->cacheControl);
    }

    /**
     * Returns a Cache-Control directive value by name.
     */
    public function getCacheControlDirective(string|int $key)
    {
        return array_key_exists($key, $this->cacheControl) ? $this->cacheControl[$key] : null;
    }

    /**
     * Returns the number of seconds after the time specified in the response's Date
     * header when the response should no longer be considered fresh.
     *
     * First, it checks for a s-maxage directive, then a max-age directive, and then it falls
     * back on an expires header. It returns null when no maximum age can be established.
     */
    public function getMaxAge(): ?int
    {
        if ($this->hasCacheControlDirective('s-maxage')) {
            return (int) $this->getCacheControlDirective('s-maxage');
        }

        if ($this->hasCacheControlDirective('max-age')) {
            return (int) $this->getCacheControlDirective('max-age');
        }

        return null;
    }

    /**
     * Sets the number of seconds after which the response should no longer be considered fresh.
     *
     * This methods sets the Cache-Control max-age directive.
     *
     * @final since version 3.2
     */
    public function setMaxAge($value): static
    {
        $this->addCacheControlDirective('max-age', $value);

        return $this;
    }

    /**
     * Sets the number of seconds after which the response should no longer be considered fresh by shared caches.
     *
     * This methods sets the Cache-Control s-maxage directive.
     *
     * @final since version 3.2
     */
    public function setSharedMaxAge($value): static
    {
        $this->addCacheControlDirective('public');
        $this->removeCacheControlDirective('private');
        $this->removeCacheControlDirective('no-cache');
        $this->addCacheControlDirective('s-maxage', $value);

        return $this;
    }

    public function getCacheControl(): array
    {
        return $this->cacheControl;
    }

    public function setCacheControlDirectiveFromString(string $value = null)
    {
        if (is_null($value) || strlen($value) === 0) {
            return false;
        }

        foreach (explode(', ', $value) as $directive) {
            $parts = explode('=', $directive);
            $this->addCacheControlDirective($parts[0], $parts[1] ?? true);
        }
    }

    protected function getCacheControlHeader(): string
    {
        $parts = [];

        ksort($this->cacheControl);

        foreach ($this->cacheControl as $key => $value) {
            if (true === $value) {
                $parts[] = $key;
            } else {
                if (preg_match('#[^a-zA-Z0-9._-]#', $value)) {
                    $value = '"' . $value . '"';
                }
                $parts[] = "$key=$value";
            }
        }

        return implode(', ', $parts);
    }
}
