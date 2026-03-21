<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\oci\providers;

use yii\db\oci\QueryBuilder;

/**
 * Data provider for {@see \yiiunit\framework\db\oci\QueryBuilderTest} test cases.
 *
 * Provides Oracle-specific input/output pairs for query builder operations.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
final class QueryBuilderProvider extends \yiiunit\base\db\providers\QueryBuilderProvider
{
    public static function foreignKeysProvider(): array
    {
        $tableName = 'T_constraints_3';
        $name = 'CN_constraints_3';
        $pkTableName = 'T_constraints_2';

        return [
            'add' => [
                <<<SQL
                ALTER TABLE {{{$tableName}}} ADD CONSTRAINT [[$name]] FOREIGN KEY ([[C_fk_id_1]]) REFERENCES {{{$pkTableName}}} ([[C_id_1]]) ON DELETE CASCADE
                SQL,
                static fn(QueryBuilder $qb): string => $qb->addForeignKey(
                    $name,
                    $tableName,
                    'C_fk_id_1',
                    $pkTableName,
                    'C_id_1',
                    'CASCADE',
                ),
            ],
            'add (2 columns)' => [
                <<<SQL
                ALTER TABLE {{{$tableName}}} ADD CONSTRAINT [[$name]] FOREIGN KEY ([[C_fk_id_1]], [[C_fk_id_2]]) REFERENCES {{{$pkTableName}}} ([[C_id_1]], [[C_id_2]]) ON DELETE CASCADE
                SQL,
                static fn(QueryBuilder $qb): string => $qb->addForeignKey(
                    $name,
                    $tableName,
                    'C_fk_id_1, C_fk_id_2',
                    $pkTableName,
                    'C_id_1, C_id_2',
                    'CASCADE',
                ),
            ],
            'drop' => [
                <<<SQL
                ALTER TABLE {{{$tableName}}} DROP CONSTRAINT [[$name]]
                SQL,
                static fn(QueryBuilder $qb) => $qb->dropForeignKey(
                    $name,
                    $tableName,
                ),
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

    public static function upsertProvider(): array
    {
        $concreteData = [
            'query with update part' => [
                3 => <<<SQL
                MERGE INTO "T_upsert" USING (SELECT "email", 2 AS "status" FROM "customer" WHERE "name"=:qp0 ORDER BY (SELECT NULL) OFFSET 0 ROWS FETCH NEXT 1 ROWS ONLY) "EXCLUDED" ON ("T_upsert"."email"="EXCLUDED"."email") WHEN MATCHED THEN UPDATE SET "address"=:qp1, "status"=:qp2, "orders"=T_upsert.orders + 1 WHEN NOT MATCHED THEN INSERT ("email", "status") VALUES ("EXCLUDED"."email", "EXCLUDED"."status")
                SQL,
            ],
            'query without update part' => [
                3 => <<<SQL
                MERGE INTO "T_upsert" USING (SELECT "email", 2 AS "status" FROM "customer" WHERE "name"=:qp0 ORDER BY (SELECT NULL) OFFSET 0 ROWS FETCH NEXT 1 ROWS ONLY) "EXCLUDED" ON ("T_upsert"."email"="EXCLUDED"."email") WHEN NOT MATCHED THEN INSERT ("email", "status") VALUES ("EXCLUDED"."email", "EXCLUDED"."status")
                SQL,
            ],
            'query' => [
                3 => <<<SQL
                MERGE INTO "T_upsert" USING (SELECT "email", 2 AS "status" FROM "customer" WHERE "name"=:qp0 ORDER BY (SELECT NULL) OFFSET 0 ROWS FETCH NEXT 1 ROWS ONLY) "EXCLUDED" ON ("T_upsert"."email"="EXCLUDED"."email") WHEN MATCHED THEN UPDATE SET "status"="EXCLUDED"."status" WHEN NOT MATCHED THEN INSERT ("email", "status") VALUES ("EXCLUDED"."email", "EXCLUDED"."status")
                SQL,
            ],
            'query, values and expressions with update part' => [
                3 => <<<SQL
                MERGE INTO {{%T_upsert}} USING (SELECT :phEmail AS "email", now() AS [[time]]) "EXCLUDED" ON ({{%T_upsert}}."email"="EXCLUDED"."email") WHEN MATCHED THEN UPDATE SET "ts"=:qp1, [[orders]]=T_upsert.orders + 1 WHEN NOT MATCHED THEN INSERT ("email", [[time]]) VALUES ("EXCLUDED"."email", "EXCLUDED".[[time]])
                SQL,
            ],
            'query, values and expressions without update part' => [
                3 => <<<SQL
                MERGE INTO {{%T_upsert}} USING (SELECT :phEmail AS "email", now() AS [[time]]) "EXCLUDED" ON ({{%T_upsert}}."email"="EXCLUDED"."email") WHEN NOT MATCHED THEN INSERT ("email", [[time]]) VALUES ("EXCLUDED"."email", "EXCLUDED".[[time]])
                SQL,
            ],
            'regular values with update part' => [
                3 => <<<SQL
                MERGE INTO "T_upsert" USING (SELECT :qp0 AS "email", :qp1 AS "address", :qp2 AS "status", :qp3 AS "profile_id" FROM "DUAL") "EXCLUDED" ON ("T_upsert"."email"="EXCLUDED"."email") WHEN MATCHED THEN UPDATE SET "address"=:qp4, "status"=:qp5, "orders"=T_upsert.orders + 1 WHEN NOT MATCHED THEN INSERT ("email", "address", "status", "profile_id") VALUES ("EXCLUDED"."email", "EXCLUDED"."address", "EXCLUDED"."status", "EXCLUDED"."profile_id")
                SQL,
            ],
            'regular values without update part' => [
                3 => <<<SQL
                MERGE INTO "T_upsert" USING (SELECT :qp0 AS "email", :qp1 AS "address", :qp2 AS "status", :qp3 AS "profile_id" FROM "DUAL") "EXCLUDED" ON ("T_upsert"."email"="EXCLUDED"."email") WHEN NOT MATCHED THEN INSERT ("email", "address", "status", "profile_id") VALUES ("EXCLUDED"."email", "EXCLUDED"."address", "EXCLUDED"."status", "EXCLUDED"."profile_id")
                SQL,
            ],
            'regular values' => [
                3 => <<<SQL
                MERGE INTO "T_upsert" USING (SELECT :qp0 AS "email", :qp1 AS "address", :qp2 AS "status", :qp3 AS "profile_id" FROM "DUAL") "EXCLUDED" ON ("T_upsert"."email"="EXCLUDED"."email") WHEN MATCHED THEN UPDATE SET "address"="EXCLUDED"."address", "status"="EXCLUDED"."status", "profile_id"="EXCLUDED"."profile_id" WHEN NOT MATCHED THEN INSERT ("email", "address", "status", "profile_id") VALUES ("EXCLUDED"."email", "EXCLUDED"."address", "EXCLUDED"."status", "EXCLUDED"."profile_id")
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

        // skip test
        unset($newData['no columns to update']);

        return $newData;
    }

    public static function batchInsertProvider(): array
    {
        $data = parent::batchInsertProvider();

        $data['bool-false, bool2-null'][3] = <<<SQL
        INSERT ALL  INTO "type" ("bool_col", "bool_col2") VALUES ('', NULL) SELECT 1 FROM DUAL
        SQL;
        $data['bool-false, time-now()'][3] = <<<SQL
        INSERT ALL  INTO {{%type}} ({{%type}}.[[bool_col]], [[time]]) VALUES (0, now()) SELECT 1 FROM DUAL
        SQL;
        $data['escape-danger-chars'][3] = <<<SQL
        INSERT ALL  INTO "customer" ("address") VALUES ('SQL-danger chars are escaped: ''); --') SELECT 1 FROM DUAL
        SQL;
        $data['float-null, time-now()'][3] = <<<SQL
        INSERT ALL  INTO {{%type}} ({{%type}}.[[float_col]], [[time]]) VALUES (NULL, now()) SELECT 1 FROM DUAL
        SQL;
        $data['no columns'][3] = <<<SQL
        INSERT ALL  INTO "customer" () VALUES ('no columns passed') SELECT 1 FROM DUAL
        SQL;
        $data['regular values'][3] = <<<SQL
        INSERT ALL  INTO "customer" ("email", "name", "address") VALUES ('test@example.com', 'silverfire', 'Kyiv {{city}}, Ukraine') SELECT 1 FROM DUAL
        SQL;

        return $data;
    }
}
