<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\pgsql\providers;

use yii\db\ColumnSchemaBuilder;
use yii\db\Schema;

/**
 * Data provider for {@see \yiiunit\framework\db\pgsql\QueryBuilderTest} column type test cases.
 *
 * Provides PostgreSQL-specific input/output pairs for column type operations.
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
            'boolean not null default true' => [
                Schema::TYPE_BOOLEAN . ' NOT NULL DEFAULT TRUE',
                static fn($t): ColumnSchemaBuilder => $t->boolean()->notNull()->defaultValue(true),
                ['pgsql' => 'boolean NOT NULL DEFAULT TRUE'],
            ],
            'char check (single quotes)' => [
                Schema::TYPE_CHAR . ' CHECK (value LIKE \'test%\')',
                static fn($t): ColumnSchemaBuilder => $t->char()->check('value LIKE \'test%\''),
                ['pgsql' => 'char(1) CHECK (value LIKE \'test%\')'],
            ],
            'char(6) check (single quotes)' => [
                Schema::TYPE_CHAR . '(6) CHECK (value LIKE \'test%\')',
                static fn($t): ColumnSchemaBuilder => $t->char(6)->check('value LIKE \'test%\''),
                ['pgsql' => 'char(6) CHECK (value LIKE \'test%\')'],
            ],
            'char(6) unsigned' => [
                Schema::TYPE_CHAR . '(6)',
                static fn($t): ColumnSchemaBuilder => $t->char(6)->unsigned(),
                ['pgsql' => 'char(6)'],
            ],
            'integer(8) unsigned' => [
                Schema::TYPE_INTEGER . '(8)',
                static fn($t): ColumnSchemaBuilder => $t->integer(8)->unsigned(),
                ['pgsql' => 'integer'],
            ],
            'json' => [
                Schema::TYPE_JSON,
                static fn($t): ColumnSchemaBuilder => $t->json(),
                ['pgsql' => 'jsonb'],
            ],
            'timestamp(4)' => [
                Schema::TYPE_TIMESTAMP . '(4)',
                static fn($t): ColumnSchemaBuilder => $t->timestamp(4),
                ['pgsql' => 'timestamp(4)'],
            ],
        ];
    }
}
