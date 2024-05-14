<?php

namespace OneTribe\Upper\Drivers;

use Craft;
use OneTribe\Upper\Plugin;
use yii\base\BaseObject;
use yii\db\Exception;
use yii\helpers\ArrayHelper;

class AbstractPurger extends BaseObject
{
    public bool $useLocalTags;

    public function __construct($config)
    {
        parent::__construct($config);
    }

    public function purgeUrls(array $urls): bool
    {
        $joinedUrls = implode(',', $urls);

        Craft::warning("Method purgeUrls([$joinedUrls]') was called - not implemented by driver ", "upper");

        return true;
    }

    public function purgeUrlsByTag(string $tag): bool
    {
        try {
            if ($urls = $this->getTaggedUrls($tag)) {

                $this->purgeUrls(array_values($urls));
                $this->invalidateLocalCache(array_keys($urls));

                return true;
            }
        } catch (Exception $e) {
            Craft::warning("Failed to purge '$tag'.", "upper");
        }

        return false;
    }

    /**
     * Get cached urls by given tag
     *
     * Example result
     * [
     *   '2481f019-27a4-4338-8784-10d1781b' => '/services/development'
     *   'a139aa60-9b56-43b0-a9f5-bfaa7c68' => '/services/app-development'
     * ]
     *
     * @throws \yii\db\Exception
     */
    public function getTaggedUrls(string $tag): array
    {
        // Use fulltext for mysql or array field for pgsql
        $sql = Craft::$app->getDb()->getIsMysql()
            ? "SELECT uid, url FROM %s WHERE MATCH(tags) AGAINST (%s IN BOOLEAN MODE)"
            : "SELECT uid, url FROM %s WHERE tags @> (ARRAY[%s]::varchar[])";

        // Replace table name and tag
        $sql = sprintf(
            $sql,
            Craft::$app->getDb()->quoteTableName(Plugin::CACHE_TABLE),
            Craft::$app->getDb()->quoteValue($tag)
        );

        // Execute the sql
        $results = Craft::$app->getDb()
            ->createCommand($sql)
            ->queryAll();

        if (count($results) === 0) {
            return [];
        }

        return ArrayHelper::map($results, 'uid', 'url');
    }

    /**
     * @throws \yii\db\Exception
     */
    public function invalidateLocalCache(array $uids): int
    {
        return Craft::$app->getDb()->createCommand()
            ->delete(Plugin::CACHE_TABLE, ['uid' => $uids])
            ->execute();
    }


    /**
     * @throws \yii\db\Exception
     */
    public function clearLocalCache(): int
    {
        return Craft::$app->getDb()->createCommand()
            ->delete(Plugin::CACHE_TABLE)
            ->execute();
    }
}
