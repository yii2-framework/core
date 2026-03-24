<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\db\mssql;

use Yii;
use yii\db\CheckConstraint;
use yii\db\Constraint;
use yii\db\ConstraintFinderInterface;
use yii\db\ConstraintFinderTrait;
use yii\db\DefaultValueConstraint;
use yii\db\ForeignKeyConstraint;
use yii\db\IndexConstraint;
use yii\db\MetadataType;
use yii\db\ViewFinderTrait;
use yii\helpers\ArrayHelper;

use function count;
use function is_array;

/**
 * Schema is the class for retrieving metadata from MS SQL Server databases (version 2017 and later).
 *
 * @author Timur Ruziev <resurtm@gmail.com>
 * @since 2.0
 *
 * @template T of ColumnSchema = ColumnSchema
 * @extends \yii\db\Schema<T>
 */
class Schema extends \yii\db\Schema implements ConstraintFinderInterface
{
    use ViewFinderTrait;
    use ConstraintFinderTrait;

    /**
     * {@inheritdoc}
     */
    public $columnSchemaClass = ColumnSchema::class;
    /**
     * @var string The default schema used for the current session.
     */
    public $defaultSchema = 'dbo';
    /**
     * @var array Mapping from physical column types (keys) to abstract column types (values).
     */
    public $typeMap = [
        // exact numbers
        'bigint' => self::TYPE_BIGINT,
        'numeric' => self::TYPE_DECIMAL,
        'bit' => self::TYPE_BOOLEAN,
        'smallint' => self::TYPE_SMALLINT,
        'decimal' => self::TYPE_DECIMAL,
        'smallmoney' => self::TYPE_MONEY,
        'int' => self::TYPE_INTEGER,
        'tinyint' => self::TYPE_TINYINT,
        'money' => self::TYPE_MONEY,
        // approximate numbers
        'float' => self::TYPE_FLOAT,
        'double' => self::TYPE_DOUBLE,
        'real' => self::TYPE_FLOAT,
        // date and time
        'date' => self::TYPE_DATE,
        'datetimeoffset' => self::TYPE_DATETIME,
        'datetime2' => self::TYPE_DATETIME,
        'smalldatetime' => self::TYPE_DATETIME,
        'datetime' => self::TYPE_DATETIME,
        'time' => self::TYPE_TIME,
        // character strings
        'char' => self::TYPE_CHAR,
        'varchar' => self::TYPE_STRING,
        'text' => self::TYPE_TEXT,
        // unicode character strings
        'nchar' => self::TYPE_CHAR,
        'nvarchar' => self::TYPE_STRING,
        'ntext' => self::TYPE_TEXT,
        // binary strings
        'binary' => self::TYPE_BINARY,
        'varbinary' => self::TYPE_BINARY,
        'image' => self::TYPE_BINARY,
        // other data types
        // 'cursor' type cannot be used with tables
        'timestamp' => self::TYPE_TIMESTAMP,
        'hierarchyid' => self::TYPE_STRING,
        'uniqueidentifier' => self::TYPE_STRING,
        'sql_variant' => self::TYPE_STRING,
        'xml' => self::TYPE_STRING,
        'table' => self::TYPE_STRING,
    ];

    /**
     * {@inheritdoc}
     */
    protected $tableQuoteCharacter = ['[', ']'];
    /**
     * {@inheritdoc}
     */
    protected $columnQuoteCharacter = ['[', ']'];

    /**
     * {@inheritdoc}
     */
    protected function resolveTableName($name)
    {
        $parts = $this->getTableNameParts($name);

        $partCount = count($parts);

        $last = $partCount - 1;
        $penultimate = $partCount - 2;
        $catalogIndex = $partCount === 4 ? 1 : 0;

        $tableName = $parts[$last];
        $schemaName = $partCount >= 2 ? $parts[$penultimate] : $this->defaultSchema;
        $catalogName = $partCount >= 3 ? $parts[$catalogIndex] : null;

        $fullName = match (true) {
            $catalogName !== null => "{$catalogName}.{$schemaName}.{$tableName}",
            $schemaName !== $this->defaultSchema => "{$schemaName}.{$tableName}",
            default => $tableName,
        };

        $resolvedName = new TableSchema();

        $resolvedName->name = $tableName;
        $resolvedName->schemaName = $schemaName;
        $resolvedName->catalogName = $catalogName;
        $resolvedName->fullName = $fullName;

        return $resolvedName;
    }

    /**
     * {@inheritDoc}
     *
     * @param string $name Table name.
     *
     * @return array Table name parts.
     *
     * @since 2.0.22
     */
    protected function getTableNameParts($name)
    {
        $parts = [$name];

        preg_match_all('/([^.\[\]]+)|\[([^\[\]]+)\]/', $name, $matches);

        if (isset($matches[0]) && is_array($matches[0]) && !empty($matches[0])) {
            $parts = $matches[0];
        }

        return str_replace(['[', ']'], '', $parts);
    }

    /**
     * {@inheritdoc}
     *
     * @see https://docs.microsoft.com/en-us/sql/relational-databases/system-catalog-views/sys-database-principals-transact-sql
     */
    protected function findSchemaNames()
    {
        $sql = <<<SQL
        SELECT [s].[name]
        FROM [sys].[schemas] AS [s]
        INNER JOIN [sys].[database_principals] AS [p] ON [p].[principal_id] = [s].[principal_id]
        WHERE [p].[is_fixed_role] = 0 AND [p].[sid] IS NOT NULL
        ORDER BY [s].[name] ASC
        SQL;

        return $this->db->createCommand($sql)->queryColumn();
    }

    /**
     * {@inheritdoc}
     *
     * @see https://learn.microsoft.com/en-us/sql/relational-databases/system-catalog-views/sys-objects-transact-sql
     */
    protected function findTableNames($schema = '')
    {
        if ($schema === '') {
            $schema = $this->defaultSchema;
        }

        $sql = <<<SQL
        SELECT [o].[name]
        FROM [sys].[objects] AS [o]
        INNER JOIN [sys].[schemas] AS [s] ON [s].[schema_id] = [o].[schema_id]
        WHERE [s].[name] = :schema
            AND [o].[type] IN ('U', 'V')
        ORDER BY [o].[name]
        SQL;

        return $this->db->createCommand($sql, [':schema' => $schema])->queryColumn();
    }

    /**
     * {@inheritdoc}
     */
    protected function loadTableSchema($name)
    {
        $table = $this->resolveTableName($name);

        $this->findPrimaryKeys($table);

        if ($this->findColumns($table)) {
            $this->findForeignKeys($table);

            return $table;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     *
     * Overridden to bracket-quote table names returned by {@see getTableNames()}, preventing
     * {@see resolveTableName()} from splitting dot-containing names (e.g., `with.special.characters`)
     * into catalog/schema/table parts.
     */
    protected function getSchemaMetadata(string $schema, MetadataType $type, bool $refresh)
    {
        $metadata = [];

        foreach ($this->getTableNames($schema, $refresh) as $name) {
            $quotedName = $this->quoteSimpleTableName($name);

            if ($schema !== '') {
                $quotedName = "{$schema}.{$quotedName}";
            }

            $tableMetadata = $this->getTableMetadata($quotedName, $type, $refresh);

            if ($tableMetadata !== null) {
                $metadata[] = $tableMetadata;
            }
        }

        return $metadata;
    }

    /**
     * {@inheritdoc}
     */
    protected function loadTablePrimaryKey($tableName)
    {
        return $this->loadTableConstraints($tableName, MetadataType::PRIMARY_KEY);
    }

    /**
     * {@inheritdoc}
     */
    protected function loadTableForeignKeys($tableName)
    {
        return $this->loadTableConstraints($tableName, MetadataType::FOREIGN_KEYS);
    }

    /**
     * {@inheritdoc}
     */
    protected function loadTableIndexes($tableName)
    {
        $resolvedName = $this->resolveTableName($tableName);
        [$fullName, $catalogPrefix] = $this->buildQuotedTableParts($resolvedName);

        $sql = <<<SQL
        SELECT
            [i].[name] AS [name],
            [iccol].[name] AS [column_name],
            [i].[is_unique] AS [index_is_unique],
            [i].[is_primary_key] AS [index_is_primary]
        FROM {$catalogPrefix}[sys].[indexes] AS [i]
        INNER JOIN {$catalogPrefix}[sys].[index_columns] AS [ic]
            ON [ic].[object_id] = [i].[object_id] AND [ic].[index_id] = [i].[index_id]
        INNER JOIN {$catalogPrefix}[sys].[columns] AS [iccol]
            ON [iccol].[object_id] = [ic].[object_id] AND [iccol].[column_id] = [ic].[column_id]
        WHERE [i].[object_id] = OBJECT_ID(:fullName)
        ORDER BY [ic].[key_ordinal] ASC
        SQL;

        $indexes = $this->db->createCommand($sql, [':fullName' => $fullName])->queryAll();
        $indexes = $this->normalizePdoRowKeyCase($indexes, true);
        $indexes = ArrayHelper::index($indexes, null, 'name');

        $result = [];

        foreach ($indexes as $name => $index) {
            $result[] = new IndexConstraint(
                [
                    'isPrimary' => (bool) $index[0]['index_is_primary'],
                    'isUnique' => (bool) $index[0]['index_is_unique'],
                    'name' => $name,
                    'columnNames' => ArrayHelper::getColumn($index, 'column_name'),
                ],
            );
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function loadTableUniques($tableName)
    {
        return $this->loadTableConstraints($tableName, MetadataType::UNIQUES);
    }

    /**
     * {@inheritdoc}
     */
    protected function loadTableChecks($tableName)
    {
        return $this->loadTableConstraints($tableName, MetadataType::CHECKS);
    }

    /**
     * {@inheritdoc}
     */
    protected function loadTableDefaultValues($tableName)
    {
        return $this->loadTableConstraints($tableName, MetadataType::DEFAULT_VALUES);
    }

    /**
     * Creates a query builder for the MSSQL database.
     *
     * @return QueryBuilder Query builder instance.
     */
    public function createQueryBuilder()
    {
        return Yii::createObject(QueryBuilder::class, [$this->db]);
    }

    /**
     * Loads the column information into a [[ColumnSchema]] object.
     *
     * @param array $info Column information.
     *
     * @return T The column schema object.
     */
    protected function loadColumnSchema($info)
    {
        $column = $this->createColumnSchema();

        $column->allowNull = $info['is_nullable'] === 'YES';
        $column->autoIncrement = $info['is_identity'] == 1;
        $column->comment = $info['comment'] ?? '';
        $column->dbType = $info['data_type'];
        // store raw default for deferred resolution in `findColumns()`, where `isPrimaryKey` is known
        $column->defaultValue = $info['column_default'];
        // mssql has only vague equivalents to enum
        $column->enumValues = [];
        $column->isComputed = (bool) $info['is_computed'];
        // primary key will be determined in `findColumns()` method
        $column->isPrimaryKey = false;
        $column->name = $info['column_name'];
        $column->unsigned = stripos($column->dbType, 'unsigned') !== false;

        $type = $column->extractSizeFromDbType();

        $column->type = $this->typeMap[$type] ?? self::TYPE_STRING;

        $column->phpType = $column->resolvePhpType();

        return $column;
    }

    /**
     * Collects the metadata of table columns.
     *
     * @param TableSchema $table The table metadata.
     *
     * @return bool Whether the table exists in the database.
     */
    protected function findColumns($table)
    {
        [$fullName, $catalogPrefix] = $this->buildQuotedTableParts($table);

        $sql = <<<SQL
        SELECT
            [c].[name] AS [column_name],
            CASE WHEN [c].[is_nullable] = 1 THEN 'YES' ELSE 'NO' END AS [is_nullable],
            CASE
                WHEN [t].[name] IN ('char','varchar','nchar','nvarchar','binary','varbinary') THEN
                    CASE
                        WHEN [c].[max_length] = -1 AND [t].[name] IN ('varchar','nvarchar','varbinary') THEN
                            [t].[name] + '(max)'
                        WHEN [t].[name] IN ('nchar','nvarchar') THEN
                            [t].[name] + '(' + CAST([c].[max_length] / 2 AS VARCHAR) + ')'
                        ELSE
                            [t].[name] + '(' + CAST([c].[max_length] AS VARCHAR) + ')'
                    END
                WHEN [t].[name] IN ('decimal','numeric') THEN
                    [t].[name] + '(' + CAST([c].[precision] AS VARCHAR) + ',' + CAST([c].[scale] AS VARCHAR) + ')'
                ELSE [t].[name]
            END AS [data_type],
            [dc].[definition] AS [column_default],
            [c].[is_identity],
            [c].[is_computed],
            CAST([ep].[value] AS NVARCHAR(MAX)) AS [comment]
        FROM {$catalogPrefix}[sys].[columns] AS [c]
        INNER JOIN {$catalogPrefix}[sys].[types] AS [t]
            ON [c].[system_type_id] = [t].[system_type_id]
            AND [t].[user_type_id] = [t].[system_type_id]
        LEFT JOIN {$catalogPrefix}[sys].[default_constraints] AS [dc]
            ON [dc].[parent_object_id] = [c].[object_id]
            AND [dc].[parent_column_id] = [c].[column_id]
        LEFT JOIN {$catalogPrefix}[sys].[extended_properties] AS [ep]
            ON [ep].[major_id] = [c].[object_id]
            AND [ep].[minor_id] = [c].[column_id]
            AND [ep].[class] = 1
            AND [ep].[name] = 'MS_Description'
        WHERE [c].[object_id] = OBJECT_ID(:fullName)
        ORDER BY [c].[column_id]
        SQL;

        // no try/catch needed: `OBJECT_ID(:fullName)` returns `NULL` for non-existent tables ('0' rows, no exception),
        // and `loadTableSchema()` calls `findPrimaryKeys()` before this method, which validates the connection first.
        $columns = $this->db->createCommand($sql, [':fullName' => $fullName])->queryAll();

        if (empty($columns)) {
            return false;
        }

        foreach ($columns as $column) {
            $column = $this->loadColumnSchema($column);

            foreach ($table->primaryKey as $primaryKey) {
                if (strcasecmp($column->name, $primaryKey) === 0) {
                    $column->isPrimaryKey = true;

                    break;
                }
            }

            if ($column->isPrimaryKey && $column->autoIncrement) {
                $table->sequenceName = '';
            }

            $column->defaultValue = $column->isPrimaryKey
                ? null
                : $column->defaultPhpTypecast($column->defaultValue);

            $table->columns[$column->name] = $column;
        }

        return true;
    }

    /**
     * Collects the constraint details for the given table and constraint type.
     *
     * @param TableSchema $table The table metadata.
     * @param string $type Either `PK` or `UQ`.
     *
     * @return array Each entry contains index_name and field_name.
     *
     * @since 2.0.4
     *
     * @see https://learn.microsoft.com/en-us/sql/relational-databases/system-catalog-views/sys-key-constraints-transact-sql
     */
    protected function findTableConstraints($table, $type)
    {
        [$fullName, $catalogPrefix] = $this->buildQuotedTableParts($table);

        $sql = <<<SQL
        SELECT
            [kc].[name] AS [index_name],
            [col].[name] AS [field_name]
        FROM {$catalogPrefix}[sys].[key_constraints] AS [kc]
        INNER JOIN {$catalogPrefix}[sys].[index_columns] AS [ic]
            ON [ic].[object_id] = [kc].[parent_object_id]
            AND [ic].[index_id] = [kc].[unique_index_id]
        INNER JOIN {$catalogPrefix}[sys].[columns] AS [col]
            ON [col].[object_id] = [ic].[object_id]
            AND [col].[column_id] = [ic].[column_id]
        WHERE [kc].[parent_object_id] = OBJECT_ID(:fullName)
            AND [kc].[type] = :type
        ORDER BY [ic].[key_ordinal] ASC
        SQL;

        return $this->db->createCommand($sql, [':fullName' => $fullName, ':type' => $type])->queryAll();
    }

    /**
     * Collects the primary key column details for the given table.
     *
     * @param TableSchema $table The table metadata.
     */
    protected function findPrimaryKeys($table)
    {
        $result = [];

        foreach ($this->findTableConstraints($table, 'PK') as $row) {
            $result[] = $row['field_name'];
        }

        $table->primaryKey = $result;
    }

    /**
     * Collects the foreign key column details for the given table.
     *
     * @param TableSchema $table The table metadata.
     */
    protected function findForeignKeys($table)
    {
        [$fullName, $catalogPrefix, $dbIdExpr] = $this->buildQuotedTableParts($table);

        $sql = <<<SQL
        SELECT
            [fk].[name] AS [fk_name],
            [cp].[name] AS [fk_column_name],
            OBJECT_NAME([fk].[referenced_object_id], {$dbIdExpr}) AS [uq_table_name],
            [cr].[name] AS [uq_column_name]
        FROM {$catalogPrefix}[sys].[foreign_keys] AS [fk]
        INNER JOIN {$catalogPrefix}[sys].[foreign_key_columns] AS [fkc]
            ON [fk].[object_id] = [fkc].[constraint_object_id]
        INNER JOIN {$catalogPrefix}[sys].[columns] AS [cp]
            ON [fk].[parent_object_id] = [cp].[object_id]
            AND [fkc].[parent_column_id] = [cp].[column_id]
        INNER JOIN {$catalogPrefix}[sys].[columns] AS [cr]
            ON [fk].[referenced_object_id] = [cr].[object_id]
            AND [fkc].[referenced_column_id] = [cr].[column_id]
        WHERE [fk].[parent_object_id] = OBJECT_ID(:fullName)
        SQL;

        $rows = $this->db->createCommand($sql, [':fullName' => $fullName])->queryAll();

        $table->foreignKeys = [];

        foreach ($rows as $row) {
            if (!isset($table->foreignKeys[$row['fk_name']])) {
                $table->foreignKeys[$row['fk_name']][] = $row['uq_table_name'];
            }

            $table->foreignKeys[$row['fk_name']][$row['fk_column_name']] = $row['uq_column_name'];
        }
    }

    /**
     * {@inheritdoc}
     *
     * @see https://learn.microsoft.com/en-us/sql/relational-databases/system-catalog-views/sys-views-transact-sql
     */
    protected function findViewNames($schema = '')
    {
        if ($schema === '') {
            $schema = $this->defaultSchema;
        }

        $sql = <<<SQL
        SELECT [v].[name]
        FROM [sys].[views] AS [v]
        INNER JOIN [sys].[schemas] AS [s] ON [s].[schema_id] = [v].[schema_id]
        WHERE [s].[name] = :schema
        ORDER BY [v].[name]
        SQL;

        return $this->db->createCommand($sql, [':schema' => $schema])->queryColumn();
    }

    /**
     * Returns all unique indexes for the given table.
     *
     * Each array element is of the following structure:
     *
     * ```
     * [
     *     'IndexName1' => ['col1' [, ...]],
     *     'IndexName2' => ['col2' [, ...]],
     * ]
     * ```
     *
     * @param TableSchema $table The table metadata.
     *
     * @return array All unique indexes for the given table.
     *
     * @since 2.0.4
     */
    public function findUniqueIndexes($table)
    {
        $result = [];

        foreach ($this->findTableConstraints($table, 'UQ') as $row) {
            $result[$row['index_name']][] = $row['field_name'];
        }

        return $result;
    }

    /**
     * Builds a bracket-quoted fully-qualified table name, a catalog prefix for cross-database `sys.*` view queries, and
     * a `DB_ID()` expression for `OBJECT_NAME()`/`OBJECT_SCHEMA_NAME()` calls.
     *
     * @param TableSchema $resolvedName Object with name, schemaName, catalogName properties.
     *
     * @return array{0: string, 1: string, 2: string} [fullName, catalogPrefix, dbIdExpr]
     */
    private function buildQuotedTableParts($resolvedName): array
    {
        $fullName = $this->quoteSimpleTableName($resolvedName->name);

        if ($resolvedName->schemaName !== null) {
            $fullName = $this->quoteSimpleTableName($resolvedName->schemaName) . '.' . $fullName;
        }

        $catalogPrefix = '';
        $dbIdExpr = 'DB_ID()';

        if ($resolvedName->catalogName !== null) {
            $catalogPrefix = $this->quoteSimpleTableName($resolvedName->catalogName) . '.';
            $dbIdExpr = 'DB_ID(' . $this->db->quoteValue($resolvedName->catalogName) . ')';

            $fullName = "{$catalogPrefix}{$fullName}";
        }

        return [$fullName, $catalogPrefix, $dbIdExpr];
    }

    /**
     * Loads multiple types of constraints and returns the specified ones.
     *
     * @param string $tableName Table name.
     * @param MetadataType $returnType Return type.
     *
     * @return mixed Constraints.
     */
    private function loadTableConstraints(string $tableName, MetadataType $returnType): mixed
    {
        $resolvedName = $this->resolveTableName($tableName);
        [$fullName, $catalogPrefix, $dbIdExpr] = $this->buildQuotedTableParts($resolvedName);

        $sql = <<<SQL
        SELECT
            [o].[name] AS [name],
            COALESCE([ccol].[name], [dcol].[name], [fccol].[name], [kiccol].[name]) AS [column_name],
            RTRIM([o].[type]) AS [type],
            OBJECT_SCHEMA_NAME([f].[referenced_object_id], {$dbIdExpr}) AS [foreign_table_schema],
            OBJECT_NAME([f].[referenced_object_id], {$dbIdExpr}) AS [foreign_table_name],
            [ffccol].[name] AS [foreign_column_name],
            [f].[update_referential_action_desc] AS [on_update],
            [f].[delete_referential_action_desc] AS [on_delete],
            [c].[definition] AS [check_expr],
            [d].[definition] AS [default_expr]
        FROM (SELECT OBJECT_ID(:fullName) AS [object_id]) AS [t]
        INNER JOIN {$catalogPrefix}[sys].[objects] AS [o]
            ON [o].[parent_object_id] = [t].[object_id] AND [o].[type] IN ('PK', 'UQ', 'C', 'D', 'F')
        LEFT JOIN {$catalogPrefix}[sys].[check_constraints] AS [c]
            ON [c].[object_id] = [o].[object_id]
        LEFT JOIN {$catalogPrefix}[sys].[columns] AS [ccol]
            ON [ccol].[object_id] = [c].[parent_object_id] AND [ccol].[column_id] = [c].[parent_column_id]
        LEFT JOIN {$catalogPrefix}[sys].[default_constraints] AS [d]
            ON [d].[object_id] = [o].[object_id]
        LEFT JOIN {$catalogPrefix}[sys].[columns] AS [dcol]
            ON [dcol].[object_id] = [d].[parent_object_id] AND [dcol].[column_id] = [d].[parent_column_id]
        LEFT JOIN {$catalogPrefix}[sys].[key_constraints] AS [k]
            ON [k].[object_id] = [o].[object_id]
        LEFT JOIN {$catalogPrefix}[sys].[index_columns] AS [kic]
            ON [kic].[object_id] = [k].[parent_object_id] AND [kic].[index_id] = [k].[unique_index_id]
        LEFT JOIN {$catalogPrefix}[sys].[columns] AS [kiccol]
            ON [kiccol].[object_id] = [kic].[object_id] AND [kiccol].[column_id] = [kic].[column_id]
        LEFT JOIN {$catalogPrefix}[sys].[foreign_keys] AS [f]
            ON [f].[object_id] = [o].[object_id]
        LEFT JOIN {$catalogPrefix}[sys].[foreign_key_columns] AS [fc]
            ON [fc].[constraint_object_id] = [o].[object_id]
        LEFT JOIN {$catalogPrefix}[sys].[columns] AS [fccol]
            ON [fccol].[object_id] = [fc].[parent_object_id] AND [fccol].[column_id] = [fc].[parent_column_id]
        LEFT JOIN {$catalogPrefix}[sys].[columns] AS [ffccol]
            ON [ffccol].[object_id] = [fc].[referenced_object_id] AND [ffccol].[column_id] = [fc].[referenced_column_id]
        ORDER BY [kic].[key_ordinal] ASC, [fc].[constraint_column_id] ASC
        SQL;

        $constraints = $this->db->createCommand($sql, [':fullName' => $fullName])->queryAll();
        $constraints = $this->normalizePdoRowKeyCase($constraints, true);
        $constraints = ArrayHelper::index($constraints, null, ['type', 'name']);

        $result = [
            MetadataType::CHECKS->value => [],
            MetadataType::DEFAULT_VALUES->value => [],
            MetadataType::FOREIGN_KEYS->value => [],
            MetadataType::PRIMARY_KEY->value => null,
            MetadataType::UNIQUES->value => [],
        ];

        foreach ($constraints as $type => $names) {
            foreach ($names as $name => $constraint) {
                switch ($type) {
                    case 'PK':
                        $result[MetadataType::PRIMARY_KEY->value] = new Constraint(
                            [
                                'name' => $name,
                                'columnNames' => ArrayHelper::getColumn($constraint, 'column_name'),
                            ],
                        );
                        break;
                    case 'F':
                        $result[MetadataType::FOREIGN_KEYS->value][] = new ForeignKeyConstraint(
                            [
                                'name' => $name,
                                'columnNames' => ArrayHelper::getColumn($constraint, 'column_name'),
                                'foreignSchemaName' => $constraint[0]['foreign_table_schema'],
                                'foreignTableName' => $constraint[0]['foreign_table_name'],
                                'foreignColumnNames' => ArrayHelper::getColumn($constraint, 'foreign_column_name'),
                                'onDelete' => str_replace('_', '', $constraint[0]['on_delete']),
                                'onUpdate' => str_replace('_', '', $constraint[0]['on_update']),
                            ],
                        );
                        break;
                    case 'UQ':
                        $result[MetadataType::UNIQUES->value][] = new Constraint(
                            [
                                'name' => $name,
                                'columnNames' => ArrayHelper::getColumn($constraint, 'column_name'),
                            ],
                        );
                        break;
                    case 'C':
                        $result[MetadataType::CHECKS->value][] = new CheckConstraint(
                            [
                                'name' => $name,
                                'columnNames' => ArrayHelper::getColumn($constraint, 'column_name'),
                                'expression' => $constraint[0]['check_expr'],
                            ],
                        );
                        break;
                    case 'D':
                        $result[MetadataType::DEFAULT_VALUES->value][] = new DefaultValueConstraint(
                            [
                                'name' => $name,
                                'columnNames' => ArrayHelper::getColumn($constraint, 'column_name'),
                                'value' => $constraint[0]['default_expr'],
                            ],
                        );
                        break;
                }
            }
        }

        return $this->cacheAndReturnConstraints($tableName, $result, $returnType);
    }

    /**
     * {@inheritdoc}
     */
    public function quoteColumnName($name)
    {
        if (preg_match('/^\[.*\]$/', $name)) {
            return $name;
        }

        return parent::quoteColumnName($name);
    }

    /**
     * {@inheritdoc}
     *
     * Retrieves inserted data from a primary key request of type uniqueidentifier.
     */
    public function insert($table, $columns)
    {
        $command = $this->db->createCommand()->insert($table, $columns);

        if (!$command->execute()) {
            return false;
        }

        $inserted = $command->pdoStatement->fetch();
        $tableSchema = $this->getTableSchema($table);

        $result = [];

        foreach ($tableSchema->primaryKey as $name) {
            // @see https://github.com/yiisoft/yii2/issues/13828 & https://github.com/yiisoft/yii2/issues/17474
            if (isset($inserted[$name])) {
                $result[$name] = $inserted[$name];
            } elseif (isset($columns[$name])) {
                $result[$name] = $columns[$name];
            } else {
                $result[$name] = $tableSchema->columns[$name]->defaultValue;
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function createColumnSchemaBuilder($type, $length = null)
    {
        return Yii::createObject(ColumnSchemaBuilder::class, [$type, $length, $this->db]);
    }
}
