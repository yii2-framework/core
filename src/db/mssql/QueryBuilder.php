<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\db\mssql;

use yii\base\InvalidArgumentException;
use yii\base\NotSupportedException;
use yii\db\conditions\InCondition;
use yii\db\conditions\LikeCondition;
use yii\db\Expression;
use yii\db\mssql\ColumnSchemaBuilder;
use yii\db\Query;
use yii\db\TableSchema;
use function count;

/**
 * QueryBuilder is the query builder for MS SQL Server databases (version 2017 and later).
 *
 * @author Timur Ruziev <resurtm@gmail.com>
 * @since 2.0
 */
class QueryBuilder extends \yii\db\QueryBuilder
{
    /**
     * @var array mapping from abstract column types (keys) to physical column types (values).
     */
    public $typeMap = [
        Schema::TYPE_PK => 'int IDENTITY PRIMARY KEY',
        Schema::TYPE_UPK => 'int IDENTITY PRIMARY KEY',
        Schema::TYPE_BIGPK => 'bigint IDENTITY PRIMARY KEY',
        Schema::TYPE_UBIGPK => 'bigint IDENTITY PRIMARY KEY',
        Schema::TYPE_CHAR => 'nchar(1)',
        Schema::TYPE_STRING => 'nvarchar(255)',
        Schema::TYPE_TEXT => 'nvarchar(max)',
        Schema::TYPE_TINYINT => 'tinyint',
        Schema::TYPE_SMALLINT => 'smallint',
        Schema::TYPE_INTEGER => 'int',
        Schema::TYPE_BIGINT => 'bigint',
        Schema::TYPE_FLOAT => 'float',
        Schema::TYPE_DOUBLE => 'float',
        Schema::TYPE_DECIMAL => 'decimal(18,0)',
        Schema::TYPE_DATETIME => 'datetime',
        Schema::TYPE_TIMESTAMP => 'datetime',
        Schema::TYPE_TIME => 'time',
        Schema::TYPE_DATE => 'date',
        Schema::TYPE_BINARY => 'varbinary(max)',
        Schema::TYPE_BOOLEAN => 'bit',
        Schema::TYPE_MONEY => 'decimal(19,4)',
    ];

    /**
     * {@inheritdoc}
     */
    protected function defaultExpressionBuilders()
    {
        return [
            ...parent::defaultExpressionBuilders(),
            InCondition::class => conditions\InConditionBuilder::class,
            LikeCondition::class => conditions\LikeConditionBuilder::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildOrderByAndLimit($sql, $orderBy, $limit, $offset)
    {
        if (!$this->hasOffset($offset) && !$this->hasLimit($limit)) {
            $orderBy = $this->buildOrderBy($orderBy);

            return $orderBy === '' ? $sql : "{$sql}{$this->separator}{$orderBy}";
        }

        $orderBy = $this->buildOrderBy($orderBy);

        if ($orderBy === '') {
            // ORDER BY clause is required when FETCH and OFFSET are in the SQL
            $orderBy = 'ORDER BY (SELECT NULL)';
        }

        $sql .= "{$this->separator}{$orderBy}";

        // https://learn.microsoft.com/en-us/sql/t-sql/queries/select-order-by-clause-transact-sql
        $offset = $this->hasOffset($offset) ? $offset : '0';

        $sql .= "{$this->separator}OFFSET {$offset} ROWS";

        if ($this->hasLimit($limit)) {
            $sql .= "{$this->separator}FETCH NEXT {$limit} ROWS ONLY";
        }

        return $sql;
    }

    /**
     * Builds a SQL statement for renaming a DB table.
     * @param string $oldName the table to be renamed. The name will be properly quoted by the method.
     * @param string $newName the new table name. The name will be properly quoted by the method.
     * @return string the SQL statement for renaming a DB table.
     */
    public function renameTable($oldName, $newName)
    {
        $oldName = $this->db->quoteTableName($oldName);
        $newName = $this->db->quoteTableName($newName);

        return <<<SQL
        sp_rename {$oldName}, {$newName}
        SQL;
    }

    /**
     * Builds a SQL statement for renaming a column.
     * @param string $table the table whose column is to be renamed. The name will be properly quoted by the method.
     * @param string $oldName the old name of the column. The name will be properly quoted by the method.
     * @param string $newName the new name of the column. The name will be properly quoted by the method.
     * @return string the SQL statement for renaming a DB column.
     */
    public function renameColumn($table, $oldName, $newName)
    {
        $table = $this->db->quoteTableName($table);
        $oldName = $this->db->quoteColumnName($oldName);
        $newName = $this->db->quoteColumnName($newName);

        return <<<SQL
        sp_rename N'{$table}.{$oldName}', {$newName}, N'COLUMN'
        SQL;
    }

    /**
     * Builds a SQL statement for changing the definition of a column.
     * @param string $table the table whose column is to be changed. The table name will be properly quoted by the method.
     * @param string $column the name of the column to be changed. The name will be properly quoted by the method.
     * @param string $type the new column type. The [[getColumnType]] method will be invoked to convert abstract column type (if any)
     * into the physical one. Anything that is not recognized as abstract type will be kept in the generated SQL.
     * For example, 'string' will be turned into 'varchar(255)', while 'string not null' will become 'varchar(255) not null'.
     * @return string the SQL statement for changing the definition of a column.
     * @throws NotSupportedException if this is not supported by the underlying DBMS.
     */
    public function alterColumn($table, $column, $type)
    {
        $sqlAfter = [$this->dropConstraintsForColumn($table, $column, 'D')];
        $constraintBase = preg_replace('/[^a-z0-9_]/i', '', "{$table}_{$column}");

        if ($type instanceof ColumnSchemaBuilder) {
            $type->setAlterColumnFormat();
            $defaultValue = $type->getDefaultValue();

            if ($defaultValue !== null) {
                $sqlAfter[] = $this->addDefaultValue(
                    "DF_{$constraintBase}",
                    $table,
                    $column,
                    $defaultValue instanceof Expression
                        ? $defaultValue
                        : new Expression($defaultValue),
                );
            }

            $checkValue = $type->getCheckValue();

            if ($checkValue !== null) {
                $check = $checkValue instanceof Expression ? $checkValue : new Expression($checkValue);
                $sqlAfter[] = <<<SQL
                ALTER TABLE {{{$table}}} ADD CONSTRAINT [[CK_{$constraintBase}]] CHECK ({$check})
                SQL;
            }

            if ($type->isUnique()) {
                $sqlAfter[] = <<<SQL
                ALTER TABLE {{{$table}}} ADD CONSTRAINT [[UQ_{$constraintBase}]] UNIQUE ([[{$column}]])
                SQL;
            }
        }

        $columnType = $this->getColumnType($type);
        $after = implode("\n", $sqlAfter);

        return <<<SQL
        ALTER TABLE {{{$table}}} ALTER COLUMN [[{$column}]] {$columnType}
        {$after}
        SQL;
    }

    /**
     * {@inheritdoc}
     */
    public function addDefaultValue($name, $table, $column, $value)
    {
        $defaultValue = $this->db->quoteValue($value);

        return <<<SQL
        ALTER TABLE {{{$table}}} ADD CONSTRAINT [[{$name}]] DEFAULT {$defaultValue} FOR [[{$column}]]
        SQL;
    }

    /**
     * {@inheritdoc}
     */
    public function dropDefaultValue($name, $table)
    {
        return <<<SQL
        ALTER TABLE {{{$table}}} DROP CONSTRAINT [[{$name}]]
        SQL;
    }

    /**
     * Creates a SQL statement for resetting the sequence value of a table's primary key.
     * The sequence will be reset such that the primary key of the next new row inserted
     * will have the specified value or 1.
     * @param string $tableName the name of the table whose primary key sequence will be reset
     * @param mixed $value the value for the primary key of the next new row inserted. If this is not set,
     * the next new row's primary key will have a value 1.
     * @return string the SQL statement for resetting sequence
     * @throws InvalidArgumentException if the table does not exist or there is no sequence associated with the table.
     */
    public function resetSequence($tableName, $value = null)
    {
        $table = $this->db->getTableSchema($tableName);

        if ($table !== null && $table->sequenceName !== null) {
            $tableName = $this->db->quoteTableName($tableName);

            if ($value === null || $value === 1) {
                $key = $this->db->quoteColumnName(reset($table->primaryKey));

                $sql = <<<SQL
                SELECT COALESCE(
                    MAX({$key}),
                    CASE WHEN EXISTS (
                        SELECT 1
                        FROM [sys].[identity_columns]
                        WHERE [object_id] = OBJECT_ID(:tableName)
                            AND [last_value] IS NOT NULL
                    ) THEN 0 ELSE 1 END
                ) FROM {$tableName}
                SQL;

                $value = $this->db->createCommand($sql, [':tableName' => $tableName])->queryScalar();
            } else {
                $value = (int) $value;
            }

            return "DBCC CHECKIDENT ('{$tableName}', RESEED, {$value})";
        } elseif ($table === null) {
            throw new InvalidArgumentException("Table not found: $tableName");
        }

        throw new InvalidArgumentException("There is not sequence associated with table '$tableName'.");
    }

    /**
     * Builds a SQL statement for enabling or disabling integrity check.
     * @param bool $check whether to turn on or off the integrity check.
     * @param string $schema the schema of the tables.
     * @param string $table the table name.
     * @return string the SQL statement for checking integrity
     */
    public function checkIntegrity($check = true, $schema = '', $table = '')
    {
        /** @var Schema $dbSchema */
        $dbSchema = $this->db->getSchema();

        $enable = $check ? 'CHECK' : 'NOCHECK';
        $schema = $schema ?: $dbSchema->defaultSchema;

        $tableNames = $this->db->getTableSchema($table) ? [$table] : $dbSchema->getTableNames($schema);
        $viewNames = $dbSchema->getViewNames($schema);
        $tableNames = array_diff($tableNames, $viewNames);

        $command = '';

        foreach ($tableNames as $tableName) {
            $command .= <<<SQL
            ALTER TABLE {{{$schema}.{$tableName}}} {$enable} CONSTRAINT ALL
            SQL;
        }

        return $command;
    }

     /**
      * Builds a SQL command for adding or updating a comment to a table or a column. The command built will check if a comment
      * already exists. If so, it will be updated, otherwise, it will be added.
      *
      * @param string $comment the text of the comment to be added. The comment will be properly quoted by the method.
      * @param string $table the table to be commented or whose column is to be commented. The table name will be
      * properly quoted by the method.
      * @param string|null $column optional. The name of the column to be commented. If empty, the command will add the
      * comment to the table instead. The column name will be properly quoted by the method.
      * @return string the SQL statement for adding a comment.
      * @throws InvalidArgumentException if the table does not exist.
      * @since 2.0.24
      */
    protected function buildAddCommentSql($comment, $table, $column = null)
    {
        $tableSchema = $this->db->schema->getTableSchema($table);

        if ($tableSchema === null) {
            throw new InvalidArgumentException("Table not found: $table");
        }

        $schemaName = $tableSchema->schemaName !== null
            ? 'N' . $this->db->quoteValue($tableSchema->schemaName)
            : 'SCHEMA_NAME()';

        $tableName = 'N' . $this->db->quoteValue($tableSchema->name);
        $columnName = $column !== null ? 'N' . $this->db->quoteValue($column) : null;
        $comment = 'N' . $this->db->quoteValue($comment);

        $schemaAndTable = $tableSchema->schemaName !== null
            ? $tableSchema->schemaName . '.' . $tableSchema->name
            : $tableSchema->name;

        $quotedSchemaAndTable = 'N' . $this->db->quoteValue($schemaAndTable);

        if ($column !== null) {
            $existsCondition = <<<SQL
            SELECT 1
            FROM [sys].[extended_properties]
            WHERE [major_id] = OBJECT_ID({$quotedSchemaAndTable})
                AND [minor_id] = COLUMNPROPERTY(OBJECT_ID({$quotedSchemaAndTable}), {$columnName}, 'ColumnId')
                AND [name] = N'MS_Description'
            SQL;
        } else {
            $existsCondition = <<<SQL
            SELECT 1
            FROM [sys].[extended_properties]
            WHERE [major_id] = OBJECT_ID({$quotedSchemaAndTable})
                AND [minor_id] = 0
                AND [name] = N'MS_Description'
            SQL;
        }

        $columnParam = $column !== null ? ", @level2type = N'COLUMN', @level2name = {$columnName}" : '';

        $functionParams = <<<SQL
        @name = N'MS_Description',
        @value = {$comment},
        @level0type = N'SCHEMA', @level0name = {$schemaName},
        @level1type = N'TABLE', @level1name = {$tableName}{$columnParam};
        SQL;

        return <<<SQL
        IF NOT EXISTS (
            {$existsCondition}
        )
            EXEC sys.sp_addextendedproperty {$functionParams}
        ELSE
            EXEC sys.sp_updateextendedproperty {$functionParams}
        SQL;
    }

    /**
     * {@inheritdoc}
     * @since 2.0.8
     */
    public function addCommentOnColumn($table, $column, $comment)
    {
        return $this->buildAddCommentSql($comment, $table, $column);
    }

    /**
     * {@inheritdoc}
     * @since 2.0.8
     */
    public function addCommentOnTable($table, $comment)
    {
        return $this->buildAddCommentSql($comment, $table);
    }

    /**
     * Builds a SQL command for removing a comment from a table or a column. The command built will check if a comment
     * already exists before trying to perform the removal.
     *
     * @param string $table the table that will have the comment removed or whose column will have the comment removed.
     * The table name will be properly quoted by the method.
     * @param string|null $column optional. The name of the column whose comment will be removed. If empty, the command
     * will remove the comment from the table instead. The column name will be properly quoted by the method.
     * @return string the SQL statement for removing the comment.
     * @throws InvalidArgumentException if the table does not exist.
     * @since 2.0.24
     */
    protected function buildRemoveCommentSql($table, $column = null)
    {
        $tableSchema = $this->db->schema->getTableSchema($table);

        if ($tableSchema === null) {
            throw new InvalidArgumentException("Table not found: $table");
        }

        $schemaName = $tableSchema->schemaName !== null
            ? 'N' . $this->db->quoteValue($tableSchema->schemaName)
            : 'SCHEMA_NAME()';

        $tableName = 'N' . $this->db->quoteValue($tableSchema->name);
        $columnName = $column !== null ? 'N' . $this->db->quoteValue($column) : null;

        $schemaAndTable = $tableSchema->schemaName !== null
            ? $tableSchema->schemaName . '.' . $tableSchema->name
            : $tableSchema->name;

        $quotedSchemaAndTable = 'N' . $this->db->quoteValue($schemaAndTable);

        if ($column !== null) {
            $existsCondition = <<<SQL
            SELECT 1
            FROM [sys].[extended_properties]
            WHERE [major_id] = OBJECT_ID({$quotedSchemaAndTable})
                AND [minor_id] = COLUMNPROPERTY(OBJECT_ID({$quotedSchemaAndTable}), {$columnName}, 'ColumnId')
                AND [name] = N'MS_Description'
            SQL;
        } else {
            $existsCondition = <<<SQL
            SELECT 1
            FROM [sys].[extended_properties]
            WHERE [major_id] = OBJECT_ID({$quotedSchemaAndTable})
                AND [minor_id] = 0
                AND [name] = N'MS_Description'
            SQL;
        }

        $columnParam = $column !== null ? ", @level2type = N'COLUMN', @level2name = {$columnName}" : '';

        $dropParams = <<<SQL
        @name = N'MS_Description',
        @level0type = N'SCHEMA', @level0name = {$schemaName},
        @level1type = N'TABLE', @level1name = {$tableName}{$columnParam};
        SQL;

        return <<<SQL
        IF EXISTS (
            {$existsCondition}
        )
            EXEC sys.sp_dropextendedproperty {$dropParams}
        SQL;
    }

    /**
     * {@inheritdoc}
     * @since 2.0.8
     */
    public function dropCommentFromColumn($table, $column)
    {
        return $this->buildRemoveCommentSql($table, $column);
    }

    /**
     * {@inheritdoc}
     * @since 2.0.8
     */
    public function dropCommentFromTable($table)
    {
        return $this->buildRemoveCommentSql($table);
    }

    /**
     * Returns an array of column names given model name.
     *
     * @param string|null $modelClass name of the model class
     * @return array|null array of column names
     */
    protected function getAllColumnNames($modelClass = null)
    {
        if (!$modelClass) {
            return null;
        }
        /** @var \yii\db\ActiveRecord $modelClass */
        $schema = $modelClass::getTableSchema();

        return array_keys($schema->columns);
    }

    /**
     * {@inheritdoc}
     * @since 2.0.8
     */
    public function selectExists($rawSql)
    {
        return "SELECT CASE WHEN EXISTS({$rawSql}) THEN 1 ELSE 0 END";
    }

    /**
     * {@inheritdoc}
     *
     * Uses the OUTPUT clause to return inserted data.
     */
    public function insert($table, $columns, &$params)
    {
        [$names, $placeholders, $values, $params] = $this->prepareInsertValues($table, $columns, $params);

        $cols = [];
        $outputColumns = [];

        /** @var TableSchema $schema */
        $schema = $this->db->getTableSchema($table);

        foreach ($schema->columns as $column) {
            if ($column->isComputed) {
                continue;
            }

            $quoteColumnName = $this->db->quoteColumnName($column->name);
            $cols[] = "{$quoteColumnName} "
                . $column->getOutputColumnDeclaration()
                . ' '
                . ($column->allowNull ? 'NULL' : '');

            $outputColumns[] = "INSERTED.{$quoteColumnName}";
        }

        $countColumns = count($outputColumns);

        $sql = 'INSERT INTO ' . $this->db->quoteTableName($table)
            . (!empty($names) ? ' (' . implode(', ', $names) . ')' : '')
            . ($countColumns ? ' OUTPUT ' . implode(',', $outputColumns) . ' INTO @temporary_inserted' : '')
            . (!empty($placeholders) ? ' VALUES (' . implode(', ', $placeholders) . ')' : $values);

        if ($countColumns) {
            $sql = 'SET NOCOUNT ON;DECLARE @temporary_inserted TABLE (' . implode(', ', $cols) . ');' . $sql .
                ';SELECT * FROM @temporary_inserted';
        }

        return $sql;
    }

    /**
     * {@inheritdoc}
     * @see https://learn.microsoft.com/en-us/sql/t-sql/statements/merge-transact-sql
     * @see https://weblogs.sqlteam.com/dang/2009/01/31/upsert-race-condition-with-merge/
     */
    public function upsert($table, $insertColumns, $updateColumns, &$params)
    {
        [$uniqueNames, $insertNames, $updateNames] = $this->prepareUpsertColumns(
            $table,
            $insertColumns,
            $updateColumns,
            $constraints,
        );

        if (empty($uniqueNames)) {
            return $this->insert($table, $insertColumns, $params);
        }

        if ($updateNames === []) {
            // there are no columns to update
            $updateColumns = false;
        }

        $onCondition = ['or'];

        $quotedTableName = $this->db->quoteTableName($table);

        foreach ($constraints as $constraint) {
            $constraintCondition = ['and'];

            foreach ($constraint->columnNames as $name) {
                $quotedName = $this->db->quoteColumnName($name);

                $constraintCondition[] = "$quotedTableName.$quotedName=[EXCLUDED].$quotedName";
            }

            $onCondition[] = $constraintCondition;
        }

        $on = $this->buildCondition($onCondition, $params);

        [, $placeholders, $values, $params] = $this->prepareInsertValues($table, $insertColumns, $params);

        $mergeSql = 'MERGE '
            . $this->db->quoteTableName($table)
            . ' WITH (HOLDLOCK) '
            . 'USING ('
            . (!empty($placeholders) ? 'VALUES ('
            . implode(', ', $placeholders)
            . ')' : ltrim($values, ' '))
            . ') AS [EXCLUDED] ('
            . implode(', ', $insertNames)
            . ') '
            . "ON ($on)";

        $insertValues = [];

        foreach ($insertNames as $name) {
            $quotedName = $this->db->quoteColumnName($name);

            if (strrpos($quotedName, '.') === false) {
                $quotedName = "[EXCLUDED].{$quotedName}";
            }

            $insertValues[] = $quotedName;
        }

        $insertSql = 'INSERT ('
            . implode(', ', $insertNames)
            . ')'
            . ' VALUES ('
            . implode(', ', $insertValues)
            . ')';

        if ($updateColumns === false) {
            return "{$mergeSql} WHEN NOT MATCHED THEN {$insertSql};";
        }

        if ($updateColumns === true) {
            $updateColumns = [];

            foreach ($updateNames as $name) {
                $quotedName = $this->db->quoteColumnName($name);

                if (strrpos($quotedName, '.') === false) {
                    $quotedName = "[EXCLUDED].{$quotedName}";
                }

                $updateColumns[$name] = new Expression($quotedName);
            }
        }

        [$updates, $params] = $this->prepareUpdateSets($table, $updateColumns, $params);

        $updateSql = 'UPDATE SET ' . implode(', ', $updates);

        return "{$mergeSql} WHEN MATCHED THEN {$updateSql} WHEN NOT MATCHED THEN {$insertSql};";
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnType($type)
    {
        $columnType = parent::getColumnType($type);
        // remove unsupported keywords
        $columnType = preg_replace("/\s*comment '.*'/i", '', $columnType);
        $columnType = preg_replace('/ first$/i', '', $columnType);

        return $columnType;
    }

    /**
     * {@inheritdoc}
     */
    protected function extractAlias($table)
    {
        if (preg_match('/^\[.*\]$/', $table)) {
            return false;
        }

        return parent::extractAlias($table);
    }

    /**
     * Builds a SQL statement for dropping constraints for column of table.
     *
     * Table-scoped CHECK constraints (`parent_column_id = 0`) are detected via `CHARINDEX(QUOTENAME())` on the
     * constraint definition. This may produce a false positive if a string literal inside the definition contains the
     * bracketed column name (for example, `CHECK ([other] <> '[bar]')`), causing the constraint to be dropped
     * unnecessarily. In practice this scenario is extremely rare.
     *
     * @param string $table the table whose constraint is to be dropped. The name will be properly quoted by the method.
     * @param string $column the column whose constraint is to be dropped. The name will be properly quoted by the method.
     * @param string $type type of constraint, leave empty for all type of constraints(for example: D - default, 'UQ' - unique, 'C' - check)
     *
     * @see https://learn.microsoft.com/en-us/sql/relational-databases/system-catalog-views/sys-default-constraints-transact-sql
     * @see https://learn.microsoft.com/en-us/sql/relational-databases/system-catalog-views/sys-check-constraints-transact-sql
     * @see https://learn.microsoft.com/en-us/sql/relational-databases/system-catalog-views/sys-objects-transact-sql
     *
     * @return string the DROP CONSTRAINTS SQL
     */
    private function dropConstraintsForColumn($table, $column, $type = '')
    {
        $tableName = $this->db->quoteTableName($table);
        $quotedType = $this->db->quoteValue($type);
        $typeFilter = $type !== '' ? "\n    WHERE [so].[type] = N{$quotedType}" : '';

        return <<<SQL
        DECLARE @tableName NVARCHAR(MAX) = N'{$tableName}'
        DECLARE @columnName NVARCHAR(MAX) = N'{$column}'

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
                INNER JOIN [sys].[objects] AS [so] ON [so].[object_id] = [cons].[object_id]{$typeFilter})
            IF @constraintName IS NULL BREAK
            EXEC (N'ALTER TABLE ' + @tableName + N' DROP CONSTRAINT [' + @constraintName + N']')
        END
        SQL;
    }

    /**
     * {@inheritdoc}
     *
     * Drop all constraints before column delete
     */
    public function dropColumn($table, $column)
    {
        $dropConstraintsForColumn = $this->dropConstraintsForColumn($table, $column);

        return <<<SQL
        $dropConstraintsForColumn
        ALTER TABLE {{{$table}}} DROP COLUMN [[{$column}]]
        SQL;
    }

    /**
     * {@inheritdoc}
     *
     * SQL Server does not support the `RECURSIVE` keyword for CTEs. Recursion is implicit when a CTE references itself.
     */
    public function buildWithQueries($withs, &$params)
    {
        if ($withs === []) {
            return '';
        }

        $result = [];

        foreach ($withs as $with) {
            $query = $with['query'];

            if ($query instanceof Query) {
                [$with['query'], $params] = $this->build($query, $params);
            }

            $result[] = $with['alias'] . ' AS (' . $with['query'] . ')';
        }

        return 'WITH ' . implode(', ', $result);
    }
}
