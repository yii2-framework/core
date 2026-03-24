<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\db\sqlite;

use Yii;
use yii\base\NotSupportedException;
use yii\db\CheckConstraint;
use yii\db\Constraint;
use yii\db\ConstraintFinderInterface;
use yii\db\ConstraintFinderTrait;
use yii\db\ForeignKeyConstraint;
use yii\db\IndexConstraint;
use yii\db\MetadataType;
use yii\db\SqlToken;
use yii\db\TableSchema;
use yii\helpers\ArrayHelper;
use yii\db\Schema as BaseSchema;

use function count;
use function is_string;
use function str_contains;
use function str_replace;
use function strncasecmp;
use function strncmp;
use function strtolower;

/**
 * Schema is the class for retrieving metadata from a SQLite 3 database.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 *
 * @template T of ColumnSchema = ColumnSchema
 * @extends BaseSchema<T>
 */
class Schema extends BaseSchema implements ConstraintFinderInterface
{
    use ConstraintFinderTrait;

    public $columnSchemaClass = ColumnSchema::class;

    /**
     * @var array Mapping from physical column types (keys) to abstract column types (values).
     */
    public $typeMap = [
        'tinyint' => self::TYPE_TINYINT,
        'bit' => self::TYPE_SMALLINT,
        'boolean' => self::TYPE_BOOLEAN,
        'bool' => self::TYPE_BOOLEAN,
        'smallint' => self::TYPE_SMALLINT,
        'mediumint' => self::TYPE_INTEGER,
        'int' => self::TYPE_INTEGER,
        'integer' => self::TYPE_INTEGER,
        'bigint' => self::TYPE_BIGINT,
        'float' => self::TYPE_FLOAT,
        'double' => self::TYPE_DOUBLE,
        'real' => self::TYPE_FLOAT,
        'decimal' => self::TYPE_DECIMAL,
        'numeric' => self::TYPE_DECIMAL,
        'tinytext' => self::TYPE_TEXT,
        'mediumtext' => self::TYPE_TEXT,
        'longtext' => self::TYPE_TEXT,
        'text' => self::TYPE_TEXT,
        'varchar' => self::TYPE_STRING,
        'string' => self::TYPE_STRING,
        'char' => self::TYPE_CHAR,
        'blob' => self::TYPE_BINARY,
        'datetime' => self::TYPE_DATETIME,
        'year' => self::TYPE_DATE,
        'date' => self::TYPE_DATE,
        'time' => self::TYPE_TIME,
        'timestamp' => self::TYPE_TIMESTAMP,
        'enum' => self::TYPE_STRING,
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
     *
     * @see https://www.sqlite.org/schematab.html
     */
    protected function findTableNames($schema = '')
    {
        $sql = <<<SQL
            SELECT DISTINCT tbl_name
            FROM sqlite_schema
            WHERE tbl_name <> 'sqlite_sequence'
            ORDER BY tbl_name
            SQL;

        return $this->db->createCommand($sql)->queryColumn();
    }

    /**
     * {@inheritdoc}
     */
    protected function loadTableSchema($name)
    {
        $table = new TableSchema();
        $table->name = $name;
        $table->fullName = $name;

        if ($this->findColumns($table)) {
            $this->findConstraints($table);

            foreach ($table->columns as $column) {
                $column->defaultValue = $column->isPrimaryKey
                    ? null
                    : $column->defaultPhpTypecast($column->defaultValue);
            }

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
     *
     * @see https://www.sqlite.org/pragma.html#pragma_foreign_key_list
     */
    protected function loadTableForeignKeys($tableName)
    {
        $foreignKeys = $this->db->createCommand(
            'PRAGMA foreign_key_list(' . $this->quoteValue($tableName) . ')'
        )->queryAll();
        $foreignKeys = $this->normalizePdoRowKeyCase($foreignKeys, true);
        $foreignKeys = ArrayHelper::index($foreignKeys, null, 'table');
        ArrayHelper::multisort($foreignKeys, 'seq', SORT_ASC, SORT_NUMERIC);

        $result = [];

        foreach ($foreignKeys as $table => $foreignKey) {
            $result[] = new ForeignKeyConstraint(
                [
                    'columnNames' => ArrayHelper::getColumn($foreignKey, 'from'),
                    'foreignTableName' => $table,
                    'foreignColumnNames' => ArrayHelper::getColumn($foreignKey, 'to'),
                    'onDelete' => $foreignKey[0]['on_delete'] ?? null,
                    'onUpdate' => $foreignKey[0]['on_update'] ?? null,
                ],
            );
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function loadTableIndexes($tableName)
    {
        return $this->loadTableConstraints($tableName, MetadataType::INDEXES);
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
     *
     * @see https://www.sqlite.org/schematab.html
     */
    protected function loadTableChecks($tableName)
    {
        $sql = $this->db->createCommand(
            <<<SQL
            SELECT `sql`
            FROM `sqlite_schema`
            WHERE name = :tableName
            SQL,
            [':tableName' => $tableName],
        )->queryScalar();

        /** @var SqlToken[]|SqlToken[][]|SqlToken[][][] $code */
        $code = (new SqlTokenizer($sql))->tokenize();
        $pattern = (new SqlTokenizer('any CREATE any TABLE any()'))->tokenize();

        if (!$code[0]->matches($pattern, 0, $firstMatchIndex, $lastMatchIndex)) {
            return [];
        }

        $createTableToken = $code[0][$lastMatchIndex - 1];
        $result = [];
        $offset = 0;

        while (true) {
            $pattern = (new SqlTokenizer('any CHECK()'))->tokenize();

            if (!$createTableToken->matches($pattern, $offset, $firstMatchIndex, $offset)) {
                break;
            }

            $checkSql = $createTableToken[$offset - 1]->getSql();

            $name = null;

            $pattern = (new SqlTokenizer('CONSTRAINT any'))->tokenize();

            if (
                isset($createTableToken[$firstMatchIndex - 2])
                && $createTableToken->matches($pattern, $firstMatchIndex - 2)
            ) {
                $name = $createTableToken[$firstMatchIndex - 1]->content;
            }

            $result[] = new CheckConstraint([
                'name' => $name,
                'expression' => $checkSql,
            ]);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @throws NotSupportedException if this method is called.
     */
    protected function loadTableDefaultValues($tableName)
    {
        throw new NotSupportedException('SQLite does not support default value constraints.');
    }

    /**
     * Creates a query builder for the SQLite database.
     * This method may be overridden by child classes to create a DBMS-specific query builder.
     *
     * @return QueryBuilder Query builder instance.
     */
    public function createQueryBuilder()
    {
        return Yii::createObject(QueryBuilder::class, [$this->db]);
    }

    /**
     * {@inheritdoc}
     *
     * @return ColumnSchemaBuilder Column schema builder instance.
     */
    public function createColumnSchemaBuilder($type, $length = null)
    {
        return Yii::createObject(ColumnSchemaBuilder::class, [$type, $length]);
    }

    /**
     * Collects the metadata of table columns.
     *
     * @param TableSchema $table The table metadata.
     *
     * @return bool Whether the table exists in the database.
     *
     * @see https://www.sqlite.org/pragma.html#pragma_table_info
     */
    protected function findColumns($table)
    {
        $sql = 'PRAGMA table_info(' . $this->quoteSimpleTableName($table->name) . ')';
        $columns = $this->db->createCommand($sql)->queryAll();

        if (empty($columns)) {
            return false;
        }

        foreach ($columns as $info) {
            $column = $this->loadColumnSchema($info);
            $table->columns[$column->name] = $column;
            if ($column->isPrimaryKey) {
                $table->primaryKey[] = $column->name;
            }
        }
        if (
            count($table->primaryKey) === 1
            && !strncasecmp($table->columns[$table->primaryKey[0]]->dbType, 'int', 3)
        ) {
            $table->sequenceName = '';
            $table->columns[$table->primaryKey[0]]->autoIncrement = true;
        }

        return true;
    }

    /**
     * Collects the foreign key column details for the given table.
     *
     * @param TableSchema $table The table metadata.
     *
     * @see https://www.sqlite.org/pragma.html#pragma_foreign_key_list
     */
    protected function findConstraints($table)
    {
        $sql = 'PRAGMA foreign_key_list(' . $this->quoteSimpleTableName($table->name) . ')';
        $keys = $this->db->createCommand($sql)->queryAll();

        foreach ($keys as $key) {
            $id = (int) $key['id'];

            if (!isset($table->foreignKeys[$id])) {
                $table->foreignKeys[$id] = [$key['table'], $key['from'] => $key['to']];
            } else {
                // composite FK
                $table->foreignKeys[$id][$key['from']] = $key['to'];
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
     * @return array All unique indexes for the given table.
     *
     * @see https://www.sqlite.org/pragma.html#pragma_index_list
     * @see https://www.sqlite.org/pragma.html#pragma_index_info
     */
    public function findUniqueIndexes($table)
    {
        $sql = 'PRAGMA index_list(' . $this->quoteSimpleTableName($table->name) . ')';
        $indexes = $this->db->createCommand($sql)->queryAll();

        $uniqueIndexes = [];

        foreach ($indexes as $index) {
            $indexName = $index['name'];
            $indexInfo = $this->db->createCommand(
                'PRAGMA index_info(' . $this->quoteValue($index['name']) . ')',
            )->queryAll();

            if ($index['unique']) {
                $uniqueIndexes[$indexName] = [];

                foreach ($indexInfo as $row) {
                    $uniqueIndexes[$indexName][] = $row['name'];
                }
            }
        }

        return $uniqueIndexes;
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

        $column->allowNull = !$info['notnull'];
        $column->dbType = strtolower($info['type']);
        // store raw default for deferred resolution in `loadTableSchema()`.
        $column->defaultValue = $info['dflt_value'];
        $column->isPrimaryKey = $info['pk'] != 0;
        $column->name = $info['name'];
        $column->unsigned = str_contains($column->dbType, 'unsigned');

        $type = strtolower($column->extractSizeFromDbType());

        $column->type = $this->typeMap[$type] ?? self::TYPE_STRING;

        $column->resolveType($type);

        $column->phpType = $column->resolvePhpType();

        return $column;
    }

    /**
     * Returns table columns info.
     *
     * @param string $tableName Table name.
     *
     * @return array Table columns information.
     *
     * @see https://www.sqlite.org/pragma.html#pragma_table_info
     */
    private function loadTableColumnsInfo($tableName)
    {
        $tableColumns = $this->db->createCommand(
            'PRAGMA table_info(' . $this->quoteValue($tableName) . ')',
        )->queryAll();
        $tableColumns = $this->normalizePdoRowKeyCase($tableColumns, true);

        return ArrayHelper::index($tableColumns, 'cid');
    }

    /**
     * Loads multiple types of constraints and returns the specified ones.
     *
     * @param string $tableName Table name.
     * @param MetadataType $returnType Return type.
     *
     * @return mixed Constraints.
     *
     * @see https://www.sqlite.org/pragma.html#pragma_index_list
     * @see https://www.sqlite.org/pragma.html#pragma_index_info
     * @see https://www.sqlite.org/lang_createtable.html#primkeyconst
     */
    private function loadTableConstraints(string $tableName, MetadataType $returnType): mixed
    {
        $indexes = $this->db->createCommand(
            'PRAGMA index_list(' . $this->quoteValue($tableName) . ')',
        )->queryAll();
        $indexes = $this->normalizePdoRowKeyCase($indexes, true);

        $tableColumns = null;

        if (!empty($indexes) && !isset($indexes[0]['origin'])) {
            /*
             * SQLite may not have an "origin" column in index_list
             * See https://www.sqlite.org/src/info/2743846cdba572f6
             */
            $tableColumns = $this->loadTableColumnsInfo($tableName);
        }

        $result = [
            MetadataType::INDEXES->value => [],
            MetadataType::PRIMARY_KEY->value => null,
            MetadataType::UNIQUES->value => [],
        ];

        foreach ($indexes as $index) {
            $columns = $this->db->createCommand(
                'PRAGMA index_info(' . $this->quoteValue($index['name']) . ')'
            )->queryAll();

            $columns = $this->normalizePdoRowKeyCase($columns, true);

            ArrayHelper::multisort($columns, 'seqno', SORT_ASC, SORT_NUMERIC);

            if ($tableColumns !== null) {
                // SQLite may not have an "origin" column in index_list
                $index['origin'] = 'c';
                if (!empty($columns) && $tableColumns[$columns[0]['cid']]['pk'] > 0) {
                    $index['origin'] = 'pk';
                } elseif ($index['unique'] && $this->isSystemIdentifier($index['name'])) {
                    $index['origin'] = 'u';
                }
            }

            $result[MetadataType::INDEXES->value][] = new IndexConstraint(
                [
                    'isPrimary' => $index['origin'] === 'pk',
                    'isUnique' => (bool) $index['unique'],
                    'name' => $index['name'],
                    'columnNames' => ArrayHelper::getColumn($columns, 'name'),
                ],
            );

            if ($index['origin'] === 'u') {
                $result[MetadataType::UNIQUES->value][] = new Constraint(
                    [
                        'name' => $index['name'],
                        'columnNames' => ArrayHelper::getColumn($columns, 'name'),
                    ],
                );
            } elseif ($index['origin'] === 'pk') {
                $result[MetadataType::PRIMARY_KEY->value] = new Constraint(
                    ['columnNames' => ArrayHelper::getColumn($columns, 'name')],
                );
            }
        }

        if ($result[MetadataType::PRIMARY_KEY->value] === null) {
            /*
             * Additional check for PK in case of INTEGER PRIMARY KEY with ROWID
             * See https://www.sqlite.org/lang_createtable.html#primkeyconst
             */
            if ($tableColumns === null) {
                $tableColumns = $this->loadTableColumnsInfo($tableName);
            }

            foreach ($tableColumns as $tableColumn) {
                if ($tableColumn['pk'] > 0) {
                    $result[MetadataType::PRIMARY_KEY->value] = new Constraint(
                        ['columnNames' => [$tableColumn['name']]],
                    );
                    break;
                }
            }
        }

        return $this->cacheAndReturnConstraints($tableName, $result, $returnType);
    }

    /**
     * Return whether the specified identifier is a SQLite system identifier.
     *
     * @param string $identifier The identifier to check.
     *
     * @return bool Whether the identifier is a SQLite system identifier.
     *
     * @see https://www.sqlite.org/src/artifact/74108007d286232f
     */
    private function isSystemIdentifier($identifier)
    {
        return strncmp($identifier, 'sqlite_', 7) === 0;
    }

    /**
     * {@inheritdoc}
     *
     * Since PHP 8.5, `PDO::quote()` throws a ValueError when the string contains null bytes ("\0").
     *
     * This method sanitizes such bytes before calling the parent implementation to avoid exceptions while maintaining
     * backward compatibility.
     *
     * @link https://github.com/php/php-src/commit/0a10f6db26875e0f1d0f867307cee591d29a43c7
     */
    public function quoteValue($value)
    {
        if (PHP_VERSION_ID >= 80500 && is_string($value) && str_contains($value, "\0")) {
            // sanitize `null` bytes to prevent PDO ValueError on PHP `8.5+`
            $value = str_replace("\0", '', $value);
        }

        return parent::quoteValue($value);
    }
}
