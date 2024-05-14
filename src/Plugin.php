<?php

namespace OneTribe\Upper;

use Craft;
use craft\base\Model;
use craft\base\Plugin as BasePlugin;
use OneTribe\Upper\Behaviors\CacheControlBehavior;
use OneTribe\Upper\Behaviors\TagHeaderBehavior;
use OneTribe\Upper\Drivers\CachePurgeInterface;
use OneTribe\Upper\Models\Settings;

/**
 * @method models\Settings getSettings()
 */
class Plugin extends BasePlugin
{
    // Event names
    public const EVENT_AFTER_SET_TAG_HEADER = 'upper_after_set_tag_header';
    public const EVENT_BEFORE_PURGE = 'upper_before_purge';
    public const EVENT_AFTER_PURGE = 'upper_after_purge';

    // Tag prefixes
    public const TAG_PREFIX_ELEMENT = 'el';
    public const TAG_PREFIX_SECTION = 'se';
    public const TAG_PREFIX_STRUCTURE = 'st';

    // Mapping element properties <> tag prefixes
    public const ELEMENT_PROPERTY_MAP = [
        'id' => self::TAG_PREFIX_ELEMENT,
        'sectionId' => self::TAG_PREFIX_SECTION,
        'structureId' => self::TAG_PREFIX_STRUCTURE,
    ];

    // DB
    public const CACHE_TABLE = '{{%upper_cache}}';

    // Header
    public const INFO_HEADER_NAME = 'X-UPPER-CACHE';
    public const TRUNCATED_HEADER_NAME = 'X-UPPER-CACHE-TRUNCATED';

    public string $schemaVersion = '1.0.1';

    /**
     * @throws \yii\base\InvalidConfigException
     */
    public function init()
    {
        parent::init();

        // Config pre-check
        if (! $this->getSettings()->drivers || ! $this->getSettings()->driver) {
            return false;
        }

        // Register plugin components
        $this->setComponents([
            'purger' => PurgerFactory::create($this->getSettings()->toArray()),
            'tagCollection' => TagCollection::class,
        ]);

        // Attach Behaviors
        Craft::$app->getResponse()->attachBehavior('cache-control', CacheControlBehavior::class);
        Craft::$app->getResponse()->attachBehavior('tag-header', TagHeaderBehavior::class);

        // Register event handlers
        EventRegistrar::registerFrontendEvents();
        EventRegistrar::registerCpEvents();
        EventRegistrar::registerUpdateEvents();

        if ($this->getSettings()->useLocalTags) {
            EventRegistrar::registerFallback();
        }

        // Register Twig extension
        Craft::$app->getView()->registerTwigExtension(new TwigExtension());
    }

    /**
     * @throws \yii\base\InvalidConfigException
     */
    public function getPurger(): CachePurgeInterface
    {
        return $this->get('purger');
    }

    /**
     * @throws \yii\base\InvalidConfigException
     */
    public function getTagCollection(): TagCollection
    {
        /* @var \OneTribe\Upper\TagCollection $collection */
        $collection = $this->get('tagCollection');
        $collection->setKeyPrefix($this->getSettings()->getKeyPrefix());

        return $collection;
    }

    /**
     * Creates and returns the model used to store the plugin’s settings.
     */
    protected function createSettingsModel(): ?Model
    {
        return new Settings();
    }


    /**
     * Is called after the plugin is installed.
     * Copies example config to project's config folder
     */
    protected function afterInstall(): void
    {
        $configSourceFile = __DIR__ . DIRECTORY_SEPARATOR . 'config.example.php';
        $configTargetFile = Craft::$app->getConfig()->configDir . DIRECTORY_SEPARATOR . $this->handle . '.php';

        if (!file_exists($configTargetFile)) {
            copy($configSourceFile, $configTargetFile);
        }
    }
}
