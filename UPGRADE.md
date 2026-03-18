# Upgrade notes

## 0.1.0 Under development

### Yii autoloader removal

- `Yii::autoload()` has been removed.
- `Yii::$classMap` has been removed.
- Do not rely on runtime autoload mappings via Yii internals.
- Yii framework classes are now loaded by Composer using Yii package autoload rules (`autoload.psr-4` for `yii\\` and
  `autoload.classmap` for the global `Yii` class).
- Use Composer autoload configuration instead:
    - `autoload.psr-4` for namespace mapping.
    - `autoload.classmap` for explicit class-to-file overrides.
    - `autoload.exclude-from-classmap` when overriding vendor classes.
    - `autoload-dev` for development and test-only classes.
- Migration example:

```php
// before (runtime mapping in entry script)
Yii::$classMap['app\\helpers\\MyHelper'] = '@app/helpers/MyHelper.php';
```

```json
{
    "autoload": {
        "psr-4": {
            "app\\\\": "src/"
        }
    }
}
```

- Use `autoload.classmap` + `autoload.exclude-from-classmap` for vendor class overrides, and `autoload-dev` for
  development/test-only classes.
- If you change autoload configuration, regenerate autoload files with `composer dump-autoload`.

### ApcCache renamed to ApcuCache

The `\yii\caching\ApcCache` class has been renamed to `\yii\caching\ApcuCache`. The legacy APC extension (`ext-apc`) is
not available in PHP >= 8.0, so the dual-mode `$useApcu` property has been removed.

- Replace all references to `\yii\caching\ApcCache` with `\yii\caching\ApcuCache`.
- Remove any `'useApcu' => true` configuration, as it is no longer needed.
- Migration example:

```php
// before
'cache' => [
    'class' => '\yii\caching\ApcCache',
    'useApcu' => true,
],

// after
'cache' => [
    'class' => '\yii\caching\ApcuCache',
],
```

### CUBRID database driver removal

The CUBRID database driver has been removed from the framework.

### HHVM support removed

All HHVM-specific code has been removed from the framework. The framework targets PHP 8.1+ on the Zend engine only.

- `\yii\base\ErrorException::E_HHVM_FATAL_ERROR` constant has been removed.
- `\yii\base\ErrorHandler::handleHhvmError()` method has been removed.
- `\yii\base\ErrorHandler` no longer registers `handleHhvmError` as the error handler when `HHVM_VERSION` is defined.
- The `PhpManager` HHVM incompatibility note has been removed from its docblock.
- All test skips guarded by `defined('HHVM_VERSION')` have been removed.

If you referenced `\yii\base\ErrorException::E_HHVM_FATAL_ERROR` or `\yii\base\ErrorHandler::handleHhvmError()` in your application code,
remove those references.

### jQuery is now optional (strategy pattern)

jQuery is no longer hardcoded in validators and widgets. A new `Application::$useJquery` property (default: `true`)
controls whether jQuery-based client scripts are registered. When set to `false`, no jQuery assets are loaded and
`clientValidateAttribute()` returns `null` for all built-in validators.

**No action required** for existing applications. The default behavior is fully backward-compatible.

#### New interfaces

- `\yii\validators\client\ClientValidatorScriptInterface` strategy for validator client scripts.
- `\yii\web\client\ClientScriptInterface` strategy for widget client scripts.

#### New properties

- `\yii\base\Application::$useJquery` master switch for jQuery client scripts (default: `true`).
- `Validator::$clientScript` on all 13 validators that support client validation (`BooleanValidator`,
  `CompareValidator`, `EmailValidator`, `FileValidator`, `ImageValidator`, `IpValidator`, `NumberValidator`,
  `RangeValidator`, `RegularExpressionValidator`, `RequiredValidator`, `StringValidator`, `TrimValidator`,
  `UrlValidator`).
- `ActiveForm::$clientScript`, `GridView::$clientScript`, `CheckboxColumn::$clientScript` widget-level overrides.

#### New method

- `Validator::getFormattedClientMessage(string, array): string` public wrapper around the protected
  `formatMessage()`, used by extracted client script classes.

#### Opting out of jQuery

```php
// In application configuration
'useJquery' => false,
```

When `useJquery` is `false` and no custom `clientScript` strategy is configured:

- `clientValidateAttribute()` returns `null` on built-in jQuery-backed validators.
- `getClientOptions()` returns `[]` on built-in jQuery-backed validators.
- `ActiveForm`, `GridView`, and `CheckboxColumn` do not register the built-in jQuery plugins.
- No built-in `JqueryAsset`, `ValidationAsset`, `ActiveFormAsset`, or `GridViewAsset` bundles are registered.

> **Note:** Custom `clientScript` strategies are always instantiated regardless of `useJquery`.

#### Custom client script strategy

You can replace the jQuery implementation with a custom one by implementing the interfaces:

```php
// In a model's rules() method
public function rules()
{
    return [
        [
            'username',
            'required',
            'clientScript' => ['class' => '\app\validators\MyRequiredClientScript'],
        ],
    ];
}

// Custom form client script
ActiveForm::begin(['clientScript' => ['class' => '\app\widgets\MyFormClientScript']]);
```

### `InCondition` typed constructor and return types (#27)

`InCondition` now uses constructor promotion with explicit union types. All public methods declare return types.

If you instantiate `InCondition` directly, ensure the arguments match the new parameter types:

- `$column`: `array|string|ExpressionInterface|Traversable` (Traversable is normalized to array on `getColumn()`)
- `$operator`: `string`
- `$values`: `array|int|string|ExpressionInterface|Traversable` (Traversable is normalized to array on `getValues()`)

Return types added: `getOperator(): string`, `getColumn(): array|string|ExpressionInterface`,
`getValues(): array|int|string|ExpressionInterface`, `fromArrayDefinition(): static`.

> **Note:** `getColumn()` and `getValues()` now convert any `Traversable` (including Generators) to `array` on first
> access. Subsequent calls return the cached array. This means the return types no longer include `Traversable`.

### `InConditionBuilder` typed protected methods (#27)

All `protected` methods in `InConditionBuilder` now declare parameter types and return types. If you extend
`InConditionBuilder` and override any of the following methods, update your signatures to match:

| Method                        | New signature                                                                                                                    |
| ----------------------------- | -------------------------------------------------------------------------------------------------------------------------------- |
| `build()`                     | `build(ExpressionInterface $expression, array &$params = []): string`                                                            |
| `buildValues()`               | `buildValues(ConditionInterface $condition, array $values, array &$params): array`                                               |
| `buildSubqueryInCondition()`  | `buildSubqueryInCondition(string $operator, array\|string\|ExpressionInterface $columns, Query $values, array &$params): string` |
| `buildCompositeInCondition()` | `buildCompositeInCondition(string $operator, array $columns, array $values, array &$params): string`                             |
| `getNullCondition()`          | `getNullCondition(string $operator, string $column): string`                                                                     |
| `getNotEqualOperator()`       | `getNotEqualOperator(): string`                                                                                                  |

> **Note:** `getRawValuesFromTraversableObject()` has been removed. Traversable normalization now happens in
> `InCondition::getValues()`, so all values are arrays by the time they reach the builder.

### `oci\InConditionBuilder` typed `build()` and `splitCondition()` (`#27`)

`build()` now returns `string` explicitly. `splitCondition()` now returns `string|null` explicitly. If you extend the Oracle
builder, update your overrides accordingly.

### MySQL dead code removal and integer display width cleanup

The minimum supported MySQL version is now **8.0.19** (**MariaDB 10.5+**). The following dead code has been removed:

- `Schema::isOldMysql()` method and `$_oldMysql` property (checked for MySQL <= 5.1, never called).
- `QueryBuilder::supportsFractionalSeconds()` method (always `true` for MySQL 8.0+).
- `CacheInterface` / `DbCache` imports from `QueryBuilder` (only used by the removed method).
- Version checks for MySQL < 5.6 / < 5.6.4 / < 5.7 in tests.

**Integer display width** (`int(11)`, `bigint(20)`, `smallint(6)`, `tinyint(3)`) has been removed from the MySQL type
map. MySQL 8.0.17+ deprecated display width for integer types and emits deprecation warnings. The new defaults are:

| Before | After |
| --- | --- |
| `int(11)` | `int` |
| `int(10) UNSIGNED` | `int UNSIGNED` |
| `bigint(20)` | `bigint` |
| `bigint(20) UNSIGNED` | `bigint UNSIGNED` |
| `smallint(6)` | `smallint` |
| `tinyint(3)` | `tinyint` |

`tinyint(1)` for `TYPE_BOOLEAN` is preserved. MySQL uses it as the canonical boolean representation.

Explicit integer sizes (for example, `$this->primaryKey(8)`) are now ignored; display width is no longer emitted.

If your application or migrations rely on the exact SQL output of `QueryBuilder::getColumnType()` for integer types
(for example, in string assertions or snapshot tests), update the expected values.

### Composite `IN`/`NOT IN` conditions `IS NULL`/`IS NOT NULL` generation (`#27`)

Composite `IN`/`NOT IN` conditions now generate `IS NULL`/`IS NOT NULL` expressions for `NULL` values in the value list,
instead of literal `NULL` comparisons. This aligns with SQL semantics where `column = NULL` always evaluates to `UNKNOWN`.

**Before:** `(col1 = :p0 AND col2 = NULL)` always fails due to SQL NULL semantics
**After:** `(col1 = :p0 AND col2 IS NULL)` correctly matches NULL values

### MSSQL dead code removal, minimum SQL Server 2017

The minimum supported SQL Server version is now **2017** (internal version `14`). With PHP 8.2+, `pdo_sqlsrv 5.11+` is
required. SQL Server 2017 is the oldest version with official Docker images and Microsoft extended support (until October
2027). The following dead code has been removed:

- `QueryBuilder::isOldMssql()` method (deprecated since 2.0.14, checked for version < 11).
- `QueryBuilder::oldBuildOrderByAndLimit()` method (ROW_NUMBER()-based pagination for SQL Server 2005–2008).
- `QueryBuilder::newBuildOrderByAndLimit()` method (inlined into `buildOrderByAndLimit()`).
- SQL Server 2005 (`v9`) version checks in `QueryBuilder::insert()` and `Schema::insert()`.
- ROW_NUMBER() workaround in `QueryBuilder::upsert()`.
- The `getLastInsertID()` fallback in `Schema::insert()` for pre-2005 servers.
- Pre-2017 boolean type heuristics in `Schema::loadColumnSchema()` (`tinyint(1)` / `bit(n)` size-based mapping).
- The `$isVersion2017orLater` version check was removed; `bit` now maps directly to `boolean` in `Schema::$typeMap`.

**Type map change:** `Schema::$typeMap['bit']` changed from `TYPE_SMALLINT` to `TYPE_BOOLEAN`. If your application
reads `$schema->typeMap['bit']` directly, update accordingly.

If your application extends `\yii\db\mssql\QueryBuilder` and overrides `oldBuildOrderByAndLimit()`,
`newBuildOrderByAndLimit()`, or `isOldMssql()`, remove those overrides. The pagination logic now lives directly in
`buildOrderByAndLimit()` using the `OFFSET ... FETCH` syntax.

### Oracle modernization, minimum Oracle 19c

The minimum supported Oracle version is now **19c**. Oracle 19c is the only version with active Long-Term Support
(until December 2032). Oracle 11g, 12c, and 18c are all in Sustaining Support (EOL, no patches).

**Pagination:** The pre-12c `ROWNUM`/CTE pagination pattern has been replaced with standard `OFFSET x ROWS FETCH NEXT
y ROWS ONLY` syntax (available since Oracle 12c). The old pattern:

```sql
WITH USER_SQL AS ($sql),
    PAGINATION AS (SELECT USER_SQL.*, rownum as rowNumId FROM USER_SQL)
SELECT * FROM PAGINATION
WHERE rowNumId > $offset AND rownum <= $limit
```

is now:

```sql
$sql
ORDER BY ...
OFFSET $offset ROWS
FETCH NEXT $limit ROWS ONLY
```

If your application extends `\yii\db\oci\QueryBuilder` and overrides `buildOrderByAndLimit()`, update your override
to match the new `OFFSET ... FETCH` syntax.

**WITH RECURSIVE:** `buildWithQueries()` no longer emits the `RECURSIVE` keyword. Oracle does not support
`WITH RECURSIVE`; recursion is implicit when a CTE references itself. If your application relied on the generated SQL
containing `WITH RECURSIVE`, update your expectations.

**Documentation links:** All Oracle documentation references in `Schema`, `QueryBuilder`, and `OracleMutex` have been
updated from Oracle 10g/11g URLs to Oracle 19c URLs.

**Schema query modernization:** Internal SQL queries in `\yii\db\oci\Schema` have been modernized. No public API
changes. `findSchemaNames()` now queries `ALL_USERS` with `ORACLE_MAINTAINED = 'N'` instead of `DBA_USERS` with
tablespace filtering — this no longer requires DBA privileges and uses the precise Oracle 12c+ column to identify
user-created accounts.

**`executeResetSequence()` privilege change:** `executeResetSequence()` now uses `ALTER SEQUENCE ... RESTART START WITH`
(Oracle 18c+) instead of `DROP SEQUENCE` + `CREATE SEQUENCE`. If your database user has `CREATE SEQUENCE` but not
`ALTER` privilege on the relevant sequences, you must grant it.

### PostgreSQL dead code removal, minimum PostgreSQL 12

The minimum supported PostgreSQL version is now **12**. PHP 8.2's `pdo_pgsql` requires libpq 10.0+ as the minimum
client library, and PostgreSQL 12 is already EOL (November 2024). PostgreSQL versions 9.x through 11 are all completely
unsupported. The following dead code has been removed:

- `QueryBuilder::oldUpsert()` method (CTE-based upsert workaround for PostgreSQL < 9.5). The `ON CONFLICT` syntax
  (available since 9.5) is now used unconditionally.
- `QueryBuilder::newUpsert()` method (inlined directly into `upsert()` since the version branch is gone).
- The `version_compare(..., '9.5')` check in `QueryBuilder::upsert()`.
- The `version_compare(..., '12.0')` check in `Schema::findColumns()` for identity column detection
  (`attidentity != ''`). The identity column clause is now always included in the SQL query.
- Version guards in PostgreSQL tests (`SchemaTest`, `QueryBuilderTest`) for features available since PostgreSQL 10 and 12.

**Documentation links:** All PostgreSQL documentation references in `Schema`, `QueryBuilder`, and `PgsqlMutex` have been
updated from version-specific URLs (9.0, 9.5) to current-version URLs.

If your application extends `\yii\db\pgsql\QueryBuilder` and overrides or calls `oldUpsert()` or `newUpsert()`, remove
those references. The upsert logic now lives directly in `upsert()` using the `ON CONFLICT` syntax.

### SQLite dead code removal, minimum SQLite 3.40.0

The minimum supported SQLite version is now **3.40.0**. PHP's `ext-pdo_sqlite` can be compiled against a bundled
SQLite or the system-provided `libsqlite3` (discovered via `pkg-config`), so the actual runtime version depends on the
build configuration and OS distribution. Official PHP builds (php.net, Docker `php:*` images) bundle SQLite ~3.40.x+
for PHP 8.2, but custom or distro builds may link against an older system library. Verify your runtime version with
`SELECT sqlite_version()`. All version-guarded code for SQLite < 3.7.11 and < 3.8.3 has been removed:

- `QueryBuilder::batchInsert()` override (version check `>= 3.7.11` always passed, delegated to parent). The ~45-line
  UNION SELECT fallback (`INSERT INTO ... SELECT ... UNION SELECT ...`) for SQLite < 3.7.11 was dead code.
- `use yii\helpers\StringHelper` import from `QueryBuilder` (only used in the dead fallback).
- `testBatchInsertOnOlderVersions()` test (always skipped because SQLite >= 3.7.11).
- `testUpsert()` override in `CommandTest` (the `< 3.8.3` version guard never triggered; the parent test runs
  correctly).
- Version guard in `DbSessionTest::setUp()` (the `< 3.8.3` check never triggered).
- Schema PHPDoc updated from `"SQLite (2/3)"` to `"SQLite 3"` (`ext-pdo_sqlite` does not support SQLite 2).

If your application extends `\yii\db\sqlite\QueryBuilder` and overrides `batchInsert()`, remove your override. The
parent `\yii\db\QueryBuilder::batchInsert()` handles all cases since native multi-row INSERT has been supported since
SQLite 3.7.11.

### `resolveTableNames()` removed from MSSQL, MySQL, PostgreSQL, and Oracle drivers

The `protected` method `resolveTableNames($table, $name)` has been removed from MSSQL, MySQL, PostgreSQL, and Oracle
Schema classes. This method was never defined in the parent `\yii\db\Schema` class; it was a per-driver internal method
that duplicated the logic of `resolveTableName($name)`.

`loadTableSchema()` now uses `resolveTableName()` directly:

```php
// before
$table = new TableSchema();
$this->resolveTableNames($table, $name);

// after
$table = $this->resolveTableName($name);
```

If your application extends any database Schema class and overrides `resolveTableNames()`, migrate your logic to
`resolveTableName()` instead. The method signature differs: `resolveTableName($name)` returns a new `TableSchema`
with the resolved parts, rather than mutating an existing one.

### MSSQL QueryBuilder uses deferred `{{table}}` / `[[column]]` quoting

The following `\yii\db\mssql\QueryBuilder` methods now return SQL with `{{table}}` / `[[column]]` placeholders instead
of pre-quoted `[table]` / `[column]` identifiers: `renameTable()`, `alterColumn()`, `addDefaultValue()`,
`dropDefaultValue()`, `dropColumn()`, `checkIntegrity()`.

These placeholders are resolved automatically by `Command::setSql()` → `Connection::quoteSql()` during normal
execution. **No action required** unless your code compares raw QueryBuilder output strings directly.

Additionally:
- `buildAddCommentSql()` and `buildRemoveCommentSql()` (protected) now use `sys.extended_properties` instead of
  `fn_listextendedproperty`, and the property name is `MS_Description` (capital D) instead of `MS_description`.
- `dropConstraintsForColumn()` (private) now uses `sys.default_constraints` + `sys.check_constraints` instead of
  the deprecated `sys.sysconstraints` compatibility view.
