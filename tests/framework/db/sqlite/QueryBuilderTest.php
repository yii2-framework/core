<?php

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\sqlite;

use Closure;

use yii\base\NotSupportedException;
use yii\db\Schema;
use yii\db\sqlite\QueryBuilder;
use yiiunit\base\db\BaseQueryBuilder;

/**
 * @group db
 * @group sqlite
 */
class QueryBuilderTest extends BaseQueryBuilder
{
    protected $driverName = 'sqlite';

    protected $likeEscapeCharSql = " ESCAPE '\\'";

    public function columnTypes()
    {
        return array_merge(parent::columnTypes(), [
            [
                Schema::TYPE_PK,
                $this->primaryKey()->first()->after('col_before'),
                'integer PRIMARY KEY AUTOINCREMENT NOT NULL',
            ],
        ]);
    }


    public static function indexesProvider(): array
    {
        $result = parent::indexesProvider();
        $result['drop'][0] = 'DROP INDEX [[CN_constraints_2_single]]';

        $indexName = 'myindex';
        $schemaName = 'myschema';
        $tableName = 'mytable';

        $result['with schema'] = [
            "CREATE INDEX {{{$schemaName}}}.[[$indexName]] ON {{{$tableName}}} ([[C_index_1]])",
            function (QueryBuilder $qb) use ($tableName, $indexName, $schemaName) {
                return $qb->createIndex($indexName, $schemaName . '.' . $tableName, 'C_index_1');
            },
        ];

        return $result;
    }

    public function testCommentColumn(): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'yii\db\sqlite\QueryBuilder::addCommentOnColumn is not supported by SQLite.',
        );

        parent::testCommentColumn();
    }

    public function testCommentTable(): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'yii\db\sqlite\QueryBuilder::addCommentOnTable is not supported by SQLite.',
        );

        parent::testCommentTable();
    }

    public static function batchInsertProvider(): array
    {
        $data = parent::batchInsertProvider();
        $data['escape-danger-chars']['expected'] = "INSERT INTO `customer` (`address`) VALUES ('SQL-danger chars are escaped: ''); --')";
        return $data;
    }

    public function testRenameTable(): void
    {
        $sql = $this->getQueryBuilder()->renameTable('table_from', 'table_to');
        $this->assertEquals('ALTER TABLE `table_from` RENAME TO `table_to`', $sql);
    }

    public function testResetSequence(): void
    {
        $qb = $this->getQueryBuilder(true, true);

        $expected = "UPDATE sqlite_sequence SET seq='5' WHERE name='item'";
        $sql = $qb->resetSequence('item');
        $this->assertEquals($expected, $sql);

        $expected = "UPDATE sqlite_sequence SET seq='3' WHERE name='item'";
        $sql = $qb->resetSequence('item', 4);
        $this->assertEquals($expected, $sql);
    }

    public static function upsertProvider(): array
    {
        $concreteData = [
            'regular values' => [
                3 => 'INSERT INTO `T_upsert` (`email`, `address`, `status`, `profile_id`) VALUES (:qp0, :qp1, :qp2, :qp3) ON CONFLICT (`email`) DO UPDATE SET `address`=EXCLUDED.`address`, `status`=EXCLUDED.`status`, `profile_id`=EXCLUDED.`profile_id`',
            ],
            'regular values with update part' => [
                3 => 'INSERT INTO `T_upsert` (`email`, `address`, `status`, `profile_id`) VALUES (:qp0, :qp1, :qp2, :qp3) ON CONFLICT (`email`) DO UPDATE SET `address`=:qp4, `status`=:qp5, `orders`=T_upsert.orders + 1',
            ],
            'regular values without update part' => [
                3 => 'INSERT INTO `T_upsert` (`email`, `address`, `status`, `profile_id`) VALUES (:qp0, :qp1, :qp2, :qp3) ON CONFLICT DO NOTHING',
            ],
            'query' => [
                3 => 'INSERT INTO `T_upsert` (`email`, `status`) SELECT `email`, 2 AS `status` FROM `customer` WHERE `name`=:qp0 LIMIT 1 ON CONFLICT (`email`) DO UPDATE SET `status`=EXCLUDED.`status`',
            ],
            'query with update part' => [
                3 => 'INSERT INTO `T_upsert` (`email`, `status`) SELECT `email`, 2 AS `status` FROM `customer` WHERE `name`=:qp0 LIMIT 1 ON CONFLICT (`email`) DO UPDATE SET `address`=:qp1, `status`=:qp2, `orders`=T_upsert.orders + 1',
            ],
            'query without update part' => [
                3 => 'INSERT INTO `T_upsert` (`email`, `status`) SELECT `email`, 2 AS `status` FROM `customer` WHERE `name`=:qp0 LIMIT 1 ON CONFLICT DO NOTHING',
            ],
            'values and expressions' => [
                3 => 'INSERT INTO {{%T_upsert}} ({{%T_upsert}}.[[email]], [[ts]]) VALUES (:qp0, now())',
            ],
            'values and expressions with update part' => [
                3 => 'INSERT INTO {{%T_upsert}} ({{%T_upsert}}.[[email]], [[ts]]) VALUES (:qp0, now())',
            ],
            'values and expressions without update part' => [
                3 => 'INSERT INTO {{%T_upsert}} ({{%T_upsert}}.[[email]], [[ts]]) VALUES (:qp0, now())',
            ],
            'query, values and expressions with update part' => [
                3 => 'INSERT INTO {{%T_upsert}} (`email`, [[time]]) SELECT :phEmail AS `email`, now() AS [[time]] ON CONFLICT (`email`) DO UPDATE SET `ts`=:qp1, [[orders]]=T_upsert.orders + 1',
            ],
            'query, values and expressions without update part' => [
                3 => 'INSERT INTO {{%T_upsert}} (`email`, [[time]]) SELECT :phEmail AS `email`, now() AS [[time]] ON CONFLICT DO NOTHING',
            ],
            'no columns to update' => [
                3 => 'INSERT INTO `T_upsert_1` (`a`) VALUES (:qp0) ON CONFLICT DO NOTHING',
            ],
        ];
        $newData = parent::upsertProvider();
        foreach ($concreteData as $testName => $data) {
            $newData[$testName] = array_replace($newData[$testName], $data);
        }
        return $newData;
    }

    /**
     * @dataProvider primaryKeysProvider
     * @param string $sql
     */
    public function testAddDropPrimaryKey($sql, Closure $builder): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessageMatches(
            '/^.*::(addPrimaryKey|dropPrimaryKey) is not supported by SQLite\.$/',
        );

        parent::testAddDropPrimaryKey($sql, $builder);
    }

    /**
     * @dataProvider foreignKeysProvider
     * @param string $sql
     */
    public function testAddDropForeignKey($sql, Closure $builder): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessageMatches(
            '/^.*::(addForeignKey|dropForeignKey) is not supported by SQLite\.$/',
        );

        parent::testAddDropForeignKey($sql, $builder);
    }

    /**
     * @dataProvider uniquesProvider
     * @param string $sql
     */
    public function testAddDropUnique($sql, Closure $builder): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessageMatches(
            '/^.*::(addUnique|dropUnique) is not supported by SQLite\.$/',
        );

        parent::testAddDropUnique($sql, $builder);
    }

    /**
     * @dataProvider checksProvider
     * @param string $sql
     */
    public function testAddDropCheck($sql, Closure $builder): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessageMatches(
            '/^.*::(addCheck|dropCheck) is not supported by SQLite\.$/',
        );

        parent::testAddDropCheck($sql, $builder);
    }

    /**
     * @dataProvider defaultValuesProvider
     * @param string $sql
     */
    public function testAddDropDefaultValue($sql, Closure $builder): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessageMatches(
            '/^.*::(addDefaultValue|dropDefaultValue) is not supported by SQLite\.$/',
        );

        parent::testAddDropDefaultValue($sql, $builder);
    }
}
