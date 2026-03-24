<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\db\mysql;

use Exception;
use PDO;
use PDOException;
use Yii;
use yii\base\NotSupportedException;
use yii\db\CheckConstraint;
use yii\db\Constraint;
use yii\db\ConstraintFinderInterface;
use yii\db\ConstraintFinderTrait;
use yii\db\ForeignKeyConstraint;
use yii\db\IndexConstraint;
use yii\db\MetadataType;
use yii\db\mysql\ColumnSchema;
use yii\db\TableSchema;
use yii\helpers\ArrayHelper;

use function in_array;

/**
 * Schema is the class for retrieving metadata from a MySQL database (version 8.0 and later).
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 *
 * @template T of ColumnSchema = ColumnSchema
 * @extends \yii\db\Schema<T>
 */
class Schema extends \yii\db\Schema implements ConstraintFinderInterface
{
    use ConstraintFinderTrait;

    /**
     * {@inheritdoc}
     */
    public $columnSchemaClass = ColumnSchema::class;
    /**
     * @var array Mapping from physical column types (keys) to abstract column types (values).
     */
    public $typeMap = [
        'tinyint' => self::TYPE_TINYINT,
        'bool' => self::TYPE_TINYINT,
        'boolean' => self::TYPE_TINYINT,
        'bit' => self::TYPE_INTEGER,
        'smallint' => self::TYPE_SMALLINT,
        'mediumint' => self::TYPE_INTEGER,
        'int' => self::TYPE_INTEGER,
        'integer' => self::TYPE_INTEGER,
        'bigint' => self::TYPE_BIGINT,
        'float' => self::TYPE_FLOAT,
        'double' => self::TYPE_DOUBLE,
        'double precision' => self::TYPE_DOUBLE,
        'real' => self::TYPE_FLOAT,
        'decimal' => self::TYPE_DECIMAL,
        'numeric' => self::TYPE_DECIMAL,
        'dec' => self::TYPE_DECIMAL,
        'fixed' => self::TYPE_DECIMAL,
        'tinytext' => self::TYPE_TEXT,
        'mediumtext' => self::TYPE_TEXT,
        'longtext' => self::TYPE_TEXT,
        'longblob' => self::TYPE_BINARY,
        'blob' => self::TYPE_BINARY,
        'text' => self::TYPE_TEXT,
        'varchar' => self::TYPE_STRING,
        'string' => self::TYPE_STRING,
        'char' => self::TYPE_CHAR,
        'datetime' => self::TYPE_DATETIME,
        'year' => self::TYPE_DATE,
        'date' => self::TYPE_DATE,
        'time' => self::TYPE_TIME,
        'timestamp' => self::TYPE_TIMESTAMP,
        'enum' => self::TYPE_STRING,
        'set' => self::TYPE_STRING,
        'binary' => self::TYPE_BINARY,
        'varbinary' => self::TYPE_BINARY,
        'json' => self::TYPE_JSON,
    ];

    /**
     * {@inheritdoc}
     */
    protected $tableQuoteCharacter = '`';
    /**
     * {@inheritdoc}
     */
    protected $columnQuoteCharacter = '`';

    /**
     * {@inheritdoc}
     */
    protected function resolveTableName($name)
    {
        $parts = explode('.', str_replace('`', '', $name));

        $tableName = $parts[1] ?? $parts[0];

        $schemaName = isset($parts[1]) ? $parts[0] : $this->defaultSchema;

        $fullName = $schemaName !== $this->defaultSchema
            ? "{$schemaName}.{$tableName}"
            : $tableName;

        $resolvedName = new TableSchema();

        $resolvedName->name = $tableName;
        $resolvedName->schemaName = $schemaName;
        $resolvedName->fullName = $fullName;

        return $resolvedName;
    }

    /**
     * {@inheritdoc}
     *
     * @see https://dev.mysql.com/doc/refman/8.0/en/show-tables.html
     */
    protected function findTableNames($schema = '')
    {
        $sql = 'SHOW TABLES';

        if ($schema !== '') {
            $sql .= ' FROM ' . $this->quoteSimpleTableName($schema);
        }

        return $this->db->createCommand($sql)->queryColumn();
    }

    /**
     * {@inheritdoc}
     */
    protected function loadTableSchema($name)
    {
        $table = $this->resolveTableName($name);

        if ($this->findColumns($table)) {
            $this->findConstraints($table);

            return $table;
        }

        return null;
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

        /** @see https://dev.mysql.com/doc/refman/8.0/en/information-schema-statistics-table.html */
        $sql = <<<SQL
        SELECT
            `s`.`INDEX_NAME` AS `name`,
            `s`.`COLUMN_NAME` AS `column_name`,
            `s`.`NON_UNIQUE` ^ 1 AS `index_is_unique`,
            `s`.`INDEX_NAME` = 'PRIMARY' AS `index_is_primary`
        FROM `information_schema`.`STATISTICS` AS `s`
        WHERE
            `s`.`TABLE_SCHEMA` = COALESCE(:schemaName, DATABASE())
            AND `s`.`INDEX_SCHEMA` = `s`.`TABLE_SCHEMA`
            AND `s`.`TABLE_NAME` = :tableName
        ORDER BY `s`.`SEQ_IN_INDEX` ASC
        SQL;

        $indexes = $this->db->createCommand(
            $sql,
            [
                ':schemaName' => $resolvedName->schemaName,
                ':tableName' => $resolvedName->name,
            ],
        )->queryAll();
        $indexes = $this->normalizePdoRowKeyCase($indexes, true);
        $indexes = ArrayHelper::index($indexes, null, 'name');

        $result = [];

        foreach ($indexes as $name => $index) {
            $result[] = new IndexConstraint(
                [
                    'isPrimary' => (bool) $index[0]['index_is_primary'],
                    'isUnique' => (bool) $index[0]['index_is_unique'],
                    'name' => $name !== 'PRIMARY' ? $name : null,
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
        $resolvedName = $this->resolveTableName($tableName);

        /** @see https://dev.mysql.com/doc/refman/8.0/en/information-schema-check-constraints-table.html */
        $sql = <<<SQL
        SELECT
            `cc`.`CONSTRAINT_NAME` AS `constraint_name`,
            `cc`.`CHECK_CLAUSE` AS `check_clause`
        FROM `information_schema`.`TABLE_CONSTRAINTS` AS `tc`
        INNER JOIN `information_schema`.`CHECK_CONSTRAINTS` AS `cc`
            ON `cc`.`CONSTRAINT_SCHEMA` = `tc`.`CONSTRAINT_SCHEMA`
            AND `cc`.`CONSTRAINT_NAME` = `tc`.`CONSTRAINT_NAME`
        WHERE
            `tc`.`TABLE_SCHEMA` = COALESCE(:schemaName, DATABASE())
            AND `tc`.`TABLE_NAME` = :tableName
            AND `tc`.`CONSTRAINT_TYPE` = 'CHECK'
        SQL;

        $tableRows = $this->db->createCommand(
            $sql,
            [
                ':schemaName' => $resolvedName->schemaName,
                ':tableName' => $resolvedName->name,
            ],
        )->queryAll();

        if ($tableRows === []) {
            return [];
        }

        $tableRows = $this->normalizePdoRowKeyCase($tableRows, true);

        $checks = [];

        foreach ($tableRows as $tableRow) {
            $checks[] = new CheckConstraint(
                [
                    'name' => $tableRow['constraint_name'],
                    'expression' => $tableRow['check_clause'],
                ],
            );
        }

        return $checks;
    }

    /**
     * {@inheritdoc}
     *
     * @throws NotSupportedException if this method is called.
     */
    protected function loadTableDefaultValues($tableName)
    {
        throw new NotSupportedException('MySQL does not support default value constraints.');
    }

    /**
     * Creates a query builder for the MySQL database.
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

        $column->allowNull = $info['null'] === 'YES';
        $column->autoIncrement = stripos($info['extra'], 'auto_increment') !== false;
        $column->comment = $info['comment'];
        $column->dbType = $info['type'];
        $column->isPrimaryKey = strpos($info['key'], 'PRI') !== false;
        $column->name = $info['field'];
        $column->unsigned = stripos($column->dbType, 'unsigned') !== false;

        $type = strtolower($column->extractSizeFromDbType());

        $column->type = $this->typeMap[$type] ?? self::TYPE_STRING;

        $column->resolveType($type);
        $column->phpType = $column->resolvePhpType();

        $column->defaultValue = $info['default'] ?? null;

        return $column;
    }

    /**
     * Collects the metadata of table columns.
     *
     * @param TableSchema $table The table metadata.
     *
     * @throws Exception if DB query fails.
     *
     * @return bool Whether the table exists in the database.
     */
    protected function findColumns($table)
    {
        /** @see https://dev.mysql.com/doc/refman/8.0/en/show-columns.html */
        $sql = 'SHOW FULL COLUMNS FROM ' . $this->quoteTableName($table->fullName);

        try {
            $columns = $this->db->createCommand($sql)->queryAll();
        } catch (Exception $e) {
            $previous = $e->getPrevious();

            if ($previous instanceof PDOException && strpos($previous->getMessage(), 'SQLSTATE[42S02') !== false) {
                // table does not exist
                // https://dev.mysql.com/doc/refman/8.0/en/error-messages-server.html#error_er_bad_table_error
                return false;
            }

            throw $e;
        }

        $jsonColumns = $this->getJsonColumns($table);

        foreach ($columns as $info) {
            if ($this->db->slavePdo->getAttribute(PDO::ATTR_CASE) !== PDO::CASE_LOWER) {
                $info = array_change_key_case($info, CASE_LOWER);
            }

            if (in_array($info['field'], $jsonColumns, true)) {
                $info['type'] = static::TYPE_JSON;
            }

            $column = $this->loadColumnSchema($info);

            if ($column->isPrimaryKey) {
                $table->primaryKey[] = $column->name;

                if ($column->autoIncrement) {
                    $table->sequenceName = '';
                }
            }

            $column->defaultValue = $column->isPrimaryKey && $column->autoIncrement
                ? null
                : $column->defaultPhpTypecast($column->defaultValue);

            $table->columns[$column->name] = $column;
        }

        return true;
    }

    /**
     * Gets the CREATE TABLE sql string.
     *
     * @param TableSchema $table The table metadata.
     *
     * @return string $sql The result of 'SHOW CREATE TABLE'.
     *
     * @see https://dev.mysql.com/doc/refman/8.0/en/show-create-table.html
     */
    protected function getCreateTableSql($table)
    {
        $row = $this->db->createCommand('SHOW CREATE TABLE ' . $this->quoteTableName($table->fullName))->queryOne();

        if (isset($row['Create Table'])) {
            $sql = $row['Create Table'];
        } else {
            $row = array_values($row);
            $sql = $row[1];
        }

        return $sql;
    }

    /**
     * Collects the foreign key column details for the given table.
     *
     * @param TableSchema $table The table metadata.
     *
     * @throws Exception if DB query fails.
     */
    protected function findConstraints($table)
    {
        /** @see https://dev.mysql.com/doc/refman/8.0/en/information-schema-referential-constraints-table.html */
        $sql = <<<SQL
        SELECT
            `kcu`.`CONSTRAINT_NAME` AS `constraint_name`,
            `kcu`.`COLUMN_NAME` AS `column_name`,
            `kcu`.`REFERENCED_TABLE_NAME` AS `referenced_table_name`,
            `kcu`.`REFERENCED_COLUMN_NAME` AS `referenced_column_name`
        FROM `information_schema`.`REFERENTIAL_CONSTRAINTS` AS `rc`
        INNER JOIN `information_schema`.`KEY_COLUMN_USAGE` AS `kcu`
            ON `kcu`.`CONSTRAINT_CATALOG` = `rc`.`CONSTRAINT_CATALOG`
            AND `kcu`.`CONSTRAINT_SCHEMA` = `rc`.`CONSTRAINT_SCHEMA`
            AND `kcu`.`CONSTRAINT_NAME` = `rc`.`CONSTRAINT_NAME`
        WHERE
            `rc`.`CONSTRAINT_SCHEMA` = COALESCE(:schemaName, DATABASE())
            AND `kcu`.`TABLE_SCHEMA` = COALESCE(:schemaName1, DATABASE())
            AND `rc`.`TABLE_NAME` = :tableName
            AND `kcu`.`TABLE_NAME` = :tableName1
        SQL;

        try {
            $rows = $this->db->createCommand(
                $sql,
                [
                    ':schemaName' => $table->schemaName,
                    ':schemaName1' => $table->schemaName,
                    ':tableName' => $table->name,
                    ':tableName1' => $table->name,
                ],
            )->queryAll();
            $rows = $this->normalizePdoRowKeyCase($rows, true);

            $constraints = [];

            foreach ($rows as $row) {
                $constraints[$row['constraint_name']]['referenced_table_name'] = $row['referenced_table_name'];
                $constraints[$row['constraint_name']]['columns'][$row['column_name']] = $row['referenced_column_name'];
            }

            $table->foreignKeys = [];

            foreach ($constraints as $name => $constraint) {
                $table->foreignKeys[$name] = [
                    $constraint['referenced_table_name'],
                    ...$constraint['columns']
                ];
            }
        } catch (Exception $e) {
            $previous = $e->getPrevious();

            if (!$previous instanceof PDOException || strpos($previous->getMessage(), 'SQLSTATE[42S02') === false) {
                throw $e;
            }

            // table does not exist, try to determine the foreign keys using the table creation sql
            $sql = $this->getCreateTableSql($table);

            $regexp = '/FOREIGN KEY\s+\(([^\)]+)\)\s+REFERENCES\s+([^\(^\s]+)\s*\(([^\)]+)\)/mi';

            if (preg_match_all($regexp, $sql, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $fks = array_map('trim', explode(',', str_replace(['`', '"'], '', $match[1])));
                    $pks = array_map('trim', explode(',', str_replace(['`', '"'], '', $match[3])));
                    $constraint = [str_replace(['`', '"'], '', $match[2])];

                    foreach ($fks as $k => $name) {
                        $constraint[$name] = $pks[$k];
                    }

                    $table->foreignKeys[md5(serialize($constraint))] = $constraint;
                }

                $table->foreignKeys = array_values($table->foreignKeys);
            }
        }
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
     */
    public function findUniqueIndexes($table)
    {
        $sql = $this->getCreateTableSql($table);

        $uniqueIndexes = [];

        $regexp = '/UNIQUE KEY\s+[`"](.+)[`"]\s*\(([`"].+[`"])+\)/mi';

        if (preg_match_all($regexp, $sql, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $indexName = $match[1];
                $indexColumns = array_map('trim', preg_split('/[`"],[`"]/', trim($match[2], '`"')));
                $uniqueIndexes[$indexName] = $indexColumns;
            }
        }

        return $uniqueIndexes;
    }

    /**
     * {@inheritdoc}
     */
    public function createColumnSchemaBuilder($type, $length = null)
    {
        return Yii::createObject(ColumnSchemaBuilder::class, [$type, $length, $this->db]);
    }

    /**
     * Loads multiple types of constraints and returns the specified ones.
     *
     * @param string $tableName The table name.
     * @param MetadataType $returnType Return type.
     *
     * @return mixed Constraints.
     */
    private function loadTableConstraints(string $tableName, MetadataType $returnType): mixed
    {
        $resolvedName = $this->resolveTableName($tableName);

        /**
         * @see https://dev.mysql.com/doc/refman/8.0/en/information-schema-key-column-usage-table.html
         * @see https://dev.mysql.com/doc/refman/8.0/en/information-schema-referential-constraints-table.html
         * @see https://dev.mysql.com/doc/refman/8.0/en/information-schema-table-constraints-table.html
         */
        $sql = <<<SQL
        SELECT
            `kcu`.`CONSTRAINT_NAME` AS `name`,
            `kcu`.`COLUMN_NAME` AS `column_name`,
            `tc`.`CONSTRAINT_TYPE` AS `type`,
            CASE
                WHEN :schemaName IS NULL AND `kcu`.`REFERENCED_TABLE_SCHEMA` = DATABASE() THEN NULL
                ELSE `kcu`.`REFERENCED_TABLE_SCHEMA`
            END AS `foreign_table_schema`,
            `kcu`.`REFERENCED_TABLE_NAME` AS `foreign_table_name`,
            `kcu`.`REFERENCED_COLUMN_NAME` AS `foreign_column_name`,
            `rc`.`UPDATE_RULE` AS `on_update`,
            `rc`.`DELETE_RULE` AS `on_delete`,
            `kcu`.`ORDINAL_POSITION` AS `position`
        FROM `information_schema`.`KEY_COLUMN_USAGE` AS `kcu`
        INNER JOIN `information_schema`.`REFERENTIAL_CONSTRAINTS` AS `rc`
            ON `rc`.`CONSTRAINT_SCHEMA` = `kcu`.`TABLE_SCHEMA`
            AND `rc`.`TABLE_NAME` = :tableName1
            AND `rc`.`CONSTRAINT_NAME` = `kcu`.`CONSTRAINT_NAME`
        INNER JOIN `information_schema`.`TABLE_CONSTRAINTS` AS `tc`
            ON `tc`.`TABLE_SCHEMA` = `kcu`.`TABLE_SCHEMA`
            AND `tc`.`TABLE_NAME` = :tableName2
            AND `tc`.`CONSTRAINT_NAME` = `kcu`.`CONSTRAINT_NAME`
            AND `tc`.`CONSTRAINT_TYPE` = 'FOREIGN KEY'
        WHERE
            `kcu`.`TABLE_SCHEMA` = COALESCE(:schemaName1, DATABASE())
            AND `kcu`.`CONSTRAINT_SCHEMA` = `kcu`.`TABLE_SCHEMA`
            AND `kcu`.`TABLE_NAME` = :tableName
        UNION
        SELECT
            `kcu`.`CONSTRAINT_NAME` AS `name`,
            `kcu`.`COLUMN_NAME` AS `column_name`,
            `tc`.`CONSTRAINT_TYPE` AS `type`,
            NULL AS `foreign_table_schema`,
            NULL AS `foreign_table_name`,
            NULL AS `foreign_column_name`,
            NULL AS `on_update`,
            NULL AS `on_delete`,
            `kcu`.`ORDINAL_POSITION` AS `position`
        FROM `information_schema`.`KEY_COLUMN_USAGE` AS `kcu`
        INNER JOIN `information_schema`.`TABLE_CONSTRAINTS` AS `tc`
            ON `tc`.`TABLE_SCHEMA` = `kcu`.`TABLE_SCHEMA`
            AND `tc`.`TABLE_NAME` = :tableName4
            AND `tc`.`CONSTRAINT_NAME` = `kcu`.`CONSTRAINT_NAME`
            AND `tc`.`CONSTRAINT_TYPE` IN ('PRIMARY KEY', 'UNIQUE')
        WHERE
            `kcu`.`TABLE_SCHEMA` = COALESCE(:schemaName2, DATABASE())
            AND `kcu`.`TABLE_NAME` = :tableName3
        ORDER BY `position` ASC
        SQL;

        $constraints = $this->db->createCommand(
            $sql,
            [
                ':schemaName' => $resolvedName->schemaName,
                ':schemaName1' => $resolvedName->schemaName,
                ':schemaName2' => $resolvedName->schemaName,
                ':tableName' => $resolvedName->name,
                ':tableName1' => $resolvedName->name,
                ':tableName2' => $resolvedName->name,
                ':tableName3' => $resolvedName->name,
                ':tableName4' => $resolvedName->name,
            ],
        )->queryAll();
        $constraints = $this->normalizePdoRowKeyCase($constraints, true);
        $constraints = ArrayHelper::index($constraints, null, ['type', 'name']);

        $result = [
            MetadataType::FOREIGN_KEYS->value => [],
            MetadataType::PRIMARY_KEY->value => null,
            MetadataType::UNIQUES->value => [],
        ];

        foreach ($constraints as $type => $names) {
            foreach ($names as $name => $constraint) {
                switch ($type) {
                    case 'PRIMARY KEY':
                        $result[MetadataType::PRIMARY_KEY->value] = new Constraint([
                            'columnNames' => ArrayHelper::getColumn($constraint, 'column_name'),
                        ]);
                        break;
                    case 'FOREIGN KEY':
                        $result[MetadataType::FOREIGN_KEYS->value][] = new ForeignKeyConstraint([
                            'name' => $name,
                            'columnNames' => ArrayHelper::getColumn($constraint, 'column_name'),
                            'foreignSchemaName' => $constraint[0]['foreign_table_schema'],
                            'foreignTableName' => $constraint[0]['foreign_table_name'],
                            'foreignColumnNames' => ArrayHelper::getColumn($constraint, 'foreign_column_name'),
                            'onDelete' => $constraint[0]['on_delete'],
                            'onUpdate' => $constraint[0]['on_update'],
                        ]);
                        break;
                    case 'UNIQUE':
                        $result[MetadataType::UNIQUES->value][] = new Constraint([
                            'name' => $name,
                            'columnNames' => ArrayHelper::getColumn($constraint, 'column_name'),
                        ]);
                        break;
                }
            }
        }

        return $this->cacheAndReturnConstraints($tableName, $result, $returnType);
    }

    /**
     * Returns column names that should be treated as JSON type.
     *
     * @param TableSchema $table The table metadata.
     *
     * @return array List of JSON column names.
     */
    private function getJsonColumns(TableSchema $table): array
    {
        $sql = $this->getCreateTableSql($table);

        $result = [];

        $regexp = '/json_valid\([\`"](.+)[\`"]\s*\)/mi';

        if (preg_match_all($regexp, $sql, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $result[] = $match[1];
            }
        }

        return $result;
    }
}
