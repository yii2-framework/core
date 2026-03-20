<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\mysql\providers;

use yii\db\ColumnSchemaBuilder;
use yii\db\Schema;

/**
 * Data provider for {@see \yiiunit\framework\db\mysql\QueryBuilderTest} column type test cases.
 *
 * Provides MySQL-specific input/output pairs for column type operations.
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
            'datetime' => [
                Schema::TYPE_DATETIME,
                static fn($t): ColumnSchemaBuilder => $t->dateTime(),
                ['mysql' => 'datetime(0)'],
            ],
            'datetime not null' => [
                Schema::TYPE_DATETIME . ' NOT NULL',
                static fn($t): ColumnSchemaBuilder => $t->dateTime()->notNull(),
                ['mysql' => 'datetime(0) NOT NULL'],
            ],
            'json' => [
                Schema::TYPE_JSON,
                static fn($t): ColumnSchemaBuilder => $t->json(),
                ['mysql' => 'json'],
            ],
            'pk after' => [
                Schema::TYPE_PK . ' AFTER `col_before`',
                static fn($t): ColumnSchemaBuilder => $t->primaryKey()->after('col_before'),
                ['mysql' => 'int NOT NULL AUTO_INCREMENT PRIMARY KEY AFTER `col_before`'],
            ],
            'pk comment after' => [
                Schema::TYPE_PK . " COMMENT 'test' AFTER `col_before`",
                static fn($t): ColumnSchemaBuilder => $t->primaryKey()->comment('test')->after('col_before'),
                ['mysql' => "int NOT NULL AUTO_INCREMENT PRIMARY KEY COMMENT 'test' AFTER `col_before`"],
            ],
            'pk comment with quotes after' => [
                Schema::TYPE_PK . " COMMENT 'testing \'quote\'' AFTER `col_before`",
                static fn($t): ColumnSchemaBuilder => $t->primaryKey()->comment('testing \'quote\'')->after('col_before'),
                ['mysql' => "int NOT NULL AUTO_INCREMENT PRIMARY KEY COMMENT 'testing \'quote\'' AFTER `col_before`"],
            ],
            'pk first' => [
                Schema::TYPE_PK . ' FIRST',
                static fn($t): ColumnSchemaBuilder => $t->primaryKey()->first(),
                ['mysql' => 'int NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST'],
            ],
            'pk first over after' => [
                Schema::TYPE_PK . ' FIRST',
                static fn($t): ColumnSchemaBuilder => $t->primaryKey()->first()->after('col_before'),
                ['mysql' => 'int NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST'],
            ],
            'pk(8) after' => [
                Schema::TYPE_PK . '(8) AFTER `col_before`',
                static fn($t): ColumnSchemaBuilder => $t->primaryKey(8)->after('col_before'),
                ['mysql' => 'int NOT NULL AUTO_INCREMENT PRIMARY KEY AFTER `col_before`'],
            ],
            'pk(8) first' => [
                Schema::TYPE_PK . '(8) FIRST',
                static fn($t): ColumnSchemaBuilder => $t->primaryKey(8)->first(),
                ['mysql' => 'int NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST'],
            ],
            'pk(8) first over after' => [
                Schema::TYPE_PK . '(8) FIRST',
                static fn($t): ColumnSchemaBuilder => $t->primaryKey(8)->first()->after('col_before'),
                ['mysql' => 'int NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST'],
            ],
            'time' => [
                Schema::TYPE_TIME,
                static fn($t): ColumnSchemaBuilder => $t->time(),
                ['mysql' => 'time(0)'],
            ],
            'time not null' => [
                Schema::TYPE_TIME . ' NOT NULL',
                static fn($t): ColumnSchemaBuilder => $t->time()->notNull(),
                ['mysql' => 'time(0) NOT NULL'],
            ],
            'timestamp' => [
                Schema::TYPE_TIMESTAMP,
                static fn($t): ColumnSchemaBuilder => $t->timestamp(),
                ['mysql' => 'timestamp(0)'],
            ],
            'timestamp not null' => [
                Schema::TYPE_TIMESTAMP . ' NOT NULL',
                static fn($t): ColumnSchemaBuilder => $t->timestamp()->notNull(),
                ['mysql' => 'timestamp(0) NOT NULL'],
            ],
            'timestamp null default null' => [
                Schema::TYPE_TIMESTAMP . ' NULL DEFAULT NULL',
                static fn($t): ColumnSchemaBuilder => $t->timestamp()->defaultValue(null),
                ['mysql' => 'timestamp(0) NULL DEFAULT NULL'],
            ],
        ];
    }
}
