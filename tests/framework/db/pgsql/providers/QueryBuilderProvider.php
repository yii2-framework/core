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
        return [
            ...parent::conditionProvider(),

            // array condition corner cases
            'array && float typed' => [
                [
                    '&&',
                    'price',
                    new ArrayExpression([12, 14], 'float')
                ],
                <<<SQL
                "price" && ARRAY[:qp0, :qp1]::float[]
                SQL,
                [':qp0' => 12, ':qp1' => 14],
            ],
            'array @> empty' => [
                [
                    '@>',
                    'id',
                    new ArrayExpression([]),
                ],
                <<<SQL
                "id" @> ARRAY[]
                SQL,
                [],
            ],
            'array @> expression' => [
                [
                    '@>',
                    'time',
                    new ArrayExpression([new Expression('now()')]),
                ],
                <<<SQL
                [[time]] @> ARRAY[now()]
                SQL,
                [],
            ],
            'array @> multiple' => [
                [
                    '@>',
                    'id',
                    new ArrayExpression([2, 3]),
                ],
                <<<SQL
                "id" @> ARRAY[:qp0, :qp1]
                SQL,
                [
                    ':qp0' => 2,
                    ':qp1' => 3,
                ],
            ],
            'array @> single' => [
                [
                    '@>',
                    'id',
                    new ArrayExpression([1]),
                ],
                <<<SQL
                "id" @> ARRAY[:qp0]
                SQL,
                [':qp0' => 1],
            ],
            'array @> subquery' => [
                [
                    '@>',
                    'id',
                    new ArrayExpression((new Query())->select('id')->from('users')->where(['active' => 1])),
                ],
                <<<SQL
                [[id]] @> ARRAY(SELECT [[id]] FROM [[users]] WHERE [[active]]=:qp0)
                SQL,
                [':qp0' => 1],
            ],
            'array @> typed subquery' => [
                [
                    '@>',
                    'id',
                    new ArrayExpression(
                        [(new Query())->select('id')->from('users')->where(['active' => 1])],
                        'integer',
                    ),
                ],
                <<<SQL
                [[id]] @> ARRAY[ARRAY(SELECT [[id]] FROM [[users]] WHERE [[active]]=:qp0)::integer[]]::integer[]
                SQL,
                [':qp0' => 1],
            ],
            'array can contain nulls' => [
                [
                    '@>',
                    'id',
                    new ArrayExpression([null]),
                ],
                <<<SQL
                "id" @> ARRAY[:qp0]
                SQL,
                [':qp0' => null],
            ],
            'array of arrays' => [
                [
                    '@>',
                    'id',
                    new ArrayExpression([[1,2], [3,4]], 'float', 2),
                ],
                <<<SQL
                "id" @> ARRAY[ARRAY[:qp0, :qp1]::float[], ARRAY[:qp2, :qp3]::float[]\\]::float[][]
                SQL,
                [
                    ':qp0' => 1,
                    ':qp1' => 2,
                    ':qp2' => 3,
                    ':qp3' => 4,
                ],
            ],
            'scalar can not be converted to array #1' => [
                [
                    '@>',
                    'id',
                    new ArrayExpression(1)],
                    <<<SQL
                    "id" @> ARRAY[]
                    SQL,
                    [],
                ],
            'scalar can not be converted to array #2' => [
                [
                    '@>',
                    'id',
                    new ArrayExpression(false),
                ],
                <<<SQL
                "id" @> ARRAY[]
                SQL,
                [],
            ],
            'traversable objects are supported' => [
                [
                    '@>',
                    'id',
                    new ArrayExpression(new TraversableObject([1, 2, 3])),
                ],
                <<<SQL
                [[id]] @> ARRAY[:qp0, :qp1, :qp2]
                SQL,
                [
                    ':qp0' => 1,
                    ':qp1' => 2,
                    ':qp2' => 3,
                ],
            ],

            // json conditions
            '2d array of text' => [
                [
                    '=',
                    'colname',
                    new ArrayExpression([['text1', 'text2'], ['text3', 'text4'], [null, 'text5'],], 'text', 2),
                ],
                <<<SQL
                "colname" = ARRAY[ARRAY[:qp0, :qp1]::text[], ARRAY[:qp2, :qp3]::text[], ARRAY[:qp4, :qp5]::text[]]::text[][]
                SQL,
                [
                    ':qp0' => 'text1',
                    ':qp1' => 'text2',
                    ':qp2' => 'text3',
                    ':qp3' => 'text4',
                    ':qp4' => null,
                    ':qp5' => 'text5',
                ],
            ],
            '3d array of booleans' => [
                [
                    '=',
                    'colname',
                    new ArrayExpression([[[true], [false, null]], [[false], [true], [false]], [['t', 'f']]], 'bool', 3),
                ],
                <<<SQL
                "colname" = ARRAY[ARRAY[ARRAY[:qp0]::bool[], ARRAY[:qp1, :qp2]::bool[]]::bool[][], ARRAY[ARRAY[:qp3]::bool[], ARRAY[:qp4]::bool[], ARRAY[:qp5]::bool[]]::bool[][], ARRAY[ARRAY[:qp6, :qp7]::bool[]]::bool[][]]::bool[][][]
                SQL,
                [
                    ':qp0' => true,
                    ':qp1' => false,
                    ':qp2' => null,
                    ':qp3' => false,
                    ':qp4' => true,
                    ':qp5' => false,
                    ':qp6' => 't',
                    ':qp7' => 'f',
                ],
            ],
            'array of json casted' => [
                [
                    '=',
                    'colname',
                    new ArrayExpression([['a' => null, 'b' => 123, 'c' => [4, 5]], [true]], 'json'),
                ],
                <<<SQL
                "colname" = ARRAY[:qp0, :qp1]::json[]
                SQL,
                [
                    ':qp0' => '{"a":null,"b":123,"c":[4,5]}',
                    ':qp1' => '[true]',
                ],
            ],
            'array of json expressions' => [
                [
                    '=',
                    'colname',
                    new ArrayExpression(
                        [
                            new JsonExpression(['a' => null, 'b' => 123, 'c' => [4, 5]]),
                            new JsonExpression([true]),
                        ],
                    ),
                ],
                <<<SQL
                "colname" = ARRAY[:qp0, :qp1]
                SQL,
                [
                    ':qp0' => '{"a":null,"b":123,"c":[4,5]}',
                    ':qp1' => '[true]',
                ],
            ],
            'json array with false' => [
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
            'json null as array value' => [
                [
                    '=',
                    'jsoncol',
                    new JsonExpression([null]),
                ],
                <<<SQL
                "jsoncol" = :qp0
                SQL,
                [':qp0' => '[null]'],
            ],
            'json null as object value' => [
                [
                    '=',
                    'jsoncol',
                    new JsonExpression(['nil' => null]),
                ],
                <<<SQL
                "jsoncol" = :qp0
                SQL,
                [':qp0' => '{"nil":null}'],
            ],
            'json null value' => [
                [
                    '=',
                    'jsoncol',
                    new JsonExpression(null),
                ],
                <<<SQL
                "jsoncol" = :qp0
                SQL,
                [':qp0' => 'null'],
            ],
            'json object' => [
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
            'json query with type' => [
                [
                    '=',
                    'jsoncol',
                    new JsonExpression((new Query())->select('params')->from('user')->where(['id' => 1]), 'jsonb'),
                ],
                <<<SQL
                [[jsoncol]] = (SELECT [[params]] FROM [[user]] WHERE [[id]]=:qp0)::jsonb
                SQL,
                [':qp0' => 1],
            ],
            'json query' => [
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
            'json with DynamicModel' => [
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
            'json with type cast' => [
                [
                    '=',
                    'prices',
                    new JsonExpression(['seeds' => 15, 'apples' => 25], 'jsonb')],
                    <<<SQL
                    [[prices]] = :qp0::jsonb
                    SQL,
                    [':qp0' => '{"seeds":15,"apples":25}'],
                ],
            'nested json' => [
                [
                    '=',
                    'data',
                    new JsonExpression(
                        [
                            'user' => ['login' => 'silverfire', 'password' => 'c4ny0ur34d17?'],
                            'props' => ['mood' => 'good'],
                        ],
                    ),
                ],
                <<<SQL
                "data" = :qp0
                SQL,
                [':qp0' => '{"user":{"login":"silverfire","password":"c4ny0ur34d17?"},"props":{"mood":"good"}}'],
            ],

            // operator verification
            'operator && array' => [
                [
                    '&&',
                    'id',
                    new ArrayExpression([1]),
                ],
                <<<SQL
                "id" && ARRAY[:qp0]
                SQL,
                [':qp0' => 1],
            ],
            'operator < array' => [
                [
                    '<',
                    'id',
                    new ArrayExpression([1]),
                ],
                <<<SQL
                "id" < ARRAY[:qp0]
                SQL,
                [':qp0' => 1],
            ],
            'operator <= array' => [
                [
                    '<=',
                    'id',
                    new ArrayExpression([1]),
                ],
                <<<SQL
                "id" <= ARRAY[:qp0]
                SQL,
                [':qp0' => 1],
            ],
            'operator <> array' => [
                [
                    '<>',
                    'id',
                    new ArrayExpression([1]),
                ],
                <<<SQL
                "id" <> ARRAY[:qp0]
                SQL,
                [':qp0' => 1],
            ],
            'operator <@' => [
                [
                    '<@',
                    'id',
                    new ArrayExpression([1]),
                ],
                <<<SQL
                "id" <@ ARRAY[:qp0]
                SQL,
                [':qp0' => 1],
            ],
            'operator = array' => [
                [
                    '=',
                    'id',
                    new ArrayExpression([1]),
                ],
                <<<SQL
                "id" = ARRAY[:qp0]
                SQL,
                [':qp0' => 1],
            ],
            'operator > array' => [
                [
                    '>',
                    'id',
                    new ArrayExpression([1]),
                ],
                <<<SQL
                "id" > ARRAY[:qp0]
                SQL,
                [':qp0' => 1],
            ],
            'operator >= array' => [
                [
                    '>=',
                    'id',
                    new ArrayExpression([1]),
                ],
                <<<SQL
                "id" >= ARRAY[:qp0]
                SQL,
                [':qp0' => 1],
            ],
            'operator @>' => [
                [
                    '@>',
                    'id',
                    new ArrayExpression([1]),
                ],
                <<<SQL
                "id" @> ARRAY[:qp0]
                SQL,
                [':qp0' => 1],
            ],
        ];
    }

    /**
     * @phpstan-return array<string, array{string, string, string|Closure, string}>
     */
    public static function alterColumnProvider(): array
    {
        return [
            'DROP DEFAULT' => [
                'foo1',
                'bar',
                'DROP DEFAULT',
                <<<SQL
                ALTER TABLE "foo1" ALTER COLUMN "bar" DROP DEFAULT
                SQL,
            ],
            'reset xyz' => [
                'foo1',
                'bar',
                'reset xyz',
                <<<SQL
                ALTER TABLE "foo1" ALTER COLUMN "bar" reset xyz
                SQL,
            ],
            'SET NOT null' => [
                'foo1',
                'bar',
                'SET NOT null',
                <<<SQL
                ALTER TABLE "foo1" ALTER COLUMN "bar" SET NOT null
                SQL,
            ],
            'string(30) unique' => [
                'foo1',
                'bar',
                static fn($t) => $t->string(30)->unique(),
                <<<SQL
                ALTER TABLE "foo1" ALTER COLUMN "bar" TYPE varchar(30), ALTER COLUMN "bar" DROP DEFAULT, ALTER COLUMN "bar" DROP NOT NULL, ADD UNIQUE ("bar")
                SQL,
            ],
            'string(255)' => [
                'foo1',
                'bar',
                static fn($t) => $t->string(255),
                <<<SQL
                ALTER TABLE "foo1" ALTER COLUMN "bar" TYPE varchar(255), ALTER COLUMN "bar" DROP DEFAULT, ALTER COLUMN "bar" DROP NOT NULL
                SQL,
            ],
            'string(255) check' => [
                'foo1',
                'bar',
                static fn($t) => $t->string(255)->check('char_length(bar) > 5'),
                <<<SQL
                ALTER TABLE "foo1" ALTER COLUMN "bar" TYPE varchar(255), ALTER COLUMN "bar" DROP DEFAULT, ALTER COLUMN "bar" DROP NOT NULL, ADD CONSTRAINT foo1_bar_check CHECK (char_length(bar) > 5)
                SQL,
            ],
            'string(255) default empty' => [
                'foo1',
                'bar',
                static fn($t) => $t->string(255)->defaultValue(''),
                <<<SQL
                ALTER TABLE "foo1" ALTER COLUMN "bar" TYPE varchar(255), ALTER COLUMN "bar" SET DEFAULT '', ALTER COLUMN "bar" DROP NOT NULL
                SQL,
            ],
            'string(255) default value' => [
                'foo1',
                'bar',
                static fn($t) => $t->string(255)->defaultValue('AbCdE'),
                <<<SQL
                ALTER TABLE "foo1" ALTER COLUMN "bar" TYPE varchar(255), ALTER COLUMN "bar" SET DEFAULT 'AbCdE', ALTER COLUMN "bar" DROP NOT NULL
                SQL,
            ],
            'string(255) not null' => [
                'foo1',
                'bar',
                static fn($t) => $t->string(255)->notNull(),
                <<<SQL
                ALTER TABLE "foo1" ALTER COLUMN "bar" TYPE varchar(255), ALTER COLUMN "bar" DROP DEFAULT, ALTER COLUMN "bar" SET NOT NULL
                SQL,
            ],
            'timestamp default expression' => [
                'foo1',
                'bar',
                static fn($t) => $t->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
                <<<SQL
                ALTER TABLE "foo1" ALTER COLUMN "bar" TYPE timestamp(0), ALTER COLUMN "bar" SET DEFAULT CURRENT_TIMESTAMP, ALTER COLUMN "bar" DROP NOT NULL
                SQL,
            ],
            'varchar(255)' => [
                'foo1',
                'bar',
                'varchar(255)',
                <<<SQL
                ALTER TABLE "foo1" ALTER COLUMN "bar" TYPE varchar(255), ALTER COLUMN "bar" DROP DEFAULT, ALTER COLUMN "bar" DROP NOT NULL
                SQL,
            ],
        ];
    }

    public static function indexesProvider(): array
    {
        $result = parent::indexesProvider();

        $result['drop'][0] = <<<SQL
        DROP INDEX [[CN_constraints_2_single]]
        SQL;

        return $result;
    }

    public static function batchInsertProvider(): array
    {
        $data = parent::batchInsertProvider();

        $data['escape-danger-chars'][3] = <<<SQL
        INSERT INTO "customer" ("address") VALUES ('SQL-danger chars are escaped: ''); --')
        SQL;
        $data['bool-false, bool2-null'][3] = <<<SQL
        INSERT INTO "type" ("bool_col", "bool_col2") VALUES (FALSE, NULL)
        SQL;
        $data['bool-false, time-now()'][3] = <<<SQL
        INSERT INTO {{%type}} ({{%type}}.[[bool_col]], [[time]]) VALUES (FALSE, now())
        SQL;

        return $data;
    }

    public static function upsertProvider(): array
    {
        $concreteData = [
            'no columns to update' => [
                3 => <<<SQL
                INSERT INTO "T_upsert_1" ("a") VALUES (:qp0) ON CONFLICT DO NOTHING
                SQL,
            ],
            'query' => [
                3 => <<<SQL
                INSERT INTO "T_upsert" ("email", "status") SELECT "email", 2 AS "status" FROM "customer" WHERE "name"=:qp0 LIMIT 1 ON CONFLICT ("email") DO UPDATE SET "status"=EXCLUDED."status"
                SQL,
            ],
            'query, values and expressions with update part' => [
                3 => <<<SQL
                INSERT INTO {{%T_upsert}} ("email", [[time]]) SELECT :phEmail AS "email", now() AS [[time]] ON CONFLICT ("email") DO UPDATE SET "ts"=:qp1, [[orders]]=T_upsert.orders + 1
                SQL,
            ],
            'query, values and expressions without update part' => [
                3 => <<<SQL
                INSERT INTO {{%T_upsert}} ("email", [[time]]) SELECT :phEmail AS "email", now() AS [[time]] ON CONFLICT DO NOTHING
                SQL,
            ],
            'query with update part' => [
                3 => <<<SQL
                INSERT INTO "T_upsert" ("email", "status") SELECT "email", 2 AS "status" FROM "customer" WHERE "name"=:qp0 LIMIT 1 ON CONFLICT ("email") DO UPDATE SET "address"=:qp1, "status"=:qp2, "orders"=T_upsert.orders + 1
                SQL,
            ],
            'query without update part' => [
                3 => <<<SQL
                INSERT INTO "T_upsert" ("email", "status") SELECT "email", 2 AS "status" FROM "customer" WHERE "name"=:qp0 LIMIT 1 ON CONFLICT DO NOTHING
                SQL,
            ],
            'regular values' => [
                3 => <<<SQL
                INSERT INTO "T_upsert" ("email", "address", "status", "profile_id") VALUES (:qp0, :qp1, :qp2, :qp3) ON CONFLICT ("email") DO UPDATE SET "address"=EXCLUDED."address", "status"=EXCLUDED."status", "profile_id"=EXCLUDED."profile_id"
                SQL,
                4 => [
                    ':qp0' => 'test@example.com',
                    ':qp1' => 'bar {{city}}',
                    ':qp2' => 1,
                    ':qp3' => null,
                ],
            ],
            'regular values with update part' => [
                3 => <<<SQL
                INSERT INTO "T_upsert" ("email", "address", "status", "profile_id") VALUES (:qp0, :qp1, :qp2, :qp3) ON CONFLICT ("email") DO UPDATE SET "address"=:qp4, "status"=:qp5, "orders"=T_upsert.orders + 1
                SQL,
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
                3 => <<<SQL
                INSERT INTO "T_upsert" ("email", "address", "status", "profile_id") VALUES (:qp0, :qp1, :qp2, :qp3) ON CONFLICT DO NOTHING
                SQL,
                4 => [
                    ':qp0' => 'test@example.com',
                    ':qp1' => 'bar {{city}}',
                    ':qp2' => 1,
                    ':qp3' => null,
                ],
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

    public static function updateProvider(): array
    {
        $items = parent::updateProvider();

        $items[] = [
            'profile',
            ['description' => new JsonExpression(['abc' => 'def', 123, null])],
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
