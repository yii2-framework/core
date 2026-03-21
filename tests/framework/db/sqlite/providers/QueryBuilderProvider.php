<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\sqlite\providers;

use yii\db\sqlite\QueryBuilder;

/**
 * Data provider for {@see \yiiunit\framework\db\sqlite\QueryBuilderTest} test cases.
 *
 * Provides SQLite-specific input/output pairs for query builder operations.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
final class QueryBuilderProvider extends \yiiunit\base\db\providers\QueryBuilderProvider
{
    public static function indexesProvider(): array
    {
        $result = parent::indexesProvider();

        $result['drop'][0] = <<<SQL
        DROP INDEX [[CN_constraints_2_single]]
        SQL;

        $indexName = 'myindex';
        $schemaName = 'myschema';
        $tableName = 'mytable';

        $result['with schema'] = [
            <<<SQL
            CREATE INDEX {{{$schemaName}}}.[[$indexName]] ON {{{$tableName}}} ([[C_index_1]])
            SQL,
            static fn(QueryBuilder $qb): string => $qb->createIndex(
                $indexName,
                "{$schemaName}.{$tableName}",
                'C_index_1',
            ),
        ];

        return $result;
    }

    public static function batchInsertProvider(): array
    {
        $data = parent::batchInsertProvider();

        $data['escape-danger-chars'][3] = <<<SQL
        INSERT INTO `customer` (`address`) VALUES ('SQL-danger chars are escaped: ''); --')
        SQL;

        return $data;
    }

    public static function upsertProvider(): array
    {
        $concreteData = [
            'no columns to update' => [
                3 => <<<SQL
                INSERT INTO `T_upsert_1` (`a`) VALUES (:qp0) ON CONFLICT DO NOTHING
                SQL,
            ],
            'query' => [
                3 => <<<SQL
                INSERT INTO `T_upsert` (`email`, `status`) SELECT `email`, 2 AS `status` FROM `customer` WHERE `name`=:qp0 LIMIT 1 ON CONFLICT (`email`) DO UPDATE SET `status`=EXCLUDED.`status`
                SQL,
            ],
            'query, values and expressions with update part' => [
                3 => <<<SQL
                INSERT INTO {{%T_upsert}} (`email`, [[time]]) SELECT :phEmail AS `email`, now() AS [[time]] ON CONFLICT (`email`) DO UPDATE SET `ts`=:qp1, [[orders]]=T_upsert.orders + 1
                SQL,
            ],
            'query, values and expressions without update part' => [
                3 => <<<SQL
                INSERT INTO {{%T_upsert}} (`email`, [[time]]) SELECT :phEmail AS `email`, now() AS [[time]] ON CONFLICT DO NOTHING
                SQL,
            ],
            'query with update part' => [
                3 => <<<SQL
                INSERT INTO `T_upsert` (`email`, `status`) SELECT `email`, 2 AS `status` FROM `customer` WHERE `name`=:qp0 LIMIT 1 ON CONFLICT (`email`) DO UPDATE SET `address`=:qp1, `status`=:qp2, `orders`=T_upsert.orders + 1
                SQL,
            ],
            'query without update part' => [
                3 => <<<SQL
                INSERT INTO `T_upsert` (`email`, `status`) SELECT `email`, 2 AS `status` FROM `customer` WHERE `name`=:qp0 LIMIT 1 ON CONFLICT DO NOTHING
                SQL,
            ],
            'regular values' => [
                3 => <<<SQL
                INSERT INTO `T_upsert` (`email`, `address`, `status`, `profile_id`) VALUES (:qp0, :qp1, :qp2, :qp3) ON CONFLICT (`email`) DO UPDATE SET `address`=EXCLUDED.`address`, `status`=EXCLUDED.`status`, `profile_id`=EXCLUDED.`profile_id`
                SQL,
            ],
            'regular values with update part' => [
                3 => <<<SQL
                INSERT INTO `T_upsert` (`email`, `address`, `status`, `profile_id`) VALUES (:qp0, :qp1, :qp2, :qp3) ON CONFLICT (`email`) DO UPDATE SET `address`=:qp4, `status`=:qp5, `orders`=T_upsert.orders + 1
                SQL,
            ],
            'regular values without update part' => [
                3 => <<<SQL
                INSERT INTO `T_upsert` (`email`, `address`, `status`, `profile_id`) VALUES (:qp0, :qp1, :qp2, :qp3) ON CONFLICT DO NOTHING
                SQL,
            ],
            'values and expressions' => [
                3 => <<<SQL
                INSERT INTO {{%T_upsert}} ({{%T_upsert}}.[[email]], [[ts]]) VALUES (:qp0, now())
                SQL,
            ],
            'values and expressions with update part' => [
                3 => <<<SQL
                INSERT INTO {{%T_upsert}} ({{%T_upsert}}.[[email]], [[ts]]) VALUES (:qp0, now())
                SQL,
            ],
            'values and expressions without update part' => [
                3 => <<<SQL
                INSERT INTO {{%T_upsert}} ({{%T_upsert}}.[[email]], [[ts]]) VALUES (:qp0, now())
                SQL,
            ],
        ];

        $newData = parent::upsertProvider();

        foreach ($concreteData as $testName => $data) {
            $newData[$testName] = array_replace($newData[$testName], $data);
        }

        return $newData;
    }
}
