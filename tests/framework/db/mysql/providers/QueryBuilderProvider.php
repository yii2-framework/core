<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\mysql\providers;

use yii\base\DynamicModel;
use yii\db\Expression;
use yii\db\JsonExpression;
use yii\db\Query;

/**
 * Data provider for {@see \yiiunit\framework\db\mysql\QueryBuilderTest} test cases.
 *
 * Provides MySQL-specific input/output pairs for query builder operations.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
final class QueryBuilderProvider extends \yiiunit\base\db\providers\QueryBuilderProvider
{
    public static function primaryKeysProvider(): array
    {
        $result = parent::primaryKeysProvider();

        $result['drop'][0] = <<<SQL
        ALTER TABLE {{T_constraints_1}} DROP PRIMARY KEY
        SQL;

        return $result;
    }

    public static function foreignKeysProvider(): array
    {
        $result = parent::foreignKeysProvider();

        $result['drop'][0] = <<<SQL
        ALTER TABLE {{T_constraints_3}} DROP FOREIGN KEY [[CN_constraints_3]]
        SQL;

        return $result;
    }

    public static function indexesProvider(): array
    {
        $result = parent::indexesProvider();

        $result['create'][0] = <<<SQL
        ALTER TABLE {{T_constraints_2}} ADD INDEX [[CN_constraints_2_single]] ([[C_index_1]])
        SQL;
        $result['create (2 columns)'][0] = <<<SQL
        ALTER TABLE {{T_constraints_2}} ADD INDEX [[CN_constraints_2_multi]] ([[C_index_2_1]], [[C_index_2_2]])
        SQL;
        $result['create unique'][0] = <<<SQL
        ALTER TABLE {{T_constraints_2}} ADD UNIQUE INDEX [[CN_constraints_2_single]] ([[C_index_1]])
        SQL;
        $result['create unique (2 columns)'][0] = <<<SQL
        ALTER TABLE {{T_constraints_2}} ADD UNIQUE INDEX [[CN_constraints_2_multi]] ([[C_index_2_1]], [[C_index_2_2]])
        SQL;

        return $result;
    }

    public static function uniquesProvider(): array
    {
        $result = parent::uniquesProvider();

        $result['drop'][0] = <<<SQL
        DROP INDEX [[CN_unique]] ON {{T_constraints_1}}
        SQL;

        return $result;
    }

    public static function upsertProvider(): array
    {
        $concreteData = [
            'no columns to update' => [
                3 => <<<SQL
                INSERT INTO `T_upsert_1` (`a`) VALUES (:qp0) ON DUPLICATE KEY UPDATE `a`=`T_upsert_1`.`a`
                SQL,
            ],
            'query with update part' => [
                3 => <<<SQL
                INSERT INTO `T_upsert` (`email`, `status`) SELECT `email`, 2 AS `status` FROM `customer` WHERE `name`=:qp0 LIMIT 1 ON DUPLICATE KEY UPDATE `address`=:qp1, `status`=:qp2, `orders`=T_upsert.orders + 1
                SQL,
            ],
            'query without update part' => [
                3 => <<<SQL
                INSERT INTO `T_upsert` (`email`, `status`) SELECT `email`, 2 AS `status` FROM `customer` WHERE `name`=:qp0 LIMIT 1 ON DUPLICATE KEY UPDATE `email`=`T_upsert`.`email`
                SQL,
            ],
            'query' => [
                3 => <<<SQL
                INSERT INTO `T_upsert` (`email`, `status`) SELECT `email`, 2 AS `status` FROM `customer` WHERE `name`=:qp0 LIMIT 1 ON DUPLICATE KEY UPDATE `status`=VALUES(`status`)
                SQL,
            ],
            'query, values and expressions with update part' => [
                3 => <<<SQL
                INSERT INTO {{%T_upsert}} (`email`, [[time]]) SELECT :phEmail AS `email`, now() AS [[time]] ON DUPLICATE KEY UPDATE `ts`=:qp1, [[orders]]=T_upsert.orders + 1
                SQL,
            ],
            'query, values and expressions without update part' => [
                3 => <<<SQL
                INSERT INTO {{%T_upsert}} (`email`, [[time]]) SELECT :phEmail AS `email`, now() AS [[time]] ON DUPLICATE KEY UPDATE `email`={{%T_upsert}}.`email`
                SQL,
            ],
            'regular values with update part' => [
                3 => <<<SQL
                INSERT INTO `T_upsert` (`email`, `address`, `status`, `profile_id`) VALUES (:qp0, :qp1, :qp2, :qp3) ON DUPLICATE KEY UPDATE `address`=:qp4, `status`=:qp5, `orders`=T_upsert.orders + 1
                SQL,
            ],
            'regular values without update part' => [
                3 => <<<SQL
                INSERT INTO `T_upsert` (`email`, `address`, `status`, `profile_id`) VALUES (:qp0, :qp1, :qp2, :qp3) ON DUPLICATE KEY UPDATE `email`=`T_upsert`.`email`
                SQL,
            ],
            'regular values' => [
                3 => <<<SQL
                INSERT INTO `T_upsert` (`email`, `address`, `status`, `profile_id`) VALUES (:qp0, :qp1, :qp2, :qp3) ON DUPLICATE KEY UPDATE `address`=VALUES(`address`), `status`=VALUES(`status`), `profile_id`=VALUES(`profile_id`)
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
            'values and expressions' => [
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

    public static function conditionProvider(): array
    {
        return [
            ...parent::conditionProvider(),
            [
                    [
                        '=',
                        'jsoncol',
                        new JsonExpression(['lang' => 'uk', 'country' => 'UA']),
                    ],
                    <<<SQL
                    [[jsoncol]] = :qp0
                    SQL,
                    [':qp0' => '{"lang":"uk","country":"UA"}'],
                ],
                [
                    [
                        '=',
                        'jsoncol',
                        new JsonExpression([false]),
                    ],
                    <<<SQL
                    [[jsoncol]] = :qp0
                    SQL,
                    [':qp0' => '[false]'],
                ],
                'nested json' => [
                    [
                        '=',
                        'data',
                        new JsonExpression(
                            [
                                'user' => [
                                    'login' => 'silverfire',
                                    'password' => 'c4ny0ur34d17?',
                                ],
                                'props' => ['mood' => 'good'],
                            ],
                        ),
                    ],
                    <<<SQL
                    [[data]] = :qp0
                    SQL,
                    [':qp0' => '{"user":{"login":"silverfire","password":"c4ny0ur34d17?"},"props":{"mood":"good"}}'],
                ],
                'null as array value' => [
                    [
                        '=',
                        'jsoncol',
                        new JsonExpression([null]),
                    ],
                    <<<SQL
                    [[jsoncol]] = :qp0
                    SQL,
                    [':qp0' => '[null]'],
                ],
                'null as object value' => [
                    [
                        '=',
                        'jsoncol',
                        new JsonExpression(['nil' => null]),
                    ],
                    <<<SQL
                    [[jsoncol]] = :qp0
                    SQL,
                    [':qp0' => '{"nil":null}'],
                ],
                'null value' => [
                    [
                        '=',
                        'jsoncol',
                        new JsonExpression(null),
                    ],
                    <<<SQL
                    [[jsoncol]] = :qp0
                    SQL,
                    [':qp0' => 'null'],
                ],
                'object with type. Type is ignored for MySQL' => [
                    [
                        '=',
                        'prices',
                        new JsonExpression(['seeds' => 15, 'apples' => 25], 'jsonb'),
                    ],
                    <<<SQL
                    [[prices]] = :qp0
                    SQL,
                    [':qp0' => '{"seeds":15,"apples":25}'],
                ],
                'query' => [
                    [
                        '=',
                        'jsoncol',
                        new JsonExpression((new Query())->select('params')->from('user')->where(['id' => 1])),
                    ],
                    <<<SQL
                    [[jsoncol]] = (SELECT [[params]] FROM [[user]] WHERE [[id]]=:qp0)
                    SQL,
                    [':qp0' => 1],
                ],
                'query with type, that is ignored in MySQL' => [
                    [
                        '=',
                        'jsoncol',
                        new JsonExpression(
                            (new Query())->select('params')->from('user')->where(['id' => 1]), 'jsonb',
                        ),
                    ],
                    <<<SQL
                    [[jsoncol]] = (SELECT [[params]] FROM [[user]] WHERE [[id]]=:qp0)
                    SQL,
                    [':qp0' => 1],
                ],
                'nested and combined json expression' => [
                    [
                        '=',
                        'jsoncol',
                        new JsonExpression(
                            new JsonExpression(['a' => 1, 'b' => 2, 'd' => new JsonExpression(['e' => 3])]),
                        ),
                    ],
                    <<<SQL
                    [[jsoncol]] = :qp0
                    SQL,
                    [':qp0' => '{"a":1,"b":2,"d":{"e":3}}'],
                ],
                'search by property in JSON column (issue #15838)' => [
                    [
                        '=',
                        new Expression("(jsoncol->>'$.someKey')"),
                        '42',
                    ],
                    <<<SQL
                    (jsoncol->>'$.someKey') = :qp0
                    SQL,
                    [':qp0' => '42'],
                ],
                'with object as value' => [
                    [
                        '=',
                        'jsoncol',
                        new JsonExpression(new DynamicModel(['a' => 1, 'b' => 2])),
                    ],
                    <<<SQL
                    [[jsoncol]] = :qp0
                    SQL,
                    [':qp0' => '{"a":1,"b":2}'],
                ],
        ];
    }

    public static function updateProvider(): array
    {
        $items = parent::updateProvider();

        $items[] = [
            'profile',
            [
                'description' => new JsonExpression(['abc' => 'def', 123, null]),
            ],
            ['id' => 1],
            <<<SQL
            UPDATE [[profile]] SET [[description]]=:qp0 WHERE [[id]]=:qp1
            SQL,
            [
                ':qp0' => '{"abc":"def","0":123,"1":null}',
                ':qp1' => 1,
            ],
        ];

        return $items;
    }
}
