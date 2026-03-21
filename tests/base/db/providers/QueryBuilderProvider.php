<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\base\db\providers;

use Closure;
use yii\db\Expression;
use yii\db\Query;
use yii\db\QueryBuilder;

/**
 * Base data provider for {@see \yiiunit\base\db\BaseQueryBuilder} test cases.
 *
 * Provides representative input/output pairs for query builder operations. Driver-specific providers extend this class
 * to add or override test cases.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
class QueryBuilderProvider
{

    /**
     * @phpstan-return array<string, array{mixed[]|string|object, string, mixed[]}>
     */
    public static function conditionProvider(): array
    {
        return [
            'direct expression with params' => [
                new Expression('a = CONCAT(col1, :param1)', ['param1' => 'value1']),
                <<<SQL
                a = CONCAT(col1, :param1)
                SQL,
                ['param1' => 'value1'],
            ],
            'direct string' => [
                <<<SQL
                a = CONCAT(col1, col2)
                SQL,
                <<<SQL
                a = CONCAT(col1, col2)
                SQL,
                [],
            ],
            'expression passthrough' => [
                new Expression('NOT (any_expression(:a))', [':a' => 1]),
                <<<SQL
                NOT (any_expression(:a))
                SQL,
                [':a' => 1],
            ],
        ];
    }

    /**
     * @phpstan-return array<string, array{array, string, mixed[]}>
     */
    public static function filterConditionProvider(): array
    {
        return [
            'filter != empty' => [
                ['!=', 'a', ''],
                '',
                [],
            ],
            'filter < empty' => [
                ['<', 'a', ''],
                '',
                [],
            ],
            'filter <= empty' => [
                ['<=', 'a', ''],
                '',
                [],
            ],
            'filter <> empty' => [
                ['<>', 'a', ''],
                '',
                [],
            ],
            'filter = empty' => [
                ['=', 'a', ''],
                '',
                [],
            ],
            'filter > empty' => [
                ['>', 'a', ''],
                '',
                [],
            ],
            'filter >= empty' => [
                ['>=', 'a', ''],
                '',
                [],
            ],
            'filter and both empty' => [
                ['and', '', ''],
                '',
                [],
            ],
            'filter and first empty' => [
                ['and', '', 'id=2'],
                <<<SQL
                id=2
                SQL,
                [],
            ],
            'filter and nested or with empty' => [
                ['and', 'type=1', ['or', '', 'id=2']],
                <<<SQL
                (type=1) AND (id=2)
                SQL,
                [],
            ],
            'filter and second empty' => [
                ['and', 'id=1', ''],
                <<<SQL
                id=1
                SQL,
                [],
            ],
            'filter between null end' => [
                ['between', 'id', 1, null],
                '',
                [],
            ],
            'filter in empty' => [
                ['in', 'id', []],
                '',
                [],
            ],
            'filter like empty' => [
                ['like', 'name', []],
                '',
                [],
            ],
            'filter not between null start' => [
                ['not between', 'id', null, 10],
                '',
                [],
            ],
            'filter not empty' => [
                ['not', ''],
                '',
                [],
            ],
            'filter not in empty' => [
                ['not in', 'id', []],
                '',
                [],
            ],
            'filter not like empty' => [
                ['not like', 'name', []],
                '',
                [],
            ],
            'filter or like empty' => [
                ['or like', 'name', []],
                '',
                [],
            ],
            'filter or nested or with empty' => [
                ['or', 'type=1', ['or', '', 'id=2']],
                <<<SQL
                (type=1) OR (id=2)
                SQL,
                [],
            ],
            'filter or not like empty' => [
                ['or not like', 'name', []],
                '',
                [],
            ],
            'filter or second empty' => [
                ['or', 'id=1', ''],
                <<<SQL
                id=1
                SQL,
                [],
            ],
        ];
    }

    /**
     * @phpstan-return array<string, array{string, string}>
     */
    public static function buildFromDataProvider(): array
    {
        return [
            'alias with as' => [
                'test as t1',
                '[[test]] [[t1]]',
            ],
            'alias with AS' => [
                'test AS t1',
                '[[test]] [[t1]]',
            ],
            'alias with space' => [
                'test t1',
                '[[test]] [[t1]]',
            ],
            'no alias' => [
                'test',
                '[[test]]',
            ],
        ];
    }

    /**
     * @phpstan-return array<string, array{string, Closure}>
     */
    public static function primaryKeysProvider(): array
    {
        $tableName = 'T_constraints_1';
        $name = 'CN_pk';

        return [
            'add' => [
                <<<SQL
                ALTER TABLE {{{$tableName}}} ADD CONSTRAINT [[$name]] PRIMARY KEY ([[C_id_1]])
                SQL,
                static fn(QueryBuilder $qb): string => $qb->addPrimaryKey(
                    $name,
                    $tableName,
                    'C_id_1',
                ),
            ],
            'add (2 columns)' => [
                <<<SQL
                ALTER TABLE {{{$tableName}}} ADD CONSTRAINT [[$name]] PRIMARY KEY ([[C_id_1]], [[C_id_2]])
                SQL,
                static fn(QueryBuilder $qb): string => $qb->addPrimaryKey(
                    $name,
                    $tableName,
                    'C_id_1, C_id_2',
                ),
            ],
            'drop' => [
                <<<SQL
                ALTER TABLE {{{$tableName}}} DROP CONSTRAINT [[$name]]
                SQL,
                static fn(QueryBuilder $qb): string => $qb->dropPrimaryKey(
                    $name,
                    $tableName,
                ),
            ],
        ];
    }

    /**
     * @phpstan-return array<string, array{string, Closure}>
     */
    public static function foreignKeysProvider(): array
    {
        $tableName = 'T_constraints_3';
        $name = 'CN_constraints_3';
        $pkTableName = 'T_constraints_2';

        return [
            'add' => [
                <<<SQL
                ALTER TABLE {{{$tableName}}} ADD CONSTRAINT [[$name]] FOREIGN KEY ([[C_fk_id_1]]) REFERENCES {{{$pkTableName}}} ([[C_id_1]]) ON DELETE CASCADE ON UPDATE CASCADE
                SQL,
                static fn(QueryBuilder $qb): string => $qb->addForeignKey(
                    $name,
                    $tableName,
                    'C_fk_id_1',
                    $pkTableName,
                    'C_id_1',
                    'CASCADE',
                    'CASCADE',
                ),
            ],
            'add (2 columns)' => [
                <<<SQL
                ALTER TABLE {{{$tableName}}} ADD CONSTRAINT [[$name]] FOREIGN KEY ([[C_fk_id_1]], [[C_fk_id_2]]) REFERENCES {{{$pkTableName}}} ([[C_id_1]], [[C_id_2]]) ON DELETE CASCADE ON UPDATE CASCADE
                SQL,
                static fn(QueryBuilder $qb): string => $qb->addForeignKey(
                    $name,
                    $tableName,
                    'C_fk_id_1, C_fk_id_2',
                    $pkTableName,
                    'C_id_1, C_id_2',
                    'CASCADE',
                    'CASCADE',
                ),
            ],
            'drop' => [
                <<<SQL
                ALTER TABLE {{{$tableName}}} DROP CONSTRAINT [[$name]]
                SQL,
                static fn(QueryBuilder $qb): string => $qb->dropForeignKey(
                    $name,
                    $tableName,
                ),
            ],
        ];
    }

    /**
     * @phpstan-return array<string, array{string, Closure}>
     */
    public static function indexesProvider(): array
    {
        $tableName = 'T_constraints_2';
        $name1 = 'CN_constraints_2_single';
        $name2 = 'CN_constraints_2_multi';

        return [
            'create' => [
                <<<SQL
                CREATE INDEX [[$name1]] ON {{{$tableName}}} ([[C_index_1]])
                SQL,
                static fn(QueryBuilder $qb): string => $qb->createIndex(
                    $name1,
                    $tableName,
                    'C_index_1',
                ),
            ],
            'create (2 columns)' => [
                <<<SQL
                CREATE INDEX [[$name2]] ON {{{$tableName}}} ([[C_index_2_1]], [[C_index_2_2]])
                SQL,
                static fn(QueryBuilder $qb): string => $qb->createIndex(
                    $name2,
                    $tableName,
                    'C_index_2_1, C_index_2_2',
                ),
            ],
            'create unique' => [
                <<<SQL
                CREATE UNIQUE INDEX [[$name1]] ON {{{$tableName}}} ([[C_index_1]])
                SQL,
                static fn(QueryBuilder $qb): string => $qb->createIndex(
                    $name1,
                    $tableName,
                    'C_index_1',
                    true,
                ),
            ],
            'create unique (2 columns)' => [
                <<<SQL
                CREATE UNIQUE INDEX [[$name2]] ON {{{$tableName}}} ([[C_index_2_1]], [[C_index_2_2]])
                SQL,
                static fn(QueryBuilder $qb): string => $qb->createIndex(
                    $name2,
                    $tableName,
                    'C_index_2_1, C_index_2_2',
                    true,
                ),
            ],
            'drop' => [
                <<<SQL
                DROP INDEX [[$name1]] ON {{{$tableName}}}
                SQL,
                static fn(QueryBuilder $qb): string => $qb->dropIndex(
                    $name1,
                    $tableName,
                ),
            ],
        ];
    }

    /**
     * @phpstan-return array<string, array{string, Closure}>
     */
    public static function uniquesProvider(): array
    {
        $tableName1 = 'T_constraints_1';
        $name1 = 'CN_unique';
        $tableName2 = 'T_constraints_2';
        $name2 = 'CN_constraints_2_multi';

        return [
            'add' => [
                <<<SQL
                ALTER TABLE {{{$tableName1}}} ADD CONSTRAINT [[$name1]] UNIQUE ([[C_unique]])
                SQL,
                static fn(QueryBuilder $qb): string => $qb->addUnique(
                    $name1,
                    $tableName1,
                    'C_unique',
                ),
            ],
            'add (2 columns)' => [
                <<<SQL
                ALTER TABLE {{{$tableName2}}} ADD CONSTRAINT [[$name2]] UNIQUE ([[C_index_2_1]], [[C_index_2_2]])
                SQL,
                static fn(QueryBuilder $qb): string => $qb->addUnique(
                    $name2,
                    $tableName2,
                    'C_index_2_1, C_index_2_2',
                ),
            ],
            'drop' => [
                <<<SQL
                ALTER TABLE {{{$tableName1}}} DROP CONSTRAINT [[$name1]]
                SQL,
                static fn(QueryBuilder $qb): string => $qb->dropUnique(
                    $name1,
                    $tableName1,
                ),
            ],
        ];
    }

    /**
     * @phpstan-return array<string, array{string, Closure}>
     */
    public static function checksProvider(): array
    {
        $tableName = 'T_constraints_1';
        $name = 'CN_check';

        return [
            'drop' => [
                <<<SQL
                ALTER TABLE {{{$tableName}}} DROP CONSTRAINT [[$name]]
                SQL,
                static fn(QueryBuilder $qb): string => $qb->dropCheck(
                    $name,
                    $tableName,
                ),
            ],
            'add' => [
                <<<SQL
                ALTER TABLE {{{$tableName}}} ADD CONSTRAINT [[$name]] CHECK ([[C_not_null]] > 100)
                SQL,
                static fn(QueryBuilder $qb): string => $qb->addCheck(
                    $name,
                    $tableName,
                    '[[C_not_null]] > 100',
                ),
            ],
        ];
    }

    /**
     * @phpstan-return array<string, array{string, Closure}>
     */
    public static function defaultValuesProvider(): array
    {
        $tableName = 'T_constraints_1';
        $name = 'CN_default';

        return [
            'add' => [
                <<<SQL
                ALTER TABLE {{{$tableName}}} ADD CONSTRAINT [[$name]] DEFAULT 0 FOR [[C_default]]
                SQL,
                static fn(QueryBuilder $qb): string => $qb->addDefaultValue(
                    $name,
                    $tableName,
                    'C_default',
                    0,
                ),
            ],
            'drop' => [
                <<<SQL
                ALTER TABLE {{{$tableName}}} DROP CONSTRAINT [[$name]]
                SQL,
                static fn(QueryBuilder $qb): string => $qb->dropDefaultValue(
                    $name,
                    $tableName,
                ),
            ],
        ];
    }

    /**
     * @phpstan-return array<string, array{0: string, 1: mixed[]|Query, 2: mixed[], 3: string, 4: mixed[], 5?: bool}>
     */
    public static function insertProvider(): array
    {
        return [
            'carry passed params' => [
                'customer',
                [
                    'email' => 'test@example.com',
                    'name' => 'sergeymakinen',
                    'address' => '{{city}}',
                    'is_active' => false,
                    'related_id' => null,
                    'col' => new Expression('CONCAT(:phFoo, :phBar)', [':phFoo' => 'foo']),
                ],
                [':phBar' => 'bar'],
                <<<SQL
                INSERT INTO [[customer]] ([[email]], [[name]], [[address]], [[is_active]], [[related_id]], [[col]]) VALUES (:qp1, :qp2, :qp3, :qp4, :qp5, CONCAT(:phFoo, :phBar))
                SQL,
                [
                    ':phBar' => 'bar',
                    ':qp1' => 'test@example.com',
                    ':qp2' => 'sergeymakinen',
                    ':qp3' => '{{city}}',
                    ':qp4' => false,
                    ':qp5' => null,
                    ':phFoo' => 'foo',
                ],
            ],
            'carry passed params (query)' => [
                'customer',
                (new Query())
                    ->select([
                        'email',
                        'name',
                        'address',
                        'is_active',
                        'related_id',
                    ])
                    ->from('customer')
                    ->where([
                        'email' => 'test@example.com',
                        'name' => 'sergeymakinen',
                        'address' => '{{city}}',
                        'is_active' => false,
                        'related_id' => null,
                        'col' => new Expression('CONCAT(:phFoo, :phBar)', [':phFoo' => 'foo']),
                    ]),
                [':phBar' => 'bar'],
                <<<SQL
                INSERT INTO [[customer]] ([[email]], [[name]], [[address]], [[is_active]], [[related_id]]) SELECT [[email]], [[name]], [[address]], [[is_active]], [[related_id]] FROM [[customer]] WHERE ([[email]]=:qp1) AND ([[name]]=:qp2) AND ([[address]]=:qp3) AND ([[is_active]]=:qp4) AND ([[related_id]] IS NULL) AND ([[col]]=CONCAT(:phFoo, :phBar))
                SQL,
                [
                    ':phBar' => 'bar',
                    ':qp1' => 'test@example.com',
                    ':qp2' => 'sergeymakinen',
                    ':qp3' => '{{city}}',
                    ':qp4' => false,
                    ':phFoo' => 'foo',
                ],
            ],
            'params-and-expressions' => [
                '{{%type}}',
                [
                    '{{%type}}.[[related_id]]' => null,
                    '[[time]]' => new Expression('now()'),
                ],
                [],
                <<<SQL
                INSERT INTO {{%type}} ({{%type}}.[[related_id]], [[time]]) VALUES (:qp0, now())
                SQL,
                [':qp0' => null],
                false,
            ],
            'regular-values' => [
                'customer',
                [
                    'email' => 'test@example.com',
                    'name' => 'silverfire',
                    'address' => 'Kyiv {{city}}, Ukraine',
                    'is_active' => false,
                    'related_id' => null,
                ],
                [],
                <<<SQL
                INSERT INTO [[customer]] ([[email]], [[name]], [[address]], [[is_active]], [[related_id]]) VALUES (:qp0, :qp1, :qp2, :qp3, :qp4)
                SQL,
                [
                    ':qp0' => 'test@example.com',
                    ':qp1' => 'silverfire',
                    ':qp2' => 'Kyiv {{city}}, Ukraine',
                    ':qp3' => false,
                    ':qp4' => null,
                ],
            ],
        ];
    }

    /**
     * @phpstan-return array<string, array{string, mixed[]|Query, mixed[]|bool, string|null, mixed[]}>
     */
    public static function upsertProvider(): array
    {
        return [
            'no columns to update' => [
                'T_upsert_1',
                ['a' => 1],
                true,
                null,
                [':qp0' => 1],
            ],
            'query' => [
                'T_upsert',
                (new Query())
                    ->select(['email', 'status' => new Expression('2')])
                    ->from('customer')
                    ->where(['name' => 'user1'])
                    ->limit(1),
                true,
                null,
                [':qp0' => 'user1'],
            ],
            'query, values and expressions with update part' => [
                '{{%T_upsert}}',
                (new Query())
                    ->select(
                        [
                            'email' => new Expression(':phEmail', [':phEmail' => 'dynamic@example.com']),
                            '[[time]]' => new Expression('now()'),
                        ],
                    ),
                [
                    'ts' => 0,
                    '[[orders]]' => new Expression('T_upsert.orders + 1'),
                ],
                null,
                [
                    ':phEmail' => 'dynamic@example.com',
                    ':qp1' => 0,
                ],
            ],
            'query, values and expressions without update part' => [
                '{{%T_upsert}}',
                (new Query())
                    ->select(
                        [
                            'email' => new Expression(':phEmail', [':phEmail' => 'dynamic@example.com']),
                            '[[time]]' => new Expression('now()'),
                        ],
                    ),
                false,
                null,
                [':phEmail' => 'dynamic@example.com'],
            ],
            'query with update part' => [
                'T_upsert',
                (new Query())
                    ->select(['email', 'status' => new Expression('2')])
                    ->from('customer')
                    ->where(['name' => 'user1'])
                    ->limit(1),
                [
                    'address' => 'foo {{city}}',
                    'status' => 2,
                    'orders' => new Expression('T_upsert.orders + 1'),
                ],
                null,
                [
                    ':qp0' => 'user1',
                    ':qp1' => 'foo {{city}}',
                    ':qp2' => 2,
                ],
            ],
            'query without update part' => [
                'T_upsert',
                (new Query())
                    ->select(['email', 'status' => new Expression('2')])
                    ->from('customer')
                    ->where(['name' => 'user1'])
                    ->limit(1),
                false,
                null,
                [':qp0' => 'user1'],
            ],
            'regular values' => [
                'T_upsert',
                [
                    'email' => 'test@example.com',
                    'address' => 'bar {{city}}',
                    'status' => 1,
                    'profile_id' => null,
                ],
                true,
                null,
                [
                    ':qp0' => 'test@example.com',
                    ':qp1' => 'bar {{city}}',
                    ':qp2' => 1,
                    ':qp3' => null,
                ],
            ],
            'regular values with update part' => [
                'T_upsert',
                [
                    'email' => 'test@example.com',
                    'address' => 'bar {{city}}',
                    'status' => 1,
                    'profile_id' => null,
                ],
                [
                    'address' => 'foo {{city}}',
                    'status' => 2,
                    'orders' => new Expression('T_upsert.orders + 1'),
                ],
                null,
                [
                    ':qp0' => 'test@example.com',
                    ':qp1' => 'bar {{city}}',
                    ':qp2' => 1,
                    ':qp3' => null,
                    ':qp4' => 'foo {{city}}',
                    ':qp5' => 2,
                ],
            ],
            'regular values without update part' => [
                'T_upsert',
                [
                    'email' => 'test@example.com',
                    'address' => 'bar {{city}}',
                    'status' => 1,
                    'profile_id' => null,
                ],
                false,
                null,
                [
                    ':qp0' => 'test@example.com',
                    ':qp1' => 'bar {{city}}',
                    ':qp2' => 1,
                    ':qp3' => null,
                ],
            ],
            'values and expressions' => [
                '{{%T_upsert}}',
                [
                    '{{%T_upsert}}.[[email]]' => 'dynamic@example.com',
                    '[[ts]]' => new Expression('now()'),
                ],
                true,
                null,
                [':qp0' => 'dynamic@example.com'],
            ],
            'values and expressions with update part' => [
                '{{%T_upsert}}',
                [
                    '{{%T_upsert}}.[[email]]' => 'dynamic@example.com',
                    '[[ts]]' => new Expression('now()'),
                ],
                ['[[orders]]' => new Expression('T_upsert.orders + 1')],
                null,
                [':qp0' => 'dynamic@example.com'],
            ],
            'values and expressions without update part' => [
                '{{%T_upsert}}',
                [
                    '{{%T_upsert}}.[[email]]' => 'dynamic@example.com',
                    '[[ts]]' => new Expression('now()'),
                ],
                false,
                null,
                [':qp0' => 'dynamic@example.com'],
            ],
        ];
    }

    /**
     * @phpstan-return array<string, array{0: string, 1: mixed[], 2: mixed[], 3: string, 4?: bool}>
     */
    public static function batchInsertProvider(): array
    {
        return [
            'bool-false, bool2-null' => [
                'type',
                [
                    'bool_col',
                    'bool_col2',
                ],
                [
                    [
                        false,
                        null,
                    ],
                ],
                <<<SQL
                INSERT INTO [[type]] ([[bool_col]], [[bool_col2]]) VALUES (0, NULL)
                SQL,
            ],
            'bool-false, time-now()' => [
                '{{%type}}',
                [
                    '{{%type}}.[[bool_col]]',
                    '[[time]]',
                ],
                [
                    [
                        false,
                        new Expression('now()'),
                    ],
                ],
                <<<SQL
                INSERT INTO {{%type}} ({{%type}}.[[bool_col]], [[time]]) VALUES (0, now())
                SQL,
                false,
            ],
            'empty rows' => [
                'customer',
                ['address'],
                [],
                '',
                false,
            ],
            'escape-danger-chars' => [
                'customer',
                ['address'],
                [["SQL-danger chars are escaped: '); --"]],
                <<<SQL
                INSERT INTO [[customer]] ([[address]]) VALUES ('SQL-danger chars are escaped: \'); --')
                SQL,
            ],
            'float-null, time-now()' => [
                '{{%type}}',
                [
                    '{{%type}}.[[float_col]]',
                    '[[time]]',
                ],
                [
                    [
                        null,
                        new Expression('now()'),
                    ],
                ],
                <<<SQL
                INSERT INTO {{%type}} ({{%type}}.[[float_col]], [[time]]) VALUES (NULL, now())
                SQL,
                false,
            ],
            'no columns' => [
                'customer',
                [],
                [['no columns passed']],
                <<<SQL
                INSERT INTO [[customer]] () VALUES ('no columns passed')
                SQL,
            ],
            'regular values' => [
                'customer',
                [
                    'email',
                    'name',
                    'address',
                ],
                [
                    [
                        'test@example.com',
                        'silverfire',
                        'Kyiv {{city}}, Ukraine',
                    ],
                ],
                <<<SQL
                INSERT INTO [[customer]] ([[email]], [[name]], [[address]]) VALUES ('test@example.com', 'silverfire', 'Kyiv {{city}}, Ukraine')
                SQL,
            ],
        ];
    }

    /**
     * @phpstan-return array<string, array{string, mixed[], mixed[], string, mixed[]}>
     */
    public static function updateProvider(): array
    {
        return [
            'regular values with expression' => [
                'customer',
                [
                    'status' => 1,
                    'updated_at' => new Expression('now()'),
                ],
                ['id' => 100],
                <<<SQL
                UPDATE [[customer]] SET [[status]]=:qp0, [[updated_at]]=now() WHERE [[id]]=:qp1
                SQL,
                [
                    ':qp0' => 1,
                    ':qp1' => 100,
                ],
            ],
        ];
    }

    /**
     * @phpstan-return array<string, array{string, mixed[], string, mixed[]}>
     */
    public static function deleteProvider(): array
    {
        return [
            'with expression value' => [
                'user',
                [
                    'is_enabled' => false,
                    'power' => new Expression('WRONG_POWER()'),
                ],
                <<<SQL
                DELETE FROM [[user]] WHERE ([[is_enabled]]=:qp0) AND ([[power]]=WRONG_POWER())
                SQL,
                [':qp0' => false],
            ],
        ];
    }
}
