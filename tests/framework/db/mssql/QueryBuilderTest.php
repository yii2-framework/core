<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\mssql;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\Group;
use yii\db\Expression;
use yii\db\IntegrityException;
use yii\db\Query;
use yiiunit\base\db\BaseQueryBuilder;
use yiiunit\framework\db\mssql\providers\QueryBuilderProvider;

/**
 * Unit test for {@see \yii\db\mssql\QueryBuilder} with MSSQL driver.
 *
 * {@see QueryBuilderProvider} for test case data providers.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
#[Group('db')]
#[Group('mssql')]
#[Group('query-builder')]
final class QueryBuilderTest extends BaseQueryBuilder
{
    protected $driverName = 'sqlsrv';

    public function testOffsetLimit(): void
    {
        $db = $this->getDb();

        $expectedQueryParams = [];

        $query = new Query();

        $query->select('id')->from('example')->limit(10)->offset(5);

        [$actualQuerySql, $actualQueryParams] = $db->queryBuilder->build($query);

        self::assertSame(
            <<<SQL
            SELECT [id] FROM [example] ORDER BY (SELECT NULL) OFFSET 5 ROWS FETCH NEXT 10 ROWS ONLY
            SQL,
            $actualQuerySql,
            "OFFSET with LIMIT should use OFFSET '5' ROWS FETCH NEXT '10' ROWS ONLY with ORDER BY (SELECT NULL).",
        );
        self::assertSame(
            $expectedQueryParams,
            $actualQueryParams,
            'OFFSET/LIMIT query should not bind any parameters.',
        );
    }

    public function testLimit(): void
    {
        $db = $this->getDb();

        $query = new Query();

        $query->select('id')->from('example')->limit(10);

        [$actualQuerySql, $actualQueryParams] = $db->queryBuilder->build($query);

        self::assertSame(
            <<<SQL
            SELECT [id] FROM [example] ORDER BY (SELECT NULL) OFFSET 0 ROWS FETCH NEXT 10 ROWS ONLY
            SQL,
            $actualQuerySql,
            "LIMIT without OFFSET should use OFFSET '0' ROWS FETCH NEXT '10' ROWS ONLY.",
        );
        self::assertSame(
            [],
            $actualQueryParams,
            'LIMIT query should not bind any parameters.',
        );
    }

    public function testOffset(): void
    {
        $db = $this->getDb();

        $query = new Query();

        $query->select('id')->from('example')->offset(10);

        [$actualQuerySql, $actualQueryParams] = $db->queryBuilder->build($query);

        self::assertSame(
            <<<SQL
            SELECT [id] FROM [example] ORDER BY (SELECT NULL) OFFSET 10 ROWS
            SQL,
            $actualQuerySql,
            "OFFSET without LIMIT should use OFFSET '10' ROWS without FETCH clause.",
        );
        self::assertSame(
            [],
            $actualQueryParams,
            'OFFSET query should not bind any parameters.',
        );
    }

    protected function getCommentsFromTable($table)
    {
        $db = $this->getDb();

        $quotedTable = $db->quoteValue($table);

        $sql = <<<SQL
        SELECT
            'TABLE' AS [objtype],
            OBJECT_NAME([ep].[major_id]) AS [objname],
            [ep].[name],
            CAST([ep].[value] AS NVARCHAR(MAX)) AS [value]
        FROM [sys].[extended_properties] AS [ep]
        WHERE [ep].[major_id] = OBJECT_ID(N'dbo.' + N{$quotedTable})
            AND [ep].[minor_id] = 0
            AND [ep].[class] = 1
            AND [ep].[name] = N'MS_Description'
        SQL;

        return $db->createCommand($sql)->queryAll();
    }

    protected function getCommentsFromColumn($table, $column)
    {
        $db = $this->getDb();

        $quotedTable = $db->quoteValue($table);
        $quotedColumn = $db->quoteValue($column);

        $sql = <<<SQL
        SELECT
            'COLUMN' AS [objtype],
            [c].[name] AS [objname],
            [ep].[name],
            CAST([ep].[value] AS NVARCHAR(MAX)) AS [value]
        FROM [sys].[extended_properties] AS [ep]
        INNER JOIN [sys].[columns] AS [c]
            ON [c].[object_id] = [ep].[major_id]
            AND [c].[column_id] = [ep].[minor_id]
        WHERE [ep].[major_id] = OBJECT_ID(N'dbo.' + N{$quotedTable})
            AND [ep].[minor_id] = COLUMNPROPERTY(OBJECT_ID(N'dbo.' + N{$quotedTable}), N{$quotedColumn}, 'ColumnId')
            AND [ep].[class] = 1
            AND [ep].[name] = N'MS_Description'
        SQL;

        return $db->createCommand($sql)->queryAll();
    }

    protected function runAddCommentOnTable($comment, $table)
    {
        $db = $this->getDb();

        $sql = $db->queryBuilder->addCommentOnTable($table, $comment);

        return $db->createCommand($sql)->execute();
    }

    protected function runAddCommentOnColumn($comment, $table, $column)
    {
        $db = $this->getDb();

        $sql = $db->queryBuilder->addCommentOnColumn($table, $column, $comment);

        return $db->createCommand($sql)->execute();
    }

    protected function runDropCommentFromTable($table)
    {
        $db = $this->getDb();

        $sql = $db->queryBuilder->dropCommentFromTable($table);

        return $db->createCommand($sql)->execute();
    }

    protected function runDropCommentFromColumn($table, $column)
    {
        $db = $this->getDb();

        $sql = $db->queryBuilder->dropCommentFromColumn($table, $column);

        return $db->createCommand($sql)->execute();
    }

    public function testCommentAdditionOnTableAndOnColumn(): void
    {
        $table = 'profile';
        $tableComment = 'A comment for profile table.';

        $this->runAddCommentOnTable($tableComment, $table);

        $resultTable = $this->getCommentsFromTable($table);

        self::assertSame(
            [
                'objtype' => 'TABLE',
                'objname' => $table,
                'name' => 'MS_Description',
                'value' => $tableComment,
            ],
            $resultTable[0],
            "Adding a comment on a table should store it as an extended property via 'sys.extended_properties'.",
        );

        $column = 'description';
        $columnComment = 'A comment for description column in profile table.';

        $this->runAddCommentOnColumn($columnComment, $table, $column);

        $resultColumn = $this->getCommentsFromColumn($table, $column);

        self::assertSame(
            [
                'objtype' => 'COLUMN',
                'objname' => $column,
                'name' => 'MS_Description',
                'value' => $columnComment,
            ],
            $resultColumn[0],
            "Adding a comment on a column should store it as an extended property via 'sys.extended_properties'.",
        );

        $tableComment2 = 'Another comment for profile table.';

        $this->runAddCommentOnTable($tableComment2, $table);

        $result = $this->getCommentsFromTable($table);

        self::assertSame(
            [
                'objtype' => 'TABLE',
                'objname' => $table,
                'name' => 'MS_Description',
                'value' => $tableComment2,
            ],
            $result[0],
            'Updating an existing table comment should replace the previous extended property value.',
        );

        $columnComment2 = 'Another comment for description column in profile table.';

        $this->runAddCommentOnColumn($columnComment2, $table, $column);

        $result = $this->getCommentsFromColumn($table, $column);

        self::assertSame(
            [
                'objtype' => 'COLUMN',
                'objname' => $column,
                'name' => 'MS_Description',
                'value' => $columnComment2,
            ],
            $result[0],
            'Updating an existing column comment should replace the previous extended property value.',
        );
    }

    public function testCommentAdditionOnQuotedTableOrColumn(): void
    {
        $table = 'stranger \'table';
        $tableComment = 'A comment for stranger \'table.';

        $this->runAddCommentOnTable($tableComment, $table);

        $resultTable = $this->getCommentsFromTable($table);

        self::assertSame(
            [
                'objtype' => 'TABLE',
                'objname' => $table,
                'name' => 'MS_Description',
                'value' => $tableComment,
            ],
            $resultTable[0],
            'Adding a comment on a table with special characters should store it correctly.',
        );

        $column = 'stranger \'field';
        $columnComment = 'A comment for stranger \'field column in stranger \'table.';

        $this->runAddCommentOnColumn($columnComment, $table, $column);

        $resultColumn = $this->getCommentsFromColumn($table, $column);

        self::assertSame(
            [
                'objtype' => 'COLUMN',
                'objname' => $column,
                'name' => 'MS_Description',
                'value' => $columnComment,
            ],
            $resultColumn[0],
            'Adding a comment on a column with special characters should store it correctly.',
        );
    }

    public function testCommentRemovalFromTableAndFromColumn(): void
    {
        $table = 'profile';

        $this->runAddCommentOnTable('A comment for profile table.', $table);
        $this->runDropCommentFromTable($table);

        self::assertEmpty(
            $this->getCommentsFromTable($table),
            'Dropping a table comment should remove the extended property completely.',
        );

        $column = 'description';

        $this->runAddCommentOnColumn('A comment for description column in profile table.', $table, $column);
        $this->runDropCommentFromColumn($table, $column);

        self::assertEmpty(
            $this->getCommentsFromColumn($table, $column),
            'Dropping a column comment should remove the extended property completely.',
        );
    }

    public function testCommentRemovalFromQuotedTableOrColumn(): void
    {
        $table = 'stranger \'table';

        $this->runAddCommentOnTable('A comment for stranger \'table.', $table);
        $this->runDropCommentFromTable($table);

        self::assertEmpty(
            $this->getCommentsFromTable($table),
            'Dropping a table comment with special characters should remove the extended property completely.',
        );

        $column = 'stranger \'field';

        $this->runAddCommentOnColumn('A comment for stranger \'field in stranger \'table.', $table, $column);
        $this->runDropCommentFromColumn($table, $column);

        self::assertEmpty(
            $this->getCommentsFromColumn($table, $column),
            'Dropping a column comment with special characters should remove the extended property completely.',
        );
    }

    public function testCommentColumn(): void
    {
        $this->markTestSkipped('Testing the behavior, not sql generation anymore.');
    }

    public function testCommentTable(): void
    {
        $this->markTestSkipped('Testing the behavior, not sql generation anymore.');
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'batchInsertProvider')]
    public function testBatchInsert($table, $columns, $value, $expected, $replaceQuotes = true): void
    {
        parent::testBatchInsert($table, $columns, $value, $expected, $replaceQuotes);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'insertProvider')]
    public function testInsert($table, $columns, $params, $expectedSQL, $expectedParams, $replaceQuotes = true): void
    {
        parent::testInsert($table, $columns, $params, $expectedSQL, $expectedParams, $replaceQuotes);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'buildFromDataProvider')]
    public function testBuildFrom($table, $expected): void
    {
        parent::testBuildFrom($table, $expected);
    }

    public function testResetSequence(): void
    {
        $qb = $this->getDb()->queryBuilder;

        self::assertSame(
            "DBCC CHECKIDENT ('[item]', RESEED, 5)",
            $qb->resetSequence('item'),
            'RESEED without explicit value should use the current max primary key value.',
        );
        self::assertSame(
            "DBCC CHECKIDENT ('[item]', RESEED, 4)",
            $qb->resetSequence('item', 4),
            'RESEED with explicit value should use the provided value directly.',
        );
    }

    #[Depends('testInitFixtures')]
    #[DataProviderExternal(QueryBuilderProvider::class, 'upsertProvider')]
    public function testUpsert($table, $insertColumns, $updateColumns, $expectedSQL, $expectedParams): void
    {
        parent::testUpsert($table, $insertColumns, $updateColumns, $expectedSQL, $expectedParams);
    }

    public function testAlterColumn(): void
    {
        $qb = $this->getDb()->queryBuilder;

        $expected = <<<SQL
        ALTER TABLE {{foo1}} ALTER COLUMN [[bar]] varchar(255)
        DECLARE @tableName NVARCHAR(MAX) = N'[foo1]'
        DECLARE @columnName NVARCHAR(MAX) = N'bar'

        WHILE 1=1 BEGIN
            DECLARE @constraintName NVARCHAR(128)
            SET @constraintName = (SELECT TOP 1 OBJECT_NAME([cons].[object_id])
                FROM (
                    SELECT [dc].[object_id]
                    FROM [sys].[default_constraints] AS [dc]
                    INNER JOIN [sys].[columns] AS [c]
                        ON [c].[object_id] = [dc].[parent_object_id]
                        AND [c].[column_id] = [dc].[parent_column_id]
                        AND [c].[name] = @columnName
                    WHERE [dc].[parent_object_id] = OBJECT_ID(@tableName)
                    UNION
                    SELECT [cc].[object_id]
                    FROM [sys].[check_constraints] AS [cc]
                    INNER JOIN [sys].[columns] AS [c]
                        ON [c].[object_id] = [cc].[parent_object_id]
                        AND (
                            [c].[column_id] = [cc].[parent_column_id]
                            OR (
                                [cc].[parent_column_id] = 0
                                AND CHARINDEX(QUOTENAME(@columnName), [cc].[definition]) > 0
                            )
                        )
                        AND [c].[name] = @columnName
                    WHERE [cc].[parent_object_id] = OBJECT_ID(@tableName)
                    UNION
                    SELECT OBJECT_ID([i].[name])
                    FROM [sys].[indexes] AS [i]
                    INNER JOIN [sys].[columns] AS [c]
                        ON [c].[object_id] = [i].[object_id]
                        AND [c].[name] = @columnName
                    INNER JOIN [sys].[index_columns] AS [ic]
                        ON [ic].[object_id] = [i].[object_id]
                        AND [i].[index_id] = [ic].[index_id]
                        AND [c].[column_id] = [ic].[column_id]
                    WHERE [i].[is_unique_constraint] = 1
                        AND [i].[object_id] = OBJECT_ID(@tableName)
                ) AS [cons]
                INNER JOIN [sys].[objects] AS [so] ON [so].[object_id] = [cons].[object_id]
            WHERE [so].[type] = N'D')
            IF @constraintName IS NULL BREAK
            EXEC (N'ALTER TABLE ' + @tableName + N' DROP CONSTRAINT [' + @constraintName + N']')
        END
        SQL;
        self::assertSame(
            $expected,
            $qb->alterColumn('foo1', 'bar', 'varchar(255)'),
            "ALTER COLUMN to 'varchar(255)' should drop default constraints and alter the column type.",
        );

        $expected = <<<SQL
        ALTER TABLE {{foo1}} ALTER COLUMN [[bar]] nvarchar(255) NOT NULL
        DECLARE @tableName NVARCHAR(MAX) = N'[foo1]'
        DECLARE @columnName NVARCHAR(MAX) = N'bar'

        WHILE 1=1 BEGIN
            DECLARE @constraintName NVARCHAR(128)
            SET @constraintName = (SELECT TOP 1 OBJECT_NAME([cons].[object_id])
                FROM (
                    SELECT [dc].[object_id]
                    FROM [sys].[default_constraints] AS [dc]
                    INNER JOIN [sys].[columns] AS [c]
                        ON [c].[object_id] = [dc].[parent_object_id]
                        AND [c].[column_id] = [dc].[parent_column_id]
                        AND [c].[name] = @columnName
                    WHERE [dc].[parent_object_id] = OBJECT_ID(@tableName)
                    UNION
                    SELECT [cc].[object_id]
                    FROM [sys].[check_constraints] AS [cc]
                    INNER JOIN [sys].[columns] AS [c]
                        ON [c].[object_id] = [cc].[parent_object_id]
                        AND (
                            [c].[column_id] = [cc].[parent_column_id]
                            OR (
                                [cc].[parent_column_id] = 0
                                AND CHARINDEX(QUOTENAME(@columnName), [cc].[definition]) > 0
                            )
                        )
                        AND [c].[name] = @columnName
                    WHERE [cc].[parent_object_id] = OBJECT_ID(@tableName)
                    UNION
                    SELECT OBJECT_ID([i].[name])
                    FROM [sys].[indexes] AS [i]
                    INNER JOIN [sys].[columns] AS [c]
                        ON [c].[object_id] = [i].[object_id]
                        AND [c].[name] = @columnName
                    INNER JOIN [sys].[index_columns] AS [ic]
                        ON [ic].[object_id] = [i].[object_id]
                        AND [i].[index_id] = [ic].[index_id]
                        AND [c].[column_id] = [ic].[column_id]
                    WHERE [i].[is_unique_constraint] = 1
                        AND [i].[object_id] = OBJECT_ID(@tableName)
                ) AS [cons]
                INNER JOIN [sys].[objects] AS [so] ON [so].[object_id] = [cons].[object_id]
            WHERE [so].[type] = N'D')
            IF @constraintName IS NULL BREAK
            EXEC (N'ALTER TABLE ' + @tableName + N' DROP CONSTRAINT [' + @constraintName + N']')
        END
        SQL;

        self::assertSame(
            $expected,
            $qb->alterColumn('foo1', 'bar', $this->string(255)->notNull()),
            "ALTER COLUMN to 'nvarchar(255)' NOT NULL should drop default constraints and alter the column type.",
        );

        $expected = <<<SQL
        ALTER TABLE {{foo1}} ALTER COLUMN [[bar]] nvarchar(255)
        DECLARE @tableName NVARCHAR(MAX) = N'[foo1]'
        DECLARE @columnName NVARCHAR(MAX) = N'bar'

        WHILE 1=1 BEGIN
            DECLARE @constraintName NVARCHAR(128)
            SET @constraintName = (SELECT TOP 1 OBJECT_NAME([cons].[object_id])
                FROM (
                    SELECT [dc].[object_id]
                    FROM [sys].[default_constraints] AS [dc]
                    INNER JOIN [sys].[columns] AS [c]
                        ON [c].[object_id] = [dc].[parent_object_id]
                        AND [c].[column_id] = [dc].[parent_column_id]
                        AND [c].[name] = @columnName
                    WHERE [dc].[parent_object_id] = OBJECT_ID(@tableName)
                    UNION
                    SELECT [cc].[object_id]
                    FROM [sys].[check_constraints] AS [cc]
                    INNER JOIN [sys].[columns] AS [c]
                        ON [c].[object_id] = [cc].[parent_object_id]
                        AND (
                            [c].[column_id] = [cc].[parent_column_id]
                            OR (
                                [cc].[parent_column_id] = 0
                                AND CHARINDEX(QUOTENAME(@columnName), [cc].[definition]) > 0
                            )
                        )
                        AND [c].[name] = @columnName
                    WHERE [cc].[parent_object_id] = OBJECT_ID(@tableName)
                    UNION
                    SELECT OBJECT_ID([i].[name])
                    FROM [sys].[indexes] AS [i]
                    INNER JOIN [sys].[columns] AS [c]
                        ON [c].[object_id] = [i].[object_id]
                        AND [c].[name] = @columnName
                    INNER JOIN [sys].[index_columns] AS [ic]
                        ON [ic].[object_id] = [i].[object_id]
                        AND [i].[index_id] = [ic].[index_id]
                        AND [c].[column_id] = [ic].[column_id]
                    WHERE [i].[is_unique_constraint] = 1
                        AND [i].[object_id] = OBJECT_ID(@tableName)
                ) AS [cons]
                INNER JOIN [sys].[objects] AS [so] ON [so].[object_id] = [cons].[object_id]
            WHERE [so].[type] = N'D')
            IF @constraintName IS NULL BREAK
            EXEC (N'ALTER TABLE ' + @tableName + N' DROP CONSTRAINT [' + @constraintName + N']')
        END
        ALTER TABLE {{foo1}} ADD CONSTRAINT [[CK_foo1_bar]] CHECK (LEN(bar) > 5)
        SQL;

        self::assertSame(
            $expected,
            $qb->alterColumn('foo1', 'bar', $this->string(255)->check('LEN(bar) > 5')),
            "ALTER COLUMN to 'nvarchar(255)' should drop default constraints and add 'LEN(bar) > 5' CHECK constraint.",
        );

        $expected = <<<SQL
        ALTER TABLE {{foo1}} ALTER COLUMN [[bar]] nvarchar(255)
        DECLARE @tableName NVARCHAR(MAX) = N'[foo1]'
        DECLARE @columnName NVARCHAR(MAX) = N'bar'

        WHILE 1=1 BEGIN
            DECLARE @constraintName NVARCHAR(128)
            SET @constraintName = (SELECT TOP 1 OBJECT_NAME([cons].[object_id])
                FROM (
                    SELECT [dc].[object_id]
                    FROM [sys].[default_constraints] AS [dc]
                    INNER JOIN [sys].[columns] AS [c]
                        ON [c].[object_id] = [dc].[parent_object_id]
                        AND [c].[column_id] = [dc].[parent_column_id]
                        AND [c].[name] = @columnName
                    WHERE [dc].[parent_object_id] = OBJECT_ID(@tableName)
                    UNION
                    SELECT [cc].[object_id]
                    FROM [sys].[check_constraints] AS [cc]
                    INNER JOIN [sys].[columns] AS [c]
                        ON [c].[object_id] = [cc].[parent_object_id]
                        AND (
                            [c].[column_id] = [cc].[parent_column_id]
                            OR (
                                [cc].[parent_column_id] = 0
                                AND CHARINDEX(QUOTENAME(@columnName), [cc].[definition]) > 0
                            )
                        )
                        AND [c].[name] = @columnName
                    WHERE [cc].[parent_object_id] = OBJECT_ID(@tableName)
                    UNION
                    SELECT OBJECT_ID([i].[name])
                    FROM [sys].[indexes] AS [i]
                    INNER JOIN [sys].[columns] AS [c]
                        ON [c].[object_id] = [i].[object_id]
                        AND [c].[name] = @columnName
                    INNER JOIN [sys].[index_columns] AS [ic]
                        ON [ic].[object_id] = [i].[object_id]
                        AND [i].[index_id] = [ic].[index_id]
                        AND [c].[column_id] = [ic].[column_id]
                    WHERE [i].[is_unique_constraint] = 1
                        AND [i].[object_id] = OBJECT_ID(@tableName)
                ) AS [cons]
                INNER JOIN [sys].[objects] AS [so] ON [so].[object_id] = [cons].[object_id]
            WHERE [so].[type] = N'D')
            IF @constraintName IS NULL BREAK
            EXEC (N'ALTER TABLE ' + @tableName + N' DROP CONSTRAINT [' + @constraintName + N']')
        END
        ALTER TABLE {{foo1}} ADD CONSTRAINT [[DF_foo1_bar]] DEFAULT '' FOR [[bar]]
        SQL;

        self::assertSame(
            $expected,
            $qb->alterColumn('foo1', 'bar', $this->string(255)->defaultValue('')),
            "ALTER COLUMN to 'nvarchar(255)' should drop default constraints and add DEFAULT empty string.",
        );

        $expected = <<<SQL
        ALTER TABLE {{foo1}} ALTER COLUMN [[bar]] nvarchar(255)
        DECLARE @tableName NVARCHAR(MAX) = N'[foo1]'
        DECLARE @columnName NVARCHAR(MAX) = N'bar'

        WHILE 1=1 BEGIN
            DECLARE @constraintName NVARCHAR(128)
            SET @constraintName = (SELECT TOP 1 OBJECT_NAME([cons].[object_id])
                FROM (
                    SELECT [dc].[object_id]
                    FROM [sys].[default_constraints] AS [dc]
                    INNER JOIN [sys].[columns] AS [c]
                        ON [c].[object_id] = [dc].[parent_object_id]
                        AND [c].[column_id] = [dc].[parent_column_id]
                        AND [c].[name] = @columnName
                    WHERE [dc].[parent_object_id] = OBJECT_ID(@tableName)
                    UNION
                    SELECT [cc].[object_id]
                    FROM [sys].[check_constraints] AS [cc]
                    INNER JOIN [sys].[columns] AS [c]
                        ON [c].[object_id] = [cc].[parent_object_id]
                        AND (
                            [c].[column_id] = [cc].[parent_column_id]
                            OR (
                                [cc].[parent_column_id] = 0
                                AND CHARINDEX(QUOTENAME(@columnName), [cc].[definition]) > 0
                            )
                        )
                        AND [c].[name] = @columnName
                    WHERE [cc].[parent_object_id] = OBJECT_ID(@tableName)
                    UNION
                    SELECT OBJECT_ID([i].[name])
                    FROM [sys].[indexes] AS [i]
                    INNER JOIN [sys].[columns] AS [c]
                        ON [c].[object_id] = [i].[object_id]
                        AND [c].[name] = @columnName
                    INNER JOIN [sys].[index_columns] AS [ic]
                        ON [ic].[object_id] = [i].[object_id]
                        AND [i].[index_id] = [ic].[index_id]
                        AND [c].[column_id] = [ic].[column_id]
                    WHERE [i].[is_unique_constraint] = 1
                        AND [i].[object_id] = OBJECT_ID(@tableName)
                ) AS [cons]
                INNER JOIN [sys].[objects] AS [so] ON [so].[object_id] = [cons].[object_id]
            WHERE [so].[type] = N'D')
            IF @constraintName IS NULL BREAK
            EXEC (N'ALTER TABLE ' + @tableName + N' DROP CONSTRAINT [' + @constraintName + N']')
        END
        ALTER TABLE {{foo1}} ADD CONSTRAINT [[DF_foo1_bar]] DEFAULT 'AbCdE' FOR [[bar]]
        SQL;

        self::assertSame(
            $expected,
            $qb->alterColumn('foo1', 'bar', $this->string(255)->defaultValue('AbCdE')),
            "ALTER COLUMN to 'nvarchar(255)' should drop default constraints and add DEFAULT 'AbCdE'.",
        );

        $expected = <<<SQL
        ALTER TABLE {{foo1}} ALTER COLUMN [[bar]] datetime
        DECLARE @tableName NVARCHAR(MAX) = N'[foo1]'
        DECLARE @columnName NVARCHAR(MAX) = N'bar'

        WHILE 1=1 BEGIN
            DECLARE @constraintName NVARCHAR(128)
            SET @constraintName = (SELECT TOP 1 OBJECT_NAME([cons].[object_id])
                FROM (
                    SELECT [dc].[object_id]
                    FROM [sys].[default_constraints] AS [dc]
                    INNER JOIN [sys].[columns] AS [c]
                        ON [c].[object_id] = [dc].[parent_object_id]
                        AND [c].[column_id] = [dc].[parent_column_id]
                        AND [c].[name] = @columnName
                    WHERE [dc].[parent_object_id] = OBJECT_ID(@tableName)
                    UNION
                    SELECT [cc].[object_id]
                    FROM [sys].[check_constraints] AS [cc]
                    INNER JOIN [sys].[columns] AS [c]
                        ON [c].[object_id] = [cc].[parent_object_id]
                        AND (
                            [c].[column_id] = [cc].[parent_column_id]
                            OR (
                                [cc].[parent_column_id] = 0
                                AND CHARINDEX(QUOTENAME(@columnName), [cc].[definition]) > 0
                            )
                        )
                        AND [c].[name] = @columnName
                    WHERE [cc].[parent_object_id] = OBJECT_ID(@tableName)
                    UNION
                    SELECT OBJECT_ID([i].[name])
                    FROM [sys].[indexes] AS [i]
                    INNER JOIN [sys].[columns] AS [c]
                        ON [c].[object_id] = [i].[object_id]
                        AND [c].[name] = @columnName
                    INNER JOIN [sys].[index_columns] AS [ic]
                        ON [ic].[object_id] = [i].[object_id]
                        AND [i].[index_id] = [ic].[index_id]
                        AND [c].[column_id] = [ic].[column_id]
                    WHERE [i].[is_unique_constraint] = 1
                        AND [i].[object_id] = OBJECT_ID(@tableName)
                ) AS [cons]
                INNER JOIN [sys].[objects] AS [so] ON [so].[object_id] = [cons].[object_id]
            WHERE [so].[type] = N'D')
            IF @constraintName IS NULL BREAK
            EXEC (N'ALTER TABLE ' + @tableName + N' DROP CONSTRAINT [' + @constraintName + N']')
        END
        ALTER TABLE {{foo1}} ADD CONSTRAINT [[DF_foo1_bar]] DEFAULT CURRENT_TIMESTAMP FOR [[bar]]
        SQL;

        self::assertSame(
            $expected,
            $qb->alterColumn('foo1', 'bar', $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP')),
            'ALTER COLUMN datetime should drop default constraints and add DEFAULT CURRENT_TIMESTAMP.',
        );

        $expected = <<<SQL
        ALTER TABLE {{foo1}} ALTER COLUMN [[bar]] nvarchar(30)
        DECLARE @tableName NVARCHAR(MAX) = N'[foo1]'
        DECLARE @columnName NVARCHAR(MAX) = N'bar'

        WHILE 1=1 BEGIN
            DECLARE @constraintName NVARCHAR(128)
            SET @constraintName = (SELECT TOP 1 OBJECT_NAME([cons].[object_id])
                FROM (
                    SELECT [dc].[object_id]
                    FROM [sys].[default_constraints] AS [dc]
                    INNER JOIN [sys].[columns] AS [c]
                        ON [c].[object_id] = [dc].[parent_object_id]
                        AND [c].[column_id] = [dc].[parent_column_id]
                        AND [c].[name] = @columnName
                    WHERE [dc].[parent_object_id] = OBJECT_ID(@tableName)
                    UNION
                    SELECT [cc].[object_id]
                    FROM [sys].[check_constraints] AS [cc]
                    INNER JOIN [sys].[columns] AS [c]
                        ON [c].[object_id] = [cc].[parent_object_id]
                        AND (
                            [c].[column_id] = [cc].[parent_column_id]
                            OR (
                                [cc].[parent_column_id] = 0
                                AND CHARINDEX(QUOTENAME(@columnName), [cc].[definition]) > 0
                            )
                        )
                        AND [c].[name] = @columnName
                    WHERE [cc].[parent_object_id] = OBJECT_ID(@tableName)
                    UNION
                    SELECT OBJECT_ID([i].[name])
                    FROM [sys].[indexes] AS [i]
                    INNER JOIN [sys].[columns] AS [c]
                        ON [c].[object_id] = [i].[object_id]
                        AND [c].[name] = @columnName
                    INNER JOIN [sys].[index_columns] AS [ic]
                        ON [ic].[object_id] = [i].[object_id]
                        AND [i].[index_id] = [ic].[index_id]
                        AND [c].[column_id] = [ic].[column_id]
                    WHERE [i].[is_unique_constraint] = 1
                        AND [i].[object_id] = OBJECT_ID(@tableName)
                ) AS [cons]
                INNER JOIN [sys].[objects] AS [so] ON [so].[object_id] = [cons].[object_id]
            WHERE [so].[type] = N'D')
            IF @constraintName IS NULL BREAK
            EXEC (N'ALTER TABLE ' + @tableName + N' DROP CONSTRAINT [' + @constraintName + N']')
        END
        ALTER TABLE {{foo1}} ADD CONSTRAINT [[UQ_foo1_bar]] UNIQUE ([[bar]])
        SQL;

        self::assertSame(
            $expected,
            $qb->alterColumn('foo1', 'bar', $this->string(30)->unique()),
            "ALTER COLUMN to 'nvarchar(30)' should drop default constraints and add a UNIQUE constraint.",
        );
    }

    public function testAlterColumnOnDb(): void
    {
        $db = $this->getConnection(true);

        $sql = $db->queryBuilder->alterColumn('foo1', 'bar', 'varchar(255)');

        $db->createCommand($sql)->execute();

        $schema = $db->getTableSchema('[foo1]', true);

        self::assertSame(
            'varchar(255)',
            $schema->getColumn('bar')->dbType,
            "ALTER COLUMN to 'varchar(255)' should change the column dbType on the database.",
        );
        self::assertTrue(
            $schema->getColumn('bar')->allowNull,
            "ALTER COLUMN to 'varchar(255)' should allow NULL by default.",
        );

        $sql = $db->queryBuilder->alterColumn('foo1', 'bar', $this->string(128)->notNull());

        $db->createCommand($sql)->execute();

        $schema = $db->getTableSchema('[foo1]', true);

        self::assertSame(
            'nvarchar(128)',
            $schema->getColumn('bar')->dbType,
            "ALTER COLUMN to 'string(128)' NOT NULL should change the column dbType on the database.",
        );
        self::assertFalse(
            $schema->getColumn('bar')->allowNull,
            'ALTER COLUMN with NOT NULL should disallow NULL on the database.',
        );
    }

    public function testAlterColumnWithNull(): void
    {
        $qb = $this->getDb()->queryBuilder;

        $expected = <<<SQL
        ALTER TABLE {{foo1}} ALTER COLUMN [[bar]] int NULL
        DECLARE @tableName NVARCHAR(MAX) = N'[foo1]'
        DECLARE @columnName NVARCHAR(MAX) = N'bar'

        WHILE 1=1 BEGIN
            DECLARE @constraintName NVARCHAR(128)
            SET @constraintName = (SELECT TOP 1 OBJECT_NAME([cons].[object_id])
                FROM (
                    SELECT [dc].[object_id]
                    FROM [sys].[default_constraints] AS [dc]
                    INNER JOIN [sys].[columns] AS [c]
                        ON [c].[object_id] = [dc].[parent_object_id]
                        AND [c].[column_id] = [dc].[parent_column_id]
                        AND [c].[name] = @columnName
                    WHERE [dc].[parent_object_id] = OBJECT_ID(@tableName)
                    UNION
                    SELECT [cc].[object_id]
                    FROM [sys].[check_constraints] AS [cc]
                    INNER JOIN [sys].[columns] AS [c]
                        ON [c].[object_id] = [cc].[parent_object_id]
                        AND (
                            [c].[column_id] = [cc].[parent_column_id]
                            OR (
                                [cc].[parent_column_id] = 0
                                AND CHARINDEX(QUOTENAME(@columnName), [cc].[definition]) > 0
                            )
                        )
                        AND [c].[name] = @columnName
                    WHERE [cc].[parent_object_id] = OBJECT_ID(@tableName)
                    UNION
                    SELECT OBJECT_ID([i].[name])
                    FROM [sys].[indexes] AS [i]
                    INNER JOIN [sys].[columns] AS [c]
                        ON [c].[object_id] = [i].[object_id]
                        AND [c].[name] = @columnName
                    INNER JOIN [sys].[index_columns] AS [ic]
                        ON [ic].[object_id] = [i].[object_id]
                        AND [i].[index_id] = [ic].[index_id]
                        AND [c].[column_id] = [ic].[column_id]
                    WHERE [i].[is_unique_constraint] = 1
                        AND [i].[object_id] = OBJECT_ID(@tableName)
                ) AS [cons]
                INNER JOIN [sys].[objects] AS [so] ON [so].[object_id] = [cons].[object_id]
            WHERE [so].[type] = N'D')
            IF @constraintName IS NULL BREAK
            EXEC (N'ALTER TABLE ' + @tableName + N' DROP CONSTRAINT [' + @constraintName + N']')
        END
        ALTER TABLE {{foo1}} ADD CONSTRAINT [[DF_foo1_bar]] DEFAULT NULL FOR [[bar]]
        SQL;

        self::assertSame(
            $expected,
            $qb->alterColumn('foo1', 'bar', $this->integer()->null()->defaultValue(null)),
            'ALTER COLUMN int NULL should drop default constraints and add DEFAULT NULL.',
        );
    }

    public function testAlterColumnWithExpression(): void
    {
        $qb = $this->getDb()->queryBuilder;

        $expected = <<<SQL
        ALTER TABLE {{foo1}} ALTER COLUMN [[bar]] int NULL
        DECLARE @tableName NVARCHAR(MAX) = N'[foo1]'
        DECLARE @columnName NVARCHAR(MAX) = N'bar'

        WHILE 1=1 BEGIN
            DECLARE @constraintName NVARCHAR(128)
            SET @constraintName = (SELECT TOP 1 OBJECT_NAME([cons].[object_id])
                FROM (
                    SELECT [dc].[object_id]
                    FROM [sys].[default_constraints] AS [dc]
                    INNER JOIN [sys].[columns] AS [c]
                        ON [c].[object_id] = [dc].[parent_object_id]
                        AND [c].[column_id] = [dc].[parent_column_id]
                        AND [c].[name] = @columnName
                    WHERE [dc].[parent_object_id] = OBJECT_ID(@tableName)
                    UNION
                    SELECT [cc].[object_id]
                    FROM [sys].[check_constraints] AS [cc]
                    INNER JOIN [sys].[columns] AS [c]
                        ON [c].[object_id] = [cc].[parent_object_id]
                        AND (
                            [c].[column_id] = [cc].[parent_column_id]
                            OR (
                                [cc].[parent_column_id] = 0
                                AND CHARINDEX(QUOTENAME(@columnName), [cc].[definition]) > 0
                            )
                        )
                        AND [c].[name] = @columnName
                    WHERE [cc].[parent_object_id] = OBJECT_ID(@tableName)
                    UNION
                    SELECT OBJECT_ID([i].[name])
                    FROM [sys].[indexes] AS [i]
                    INNER JOIN [sys].[columns] AS [c]
                        ON [c].[object_id] = [i].[object_id]
                        AND [c].[name] = @columnName
                    INNER JOIN [sys].[index_columns] AS [ic]
                        ON [ic].[object_id] = [i].[object_id]
                        AND [i].[index_id] = [ic].[index_id]
                        AND [c].[column_id] = [ic].[column_id]
                    WHERE [i].[is_unique_constraint] = 1
                        AND [i].[object_id] = OBJECT_ID(@tableName)
                ) AS [cons]
                INNER JOIN [sys].[objects] AS [so] ON [so].[object_id] = [cons].[object_id]
            WHERE [so].[type] = N'D')
            IF @constraintName IS NULL BREAK
            EXEC (N'ALTER TABLE ' + @tableName + N' DROP CONSTRAINT [' + @constraintName + N']')
        END
        ALTER TABLE {{foo1}} ADD CONSTRAINT [[DF_foo1_bar]] DEFAULT CAST(GETDATE() AS INT) FOR [[bar]]
        SQL;

        self::assertSame(
            $expected,
            $qb->alterColumn(
                'foo1',
                'bar',
                $this->integer()->null()->defaultValue(new Expression('CAST(GETDATE() AS INT)')),
            ),
            'ALTER COLUMN int NULL should drop default constraints and add DEFAULT with Expression.',
        );
    }

    public function testAlterColumnWithCheckConstraintOnDb(): void
    {
        $db = $this->getConnection(true);

        $sql = $db->queryBuilder->alterColumn('foo1', 'bar', $this->string(128)->null()->check('LEN(bar) > 5'));

        $db->createCommand($sql)->execute();

        $schema = $db->getTableSchema('[foo1]', true);

        self::assertSame(
            'nvarchar(128)',
            $schema->getColumn('bar')->dbType,
            'ALTER COLUMN with CHECK should change the column dbType on the database.',
        );
        self::assertTrue(
            $schema->getColumn('bar')->allowNull,
            'ALTER COLUMN with CHECK and NULL should allow NULL on the database.',
        );

        $sql = <<<SQL
        INSERT INTO [foo1]([bar]) values('abcdef')
        SQL;

        self::assertSame(
            1,
            $db->createCommand($sql)->execute(),
            "INSERT with value passing CHECK constraint ('LEN > 5') should succeed.",
        );
    }

    public function testAlterColumnWithCheckConstraintOnDbWithException(): void
    {
        $db = $this->getConnection(true);

        $sql = $db->queryBuilder->alterColumn('foo1', 'bar', $this->string(64)->check('LEN(bar) > 5'));

        $db->createCommand($sql)->execute();

        self::expectException(IntegrityException::class);
        self::expectExceptionMessageMatches(
            '/conflicted with the CHECK constraint "CK_foo1_bar"/',
        );

        $sql = <<<SQL
        INSERT INTO [foo1]([bar]) values('abcde')
        SQL;

        $db->createCommand($sql)->execute();
    }

    public function testAlterColumnWithUniqueConstraintOnDbWithException(): void
    {
        $db = $this->getConnection(true);

        $sql = $db->queryBuilder->alterColumn('foo1', 'bar', $this->string(64)->unique());

        $db->createCommand($sql)->execute();

        $sql = <<<SQL
        INSERT INTO [foo1]([bar]) values('abcdef')
        SQL;

        self::assertSame(
            1,
            $db->createCommand($sql)->execute(),
            'First INSERT with UNIQUE constraint should succeed.',
        );

        self::expectException(
            IntegrityException::class,
        );
        self::expectExceptionMessageMatches(
            '/Violation of UNIQUE KEY constraint \'UQ_foo1_bar\'/',
        );

        $db->createCommand($sql)->execute();
    }

    public function testDropColumn(): void
    {
        $qb = $this->getDb()->queryBuilder;

        $expected = <<<SQL
        DECLARE @tableName NVARCHAR(MAX) = N'[foo1]'
        DECLARE @columnName NVARCHAR(MAX) = N'bar'

        WHILE 1=1 BEGIN
            DECLARE @constraintName NVARCHAR(128)
            SET @constraintName = (SELECT TOP 1 OBJECT_NAME([cons].[object_id])
                FROM (
                    SELECT [dc].[object_id]
                    FROM [sys].[default_constraints] AS [dc]
                    INNER JOIN [sys].[columns] AS [c]
                        ON [c].[object_id] = [dc].[parent_object_id]
                        AND [c].[column_id] = [dc].[parent_column_id]
                        AND [c].[name] = @columnName
                    WHERE [dc].[parent_object_id] = OBJECT_ID(@tableName)
                    UNION
                    SELECT [cc].[object_id]
                    FROM [sys].[check_constraints] AS [cc]
                    INNER JOIN [sys].[columns] AS [c]
                        ON [c].[object_id] = [cc].[parent_object_id]
                        AND (
                            [c].[column_id] = [cc].[parent_column_id]
                            OR (
                                [cc].[parent_column_id] = 0
                                AND CHARINDEX(QUOTENAME(@columnName), [cc].[definition]) > 0
                            )
                        )
                        AND [c].[name] = @columnName
                    WHERE [cc].[parent_object_id] = OBJECT_ID(@tableName)
                    UNION
                    SELECT OBJECT_ID([i].[name])
                    FROM [sys].[indexes] AS [i]
                    INNER JOIN [sys].[columns] AS [c]
                        ON [c].[object_id] = [i].[object_id]
                        AND [c].[name] = @columnName
                    INNER JOIN [sys].[index_columns] AS [ic]
                        ON [ic].[object_id] = [i].[object_id]
                        AND [i].[index_id] = [ic].[index_id]
                        AND [c].[column_id] = [ic].[column_id]
                    WHERE [i].[is_unique_constraint] = 1
                        AND [i].[object_id] = OBJECT_ID(@tableName)
                ) AS [cons]
                INNER JOIN [sys].[objects] AS [so] ON [so].[object_id] = [cons].[object_id])
            IF @constraintName IS NULL BREAK
            EXEC (N'ALTER TABLE ' + @tableName + N' DROP CONSTRAINT [' + @constraintName + N']')
        END
        ALTER TABLE {{foo1}} DROP COLUMN [[bar]]
        SQL;

        self::assertSame(
            $expected,
            $qb->dropColumn('foo1', 'bar'),
            'DROP COLUMN should drop all constraints for the column before removing it.',
        );
    }

    public function testDropColumnOnDb(): void
    {
        $db = $this->getConnection(true);

        $sql = $db->queryBuilder
            ->alterColumn('foo1', 'bar', $this->string(64)
            ->check('LEN(bar) < 5')
            ->defaultValue('')
            ->unique());

        $db->createCommand($sql)->execute();

        $sql = $db->queryBuilder
            ->dropColumn('foo1', 'bar');

        self::assertSame(
            0,
            $db->createCommand($sql)->execute(),
            'DROP COLUMN should execute without errors on a column with DEFAULT, CHECK, and UNIQUE constraints.',
        );

        $schema = $db->getTableSchema('[foo1]', true);

        self::assertNull(
            $schema->getColumn('bar'),
            'Column should no longer exist in the table schema after DROP COLUMN.',
        );
    }

    public function testDropColumnWithTableScopedCheckConstraintOnDb(): void
    {
        $db = $this->getConnection(true);

        $db->createCommand(
            <<<SQL
            ALTER TABLE [foo1] ADD CONSTRAINT [CK_foo1_table_scope] CHECK ([bar] IS NOT NULL OR [id] > 0)
            SQL,
        )->execute();

        $sql = $db->queryBuilder->dropColumn('foo1', 'bar');

        self::assertSame(
            0,
            $db->createCommand($sql)->execute(),
            'DROP COLUMN should execute without errors on a column with a table-scoped CHECK constraint.',
        );

        $schema = $db->getTableSchema('[foo1]', true);

        self::assertNull(
            $schema->getColumn('bar'),
            'Column should no longer exist in the table schema after DROP COLUMN with table-scoped CHECK.',
        );
    }
}
