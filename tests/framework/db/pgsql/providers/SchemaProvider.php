<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\pgsql\providers;

use yii\db\CheckConstraint;
use yii\db\Constraint;
use yii\db\ForeignKeyConstraint;
use yii\db\IndexConstraint;

/**
 * Data provider for {@see \yiiunit\framework\db\pgsql\SchemaTest} test cases.
 *
 * Provides PostgreSQL-specific input/output pairs for schema constraints.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
final class SchemaProvider extends \yiiunit\base\db\providers\SchemaProvider
{
    /**
     * @phpstan-return array<int, array{array<string, array<string, mixed>>}>
     */
    public static function expectedColumns(): array
    {
        $result = parent::expectedColumns();

        $columns = &$result[0][0];

        unset($columns['enum_col']);

        $columns['bigint_col'] = [
            'type' => 'bigint',
            'dbType' => 'int8',
            'phpType' => 'integer',
            'allowNull' => true,
            'autoIncrement' => false,
            'enumValues' => null,
            'size' => null,
            'precision' => 64,
            'scale' => 0,
            'defaultValue' => null,
        ];
        $columns['bit_col']['dbType'] = 'bit';
        $columns['bit_col']['precision'] = null;
        $columns['bit_col']['size'] = 8;
        $columns['blob_col']['dbType'] = 'bytea';
        $columns['blob_col']['phpType'] = 'resource';
        $columns['blob_col']['type'] = 'binary';
        $columns['bool_col']['dbType'] = 'bool';
        $columns['bool_col']['phpType'] = 'boolean';
        $columns['bool_col']['precision'] = null;
        $columns['bool_col']['scale'] = null;
        $columns['bool_col']['size'] = null;
        $columns['bool_col']['type'] = 'boolean';
        $columns['bool_col2']['dbType'] = 'bool';
        $columns['bool_col2']['defaultValue'] = true;
        $columns['bool_col2']['phpType'] = 'boolean';
        $columns['bool_col2']['precision'] = null;
        $columns['bool_col2']['scale'] = null;
        $columns['bool_col2']['size'] = null;
        $columns['bool_col2']['type'] = 'boolean';
        $columns['char_col']['dbType'] = 'bpchar';
        $columns['char_col']['precision'] = null;
        $columns['char_col2']['dbType'] = 'varchar';
        $columns['char_col2']['precision'] = null;
        $columns['float_col']['dbType'] = 'float8';
        $columns['float_col']['precision'] = 53;
        $columns['float_col']['scale'] = null;
        $columns['float_col']['size'] = null;
        $columns['float_col2']['dbType'] = 'float8';
        $columns['float_col2']['precision'] = 53;
        $columns['float_col2']['scale'] = null;
        $columns['float_col2']['size'] = null;
        $columns['int_col']['dbType'] = 'int4';
        $columns['int_col']['precision'] = 32;
        $columns['int_col']['scale'] = 0;
        $columns['int_col']['size'] = null;
        $columns['int_col2']['dbType'] = 'int4';
        $columns['int_col2']['precision'] = 32;
        $columns['int_col2']['scale'] = 0;
        $columns['int_col2']['size'] = null;
        $columns['intarray_col'] = [
            'type' => 'integer',
            'dbType' => 'int4',
            'phpType' => 'integer',
            'allowNull' => true,
            'autoIncrement' => false,
            'enumValues' => null,
            'size' => null,
            'precision' => null,
            'scale' => null,
            'defaultValue' => null,
            'dimension' => 1,
        ];
        $columns['json_col'] = [
            'type' => 'json',
            'dbType' => 'json',
            'phpType' => 'array',
            'allowNull' => true,
            'autoIncrement' => false,
            'enumValues' => null,
            'size' => null,
            'precision' => null,
            'scale' => null,
            'defaultValue' => ['a' => 1],
            'dimension' => 0,
        ];
        $columns['jsonarray_col'] = [
            'type' => 'json',
            'dbType' => 'json',
            'phpType' => 'array',
            'allowNull' => true,
            'autoIncrement' => false,
            'enumValues' => null,
            'size' => null,
            'precision' => null,
            'scale' => null,
            'defaultValue' => null,
            'dimension' => 1,
        ];
        $columns['jsonb_col'] = [
            'type' => 'json',
            'dbType' => 'jsonb',
            'phpType' => 'array',
            'allowNull' => true,
            'autoIncrement' => false,
            'enumValues' => null,
            'size' => null,
            'precision' => null,
            'scale' => null,
            'defaultValue' => null,
            'dimension' => 0,
        ];
        $columns['numeric_col']['dbType'] = 'numeric';
        $columns['numeric_col']['size'] = null;
        $columns['smallint_col']['dbType'] = 'int2';
        $columns['smallint_col']['precision'] = 16;
        $columns['smallint_col']['scale'] = 0;
        $columns['smallint_col']['size'] = null;
        $columns['textarray2_col'] = [
            'type' => 'text',
            'dbType' => 'text',
            'phpType' => 'string',
            'allowNull' => true,
            'autoIncrement' => false,
            'enumValues' => null,
            'size' => null,
            'precision' => null,
            'scale' => null,
            'defaultValue' => null,
            'dimension' => 2,
        ];
        $columns['tinyint_col']['dbType'] = 'int2';
        $columns['tinyint_col']['precision'] = 16;
        $columns['tinyint_col']['scale'] = 0;
        $columns['tinyint_col']['size'] = null;
        $columns['tinyint_col']['type'] = 'smallint';

        return $result;
    }

    /**
     * @phpstan-return array<
     *   string,
     *   array{
     *     string,
     *     string,
     *     Constraint|Constraint[]|CheckConstraint[]|ForeignKeyConstraint[]|IndexConstraint[]|null,
     * },
     * >
     */
    public static function constraints(): array
    {
        $result = parent::constraints();

        $result['1: check'][2][0]->expression = 'CHECK ((("C_check")::text <> \'\'::text))';
        $result['3: foreign key'][2][0]->foreignSchemaName = 'public';
        $result['3: index'][2] = [];

        return $result;
    }

    /**
     * @phpstan-return array<int, array{int}>
     */
    public static function bigintValue(): array
    {
        return [
            [8817806877],
            [3797444208],
            [3199585540],
            [1389831585],
            [922337203685477580],
            [9223372036854775807],
            [-9223372036854775808],
        ];
    }

    /**
     * @phpstan-return array<string, array{string, string}>
     */
    public static function unquoteSimpleTableName(): array
    {
        return [
            ...parent::unquoteSimpleTableName(),
            'embedded double quote' => ['"a""b"', 'a"b'],
            'multiple embedded double quotes' => ['"a""b""c"', 'a"b"c'],
            'quoted' => ['"myTable"', 'myTable'],
        ];
    }

    /**
     * @phpstan-return array<string, array{string, string}>
     */
    public static function unquoteSimpleColumnName(): array
    {
        return [
            ...parent::unquoteSimpleColumnName(),
            'embedded double quote' => ['"a""b"', 'a"b'],
            'multiple embedded double quotes' => ['"a""b""c"', 'a"b"c'],
            'quoted' => ['"myColumn"', 'myColumn'],
        ];
    }
}
