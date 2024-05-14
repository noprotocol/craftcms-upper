<?php

namespace OneTribe\Upper\Migrations;

use craft\db\Migration;
use OneTribe\Upper\Plugin;

class m180618_120307_url_index extends Migration
{
    public function safeUp(): bool
    {
        echo "  > Truncate table: " . Plugin::CACHE_TABLE . PHP_EOL;
        $this->truncateTable(Plugin::CACHE_TABLE);

        echo "  > Drop index: url_idx" . PHP_EOL;
        $this->dropIndex('url_idx', Plugin::CACHE_TABLE);

        echo "  > Alter column: 'url' - string to text " . PHP_EOL;
        $this->alterColumn(Plugin::CACHE_TABLE, 'url', $this->text());

        echo "  > Add column: 'urlHash'" . PHP_EOL;
        $this->addColumn(Plugin::CACHE_TABLE, 'urlHash', $this->string(32)->notNull()->after('url'));

        echo "  > Create index: urlhash_idx" . PHP_EOL;

        $this->createIndex('urlhash_idx', Plugin::CACHE_TABLE, 'urlHash', true);

        return true;
    }

    public function safeDown(): bool
    {
        echo "m180618_120307_url_index cannot be reverted.\n";

        return false;
    }
}
