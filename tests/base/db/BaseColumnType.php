<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\base\db;

use Closure;
use yii\db\ColumnSchemaBuilder;
use yii\db\Schema;
use yii\db\SchemaBuilderTrait;
use yiiunit\base\db\providers\ColumnTypeProvider;

use function array_key_exists;

/**
 * Base test for column type mapping across all database drivers.
 *
 * Validates that `QueryBuilder::getColumnType()` and `ColumnSchemaBuilder::__toString()` produce correct SQL for each
 * abstract column type. Uses `SchemaBuilderTrait` to create builder instances with a live database connection.
 *
 * {@see ColumnTypeProvider} for base test case data.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
abstract class BaseColumnType extends BaseDatabase
{
    use SchemaBuilderTrait;

    public function getDb()
    {
        return $this->getConnection(false, false);
    }

    /**
     * Resolves column type entries from a provider: calls builder closures and filters by current driver.
     */
    protected function resolveColumnTypes(array $items): array
    {
        foreach ($items as $i => $item) {
            if ($item[1] instanceof Closure) {
                $items[$i][1] = $item[1]($this);
            }

            if (array_key_exists($this->driverName, $item[2])) {
                $items[$i][2] = $item[2][$this->driverName];
            } else {
                unset($items[$i]);
            }
        }

        return array_values($items);
    }

    /**
     * This is not used as a dataprovider for testGetColumnType to speed up the test
     * when used as dataprovider every single line will cause a reconnect with the database which is not needed here.
     */
    public function columnTypes(): array
    {
        return $this->resolveColumnTypes(ColumnTypeProvider::columnTypes());
    }

    public function testGetColumnType(): void
    {
        $qb = $this->getDb()->getQueryBuilder();

        foreach ($this->columnTypes() as $item) {
            /** @var ColumnSchemaBuilder $builder */
            [$column, $builder, $expected] = $item;

            if (isset($item[3][$this->driverName])) {
                $expectedColumnSchemaBuilder = $item[3][$this->driverName];
            } elseif (isset($item[3]) && !is_array($item[3])) {
                $expectedColumnSchemaBuilder = $item[3];
            } else {
                $expectedColumnSchemaBuilder = $column;
            }

            self::assertSame(
                $expected,
                $qb->getColumnType($column),
                "Column type for '$column' does not match.",
            );
            self::assertSame(
                $expected,
                $qb->getColumnType($builder),
                "Column type for builder of '$column' does not match.",
            );
            self::assertSame(
                $expectedColumnSchemaBuilder,
                $builder->__toString(),
                "ColumnSchemaBuilder string for '$column' does not match.",
            );
        }
    }

    public function testCreateTableColumnTypes(): void
    {
        $qb = $this->getDb()->getQueryBuilder();

        if ($qb->db->getTableSchema('column_type_table', true) !== null) {
            $this->getConnection(false)->createCommand($qb->dropTable('column_type_table'))->execute();
        }

        $columns = [];
        $i = 0;

        foreach ($this->columnTypes() as $item) {
            [$column, $builder, $expected] = $item;

            if (
                !(
                    strncmp($column, Schema::TYPE_PK, 2) === 0 ||
                    strncmp($column, Schema::TYPE_UPK, 3) === 0 ||
                    strncmp($column, Schema::TYPE_BIGPK, 5) === 0 ||
                    strncmp($column, Schema::TYPE_UBIGPK, 6) === 0 ||
                    strncmp(substr($column, -5), 'FIRST', 5) === 0
                )
            ) {
                $columns['col' . ++$i] = str_replace('CHECK (value', 'CHECK ([[col' . $i . ']]', $column);
            }
        }

        $this->getDb()->createCommand($qb->createTable('column_type_table', $columns))->execute();

        self::assertNotEmpty(
            $qb->db->getTableSchema('column_type_table', true),
            'Table column_type_table should exist after CREATE TABLE.',
        );
    }
}
