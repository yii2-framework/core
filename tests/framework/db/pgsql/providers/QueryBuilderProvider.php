<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\pgsql\providers;

use yii\base\DynamicModel;
use yii\db\ArrayExpression;
use yii\db\Expression;
use yii\db\JsonExpression;
use yii\db\Query;
use yiiunit\data\base\TraversableObject;

/**
 * Data provider for {@see \yiiunit\framework\db\pgsql\QueryBuilderTest} test cases.
 *
 * Provides PostgreSQL-specific input/output pairs for query builder operations.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
final class QueryBuilderProvider extends \yiiunit\base\db\providers\QueryBuilderProvider
{
    public static function conditionProvider(): array
    {
        return array_merge(
            parent::conditionProvider(),
            [
                // array condition corner cases
                [['@>', 'id', new ArrayExpression([1])], '"id" @> ARRAY[:qp0]', [':qp0' => 1]],
                'scalar can not be converted to array #1' => [['@>', 'id', new ArrayExpression(1)], '"id" @> ARRAY[]', []],
                'scalar can not be converted to array #2' => [['@>', 'id', new ArrayExpression(false)], '"id" @> ARRAY[]', []],
                [['&&', 'price', new ArrayExpression([12, 14], 'float')], '"price" && ARRAY[:qp0, :qp1]::float[]', [':qp0' => 12, ':qp1' => 14]],
                [['@>', 'id', new ArrayExpression([2, 3])], '"id" @> ARRAY[:qp0, :qp1]', [':qp0' => 2, ':qp1' => 3]],
                'array of arrays' => [['@>', 'id', new ArrayExpression([[1,2], [3,4]], 'float', 2)], '"id" @> ARRAY[ARRAY[:qp0, :qp1]::float[], ARRAY[:qp2, :qp3]::float[]\\]::float[][]', [':qp0' => 1, ':qp1' => 2, ':qp2' => 3, ':qp3' => 4]],
                [['@>', 'id', new ArrayExpression([])], '"id" @> ARRAY[]', []],
                'array can contain nulls' => [['@>', 'id', new ArrayExpression([null])], '"id" @> ARRAY[:qp0]', [':qp0' => null]],
                'traversable objects are supported' => [['@>', 'id', new ArrayExpression(new TraversableObject([1, 2, 3]))], '[[id]] @> ARRAY[:qp0, :qp1, :qp2]', [':qp0' => 1, ':qp1' => 2, ':qp2' => 3]],
                [['@>', 'time', new ArrayExpression([new Expression('now()')])], '[[time]] @> ARRAY[now()]', []],
                [['@>', 'id', new ArrayExpression((new Query())->select('id')->from('users')->where(['active' => 1]))], '[[id]] @> ARRAY(SELECT [[id]] FROM [[users]] WHERE [[active]]=:qp0)', [':qp0' => 1]],
                [['@>', 'id', new ArrayExpression([(new Query())->select('id')->from('users')->where(['active' => 1])], 'integer')], '[[id]] @> ARRAY[ARRAY(SELECT [[id]] FROM [[users]] WHERE [[active]]=:qp0)::integer[]]::integer[]', [':qp0' => 1]],

                // json conditions
                [['=', 'jsoncol', new JsonExpression(['lang' => 'uk', 'country' => 'UA'])], '[[jsoncol]] = :qp0', [':qp0' => '{"lang":"uk","country":"UA"}']],
                [['=', 'jsoncol', new JsonExpression([false])], '[[jsoncol]] = :qp0', [':qp0' => '[false]']],
                [['=', 'prices', new JsonExpression(['seeds' => 15, 'apples' => 25], 'jsonb')], '[[prices]] = :qp0::jsonb', [':qp0' => '{"seeds":15,"apples":25}']],
                'nested json' => [
                    ['=', 'data', new JsonExpression(['user' => ['login' => 'silverfire', 'password' => 'c4ny0ur34d17?'], 'props' => ['mood' => 'good']])],
                    '"data" = :qp0', [':qp0' => '{"user":{"login":"silverfire","password":"c4ny0ur34d17?"},"props":{"mood":"good"}}']
                ],
                'null value' => [['=', 'jsoncol', new JsonExpression(null)], '"jsoncol" = :qp0', [':qp0' => 'null']],
                'null as array value' => [['=', 'jsoncol', new JsonExpression([null])], '"jsoncol" = :qp0', [':qp0' => '[null]']],
                'null as object value' => [['=', 'jsoncol', new JsonExpression(['nil' => null])], '"jsoncol" = :qp0', [':qp0' => '{"nil":null}']],

                [['=', 'jsoncol', new JsonExpression(new DynamicModel(['a' => 1, 'b' => 2]))], '[[jsoncol]] = :qp0', [':qp0' => '{"a":1,"b":2}']],
                'query' => [['=', 'jsoncol', new JsonExpression((new Query())->select('params')->from('user')->where(['id' => 1]))], '[[jsoncol]] = (SELECT [[params]] FROM [[user]] WHERE [[id]]=:qp0)', [':qp0' => 1]],
                'query with type' => [['=', 'jsoncol', new JsonExpression((new Query())->select('params')->from('user')->where(['id' => 1]), 'jsonb')], '[[jsoncol]] = (SELECT [[params]] FROM [[user]] WHERE [[id]]=:qp0)::jsonb', [':qp0' => 1]],

                'array of json expressions' => [
                    ['=', 'colname', new ArrayExpression([new JsonExpression(['a' => null, 'b' => 123, 'c' => [4, 5]]), new JsonExpression([true])])],
                    '"colname" = ARRAY[:qp0, :qp1]',
                    [':qp0' => '{"a":null,"b":123,"c":[4,5]}', ':qp1' => '[true]']
                ],
                'Items in ArrayExpression of type json should be casted to Json' => [
                    ['=', 'colname', new ArrayExpression([['a' => null, 'b' => 123, 'c' => [4, 5]], [true]], 'json')],
                    '"colname" = ARRAY[:qp0, :qp1]::json[]',
                    [':qp0' => '{"a":null,"b":123,"c":[4,5]}', ':qp1' => '[true]']
                ],
                'Two dimension array of text' => [
                    ['=', 'colname', new ArrayExpression([['text1', 'text2'], ['text3', 'text4'], [null, 'text5']], 'text', 2)],
                    '"colname" = ARRAY[ARRAY[:qp0, :qp1]::text[], ARRAY[:qp2, :qp3]::text[], ARRAY[:qp4, :qp5]::text[]]::text[][]',
                    [':qp0' => 'text1', ':qp1' => 'text2', ':qp2' => 'text3', ':qp3' => 'text4', ':qp4' => null, ':qp5' => 'text5'],
                ],
                'Three dimension array of booleans' => [
                    ['=', 'colname', new ArrayExpression([[[true], [false, null]], [[false], [true], [false]], [['t', 'f']]], 'bool', 3)],
                    '"colname" = ARRAY[ARRAY[ARRAY[:qp0]::bool[], ARRAY[:qp1, :qp2]::bool[]]::bool[][], ARRAY[ARRAY[:qp3]::bool[], ARRAY[:qp4]::bool[], ARRAY[:qp5]::bool[]]::bool[][], ARRAY[ARRAY[:qp6, :qp7]::bool[]]::bool[][]]::bool[][][]',
                    [':qp0' => true, ':qp1' => false, ':qp2' => null, ':qp3' => false, ':qp4' => true, ':qp5' => false, ':qp6' => 't', ':qp7' => 'f'],
                ],

                // Checks to verity that operators work correctly
                [['@>', 'id', new ArrayExpression([1])], '"id" @> ARRAY[:qp0]', [':qp0' => 1]],
                [['<@', 'id', new ArrayExpression([1])], '"id" <@ ARRAY[:qp0]', [':qp0' => 1]],
                [['=', 'id',  new ArrayExpression([1])], '"id" = ARRAY[:qp0]', [':qp0' => 1]],
                [['<>', 'id', new ArrayExpression([1])], '"id" <> ARRAY[:qp0]', [':qp0' => 1]],
                [['>', 'id',  new ArrayExpression([1])], '"id" > ARRAY[:qp0]', [':qp0' => 1]],
                [['<', 'id',  new ArrayExpression([1])], '"id" < ARRAY[:qp0]', [':qp0' => 1]],
                [['>=', 'id', new ArrayExpression([1])], '"id" >= ARRAY[:qp0]', [':qp0' => 1]],
                [['<=', 'id', new ArrayExpression([1])], '"id" <= ARRAY[:qp0]', [':qp0' => 1]],
                [['&&', 'id', new ArrayExpression([1])], '"id" && ARRAY[:qp0]', [':qp0' => 1]],
            ],
        );
    }

    public static function indexesProvider(): array
    {
        $result = parent::indexesProvider();
        $result['drop'][0] = 'DROP INDEX [[CN_constraints_2_single]]';
        return $result;
    }

    public static function batchInsertProvider(): array
    {
        $data = parent::batchInsertProvider();

        $data['escape-danger-chars'][3] = "INSERT INTO \"customer\" (\"address\") VALUES ('SQL-danger chars are escaped: ''); --')";
        $data['bool-false, bool2-null'][3] = 'INSERT INTO "type" ("bool_col", "bool_col2") VALUES (FALSE, NULL)';
        $data['bool-false, time-now()'][3] = 'INSERT INTO {{%type}} ({{%type}}.[[bool_col]], [[time]]) VALUES (FALSE, now())';

        return $data;
    }

    public static function upsertProvider(): array
    {
        $concreteData = [
            'regular values' => [
                3 => 'INSERT INTO "T_upsert" ("email", "address", "status", "profile_id") VALUES (:qp0, :qp1, :qp2, :qp3) ON CONFLICT ("email") DO UPDATE SET "address"=EXCLUDED."address", "status"=EXCLUDED."status", "profile_id"=EXCLUDED."profile_id"',
                4 => [
                    ':qp0' => 'test@example.com',
                    ':qp1' => 'bar {{city}}',
                    ':qp2' => 1,
                    ':qp3' => null,
                ],
            ],
            'regular values with update part' => [
                3 => 'INSERT INTO "T_upsert" ("email", "address", "status", "profile_id") VALUES (:qp0, :qp1, :qp2, :qp3) ON CONFLICT ("email") DO UPDATE SET "address"=:qp4, "status"=:qp5, "orders"=T_upsert.orders + 1',
                4 => [
                    ':qp0' => 'test@example.com',
                    ':qp1' => 'bar {{city}}',
                    ':qp2' => 1,
                    ':qp3' => null,
                    ':qp4' => 'foo {{city}}',
                    ':qp5' => 2,
                ],
            ],
            'regular values without update part' => [
                3 => 'INSERT INTO "T_upsert" ("email", "address", "status", "profile_id") VALUES (:qp0, :qp1, :qp2, :qp3) ON CONFLICT DO NOTHING',
                4 => [
                    ':qp0' => 'test@example.com',
                    ':qp1' => 'bar {{city}}',
                    ':qp2' => 1,
                    ':qp3' => null,
                ],
            ],
            'query' => [
                3 => 'INSERT INTO "T_upsert" ("email", "status") SELECT "email", 2 AS "status" FROM "customer" WHERE "name"=:qp0 LIMIT 1 ON CONFLICT ("email") DO UPDATE SET "status"=EXCLUDED."status"',
            ],
            'query with update part' => [
                3 => 'INSERT INTO "T_upsert" ("email", "status") SELECT "email", 2 AS "status" FROM "customer" WHERE "name"=:qp0 LIMIT 1 ON CONFLICT ("email") DO UPDATE SET "address"=:qp1, "status"=:qp2, "orders"=T_upsert.orders + 1',
            ],
            'query without update part' => [
                3 => 'INSERT INTO "T_upsert" ("email", "status") SELECT "email", 2 AS "status" FROM "customer" WHERE "name"=:qp0 LIMIT 1 ON CONFLICT DO NOTHING',
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
                3 => 'INSERT INTO {{%T_upsert}} ("email", [[time]]) SELECT :phEmail AS "email", now() AS [[time]] ON CONFLICT ("email") DO UPDATE SET "ts"=:qp1, [[orders]]=T_upsert.orders + 1',
            ],
            'query, values and expressions without update part' => [
                3 => 'INSERT INTO {{%T_upsert}} ("email", [[time]]) SELECT :phEmail AS "email", now() AS [[time]] ON CONFLICT DO NOTHING',
            ],
            'no columns to update' => [
                3 => 'INSERT INTO "T_upsert_1" ("a") VALUES (:qp0) ON CONFLICT DO NOTHING',
            ],
        ];

        $newData = parent::upsertProvider();

        foreach ($concreteData as $testName => $data) {
            $newData[$testName] = array_replace($newData[$testName], $data);
        }

        return $newData;
    }

    public static function updateProvider(): array
    {
        $items = parent::updateProvider();

        $items[] = [
            'profile',
            [
                'description' => new JsonExpression(['abc' => 'def', 123, null]),
            ],
            [
                'id' => 1,
            ],
            'UPDATE [[profile]] SET [[description]]=:qp0 WHERE [[id]]=:qp1',
            [
                ':qp0' => '{"abc":"def","0":123,"1":null}',
                ':qp1' => 1,
            ],
        ];

        return $items;
    }
}
