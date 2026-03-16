<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\oci;

use PDO;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use yii\db\Expression;
use yii\db\PdoValue;
use yii\db\oci\ColumnSchema;
use yii\db\oci\Schema;
use yiiunit\framework\db\oci\providers\ColumnSchemaProvider;

/**
 * Unit tests for {@see ColumnSchema} with Oracle driver.
 *
 * {@see ColumnSchemaProvider} for test case data providers.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
#[Group('db')]
#[Group('oci')]
#[Group('column-schema')]
final class ColumnSchemaTest extends TestCase
{
    #[DataProviderExternal(ColumnSchemaProvider::class, 'dbTypecast')]
    public function testDbTypecast(
        string $type,
        string $dbType,
        mixed $value,
        mixed $expected,
    ): void {
        $column = new ColumnSchema();

        $column->type = $type;
        $column->dbType = $dbType;

        $result = $column->dbTypecast($value);

        if ($expected !== Expression::class) {
            self::assertSame(
                $expected,
                $result,
                'Result does not match expected value.',
            );

            return;
        }

        self::assertInstanceOf(
            Expression::class, $result,
            'Should return an Expression instance.',
        );
        self::assertStringContainsString(
            'TO_BLOB(UTL_RAW.CAST_TO_RAW(',
            $result->expression,
            'Expression SQL should contain TO_BLOB wrapper.',
        );
    }

    public function testDbTypecastBlobPdoValue(): void
    {
        $column = new ColumnSchema();

        $column->type = Schema::TYPE_BINARY;
        $column->dbType = 'BLOB';

        $pdoValue = new PdoValue('binary data', PDO::PARAM_LOB);

        $result = $column->dbTypecast($pdoValue);

        self::assertInstanceOf(
            PdoValue::class,
            $result,
            "PdoValue should pass through to parent 'dbTypecast()'.",
        );
    }

    #[DataProviderExternal(ColumnSchemaProvider::class, 'defaultPhpTypecast')]
    public function testDefaultPhpTypecast(
        string $type,
        string $dbType,
        string $phpType,
        mixed $value,
        mixed $expected,
    ): void {
        $column = new ColumnSchema();

        $column->type = $type;
        $column->dbType = $dbType;
        $column->phpType = $phpType;

        $result = $column->defaultPhpTypecast($value);

        if (!($expected instanceof Expression)) {
            self::assertSame(
                $expected,
                $result,
                'Result does not match expected value.',
            );

            return;
        }

        self::assertInstanceOf(
            Expression::class,
            $result,
            'Should return an Expression instance.',
        );
        self::assertSame(
            $expected->expression,
            $result->expression,
            'Expression SQL does not match.',
        );
    }
}
