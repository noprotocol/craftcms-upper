<?php

namespace OneTribe\Upper\Models;

use craft\base\Model;
use yii\helpers\Inflector;

/**
 * Upper Settings Model
 *
 * This is a model used to define the plugin's settings.
 *
 * Models are containers for data. Just about every time information is passed
 * between services, controllers, and templates in Craft, it’s passed via a model.
 *
 * https://craftcms.com/docs/plugins/models
 *
 * @author    Oliver Stark
 * @package   Upper
 * @since     1.0.0
 */
class Settings extends Model
{
    public string $driver;

    public array $drivers = [];

    public ?int $defaultMaxAge = null;

    public bool $useLocalTags = true;

    public string $keyPrefix = '';

    public ?int $maxBytesForCacheTagHeader = null;

    public ?int $jobTtr = null;

    /**
     * Returns the validation rules for attributes.
     *
     * Validation rules are used by [[validate()]] to check if attribute values are valid.
     * Child classes may override this method to declare different validation rules.
     *
     * More info: http://www.yiiframework.com/doc-2.0/guide-input-validation.html
     */
    public function rules(): array
    {
        return [
            [
                [
                    'driver',
                    'drivers',
                    'keyPrefix',
                ],
                'required'
            ],
        ];
    }

    public function getTagHeaderName(): string
    {
        return $this->drivers[$this->driver]['tagHeaderName'];
    }

    public function getHeaderTagDelimiter(): string
    {
        return $this->drivers[$this->driver]['tagHeaderDelimiter'] ?? ' ';
    }

    /**
     * Get key prefix.
     * To prevent key collision if you use the same
     * cache server for several Craft installations.
     */
    public function getKeyPrefix(): string
    {
        if (!$this->keyPrefix) {
            return '';
        }

        $clean = Inflector::slug($this->keyPrefix);

        return substr($clean, 0, 8);
    }

    public function getNoCacheElements(): array
    {
        return ['craft\elements\User', 'craft\elements\MatrixBlock', 'verbb\supertable\elements\SuperTableBlockElement'];
    }

    public function isCachableElement(string $class): bool
    {
        return in_array($class, $this->getNoCacheElements(), true) ? false : true;
    }

}
