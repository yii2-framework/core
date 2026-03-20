<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\sqlite\providers;

use yii\db\ColumnSchemaBuilder;
use yii\db\Schema;

/**
 * Data provider for {@see \yiiunit\framework\db\sqlite\ColumnTypeTest} column type test cases.
 *
 * Provides SQLite-specific input/output pairs for column type operations.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
final class ColumnTypeProvider extends \yiiunit\base\db\providers\ColumnTypeProvider
{
    public static function columnTypes(): array
    {
        return [
            ...parent::columnTypes(),
            'pk first after' => [
                Schema::TYPE_PK,
                static fn($t): ColumnSchemaBuilder => $t->primaryKey()->first()->after('col_before'),
                ['sqlite' => 'integer PRIMARY KEY AUTOINCREMENT NOT NULL'],
            ],
        ];
    }
}
