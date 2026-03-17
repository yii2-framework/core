# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Conventional Commits](https://www.conventionalcommits.org/en/v1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 0.1.0 Under development

- chore: initial commit.
- feat!: remove Yii runtime autoloader and rely on Composer autoload configuration.
- fix: avoid Composer ambiguous autoloading and remove PSR-4 autoload warnings in tests and legacy files.
- refactor(tests): remove legacy test configurations and scripts; add new Docker Compose files for various databases.
- refactor(db)!: remove CUBRID database driver, tests, fixture, configuration entries, and PHPStan baseline suppressions.
- refactor(caching)!: rename `ApcCache` to `ApcuCache` and remove legacy APC extension support.
- refactor(base): remove dead `E_STRICT` handling and `PHP_VERSION_ID < 80100` guards from `ErrorException` and `ErrorHandler`.
- refactor(base)!: remove all HHVM support drop `E_HHVM_FATAL_ERROR` constant, `handleHhvmError()` method, `$_hhvmException` property, and all HHVM-specific conditionals and test skips.
- refactor!: remove `PHP < 8.2` version guards and dead fallbacks across `src/` and `tests`.
- feat(widgets): enhance `ActiveField::label()` with `tag` option and fix `labelOptions` for `checkbox`/`radio`.
- feat: make jQuery optional via strategy pattern introduce `Application::$useJquery`, `ClientValidatorScriptInterface`, `ClientScriptInterface`, and extracted jQuery client script classes for all validators and widgets.
- test(validators): add comprehensive test coverage for `CompareValidator` data-provider-driven tests, closure validation, client-side validation, and numeric type conversion scenarios.
- tests(base): raise code coverage to `100%` for `Component`, `Event` and `Model` classes, and update related tests.
- refactor(tests): simplify BaseDatabase, migrate Oracle to `gvenzl/oracle-free` and optimize test performance.
- fix(oracle): `loadTableIndexes()` includes LOB indexes with `NULL` column names, causing `strpos()` deprecation on PHP `8.1+`.
- fix(console): `getActionArgsHelp()` crashes on PHP `8.2+` DNF/intersection types, add data-provider-driven tests for full type coverage.
- fix(db)!: composite `IN`/`NOT IN` conditions generate `IS NULL`/`IS NOT NULL` instead of literal `NULL` comparisons.
- test(db): add UNION/UNION ALL subquery tests for FROM, JOIN, and IN clauses across all database drivers.
- feat(web): add `yii\web\ErrorHandler::EVENT_AFTER_RENDER` with mutable `ErrorHandlerRenderEvent::$output` to allow post-processing HTML error pages, including PHP `ErrorException` rendering path.
- fix(db): MSSQL `buildWithQueries()` emits `WITH RECURSIVE` keyword unsupported by SQL Server; recursion is implicit.
- fix(helpers): preserve escaped non-format literals in `FormatConverter::convertDatePhpToIcu()` and keep ICU/PHP round-trip output consistent.
- fix(helpers): support escaped PHP `date()` format chars `v`, `p`, `X`, `x` in `FormatConverter::convertDatePhpToIcu()`, split escape handling, and optimize the hot path while preserving #35/#37 behavior.
- refactor(db)!: remove MySQL `< 8.0` dead code and deprecated integer display width from type map (`int(11)` → `int`, `bigint(20)` → `bigint`, etc.); `tinyint(1)` for `BOOLEAN` is preserved.
- refactor(db)!: remove MSSQL `< 2017` dead code drop `isOldMssql()`, `oldBuildOrderByAndLimit()`, `newBuildOrderByAndLimit()`, ROW_NUMBER() pagination, SQL Server `2005` version checks, and pre-2017 boolean type heuristics; `bit` now maps directly to `boolean` in `typeMap`; minimum supported version is now SQL Server `2017`.
- refactor(db)!: modernize Oracle support for 19c+ replace `ROWNUM`/CTE pagination with standard `OFFSET x ROWS FETCH NEXT y ROWS ONLY`, fix `buildWithQueries()` emitting unsupported `WITH RECURSIVE` keyword (same issue as MSSQL #34), and update documentation links; minimum supported version is now Oracle `19c`.
- fix(db): keep `inverseOf()` array hydration shape consistent and avoid indirect modification notices in mixed object/array inverse population (`asArray()`).
- fix(db): MSSQL RBAC cascade gaps and `varbinary` type-casting move binary type-casting to `ColumnSchema::dbTypecast()`, remove `normalizeTableRowData()`, extend `auth_item` triggers to cascade to `auth_assignment`, add `auth_rule` INSTEAD OF triggers, and add `MsSQLManagerTest`/`MsSQLManagerCacheTest`.
- fix(db): prevent `ActiveRecord::refresh()` parameter mismatch when custom `find()` adds bound parameters.
- chore: adjust code style.
- fix(console): `MessageController` crashes on dynamic concatenation in `Yii::t()` calls validate tokens as `T_CONSTANT_ENCAPSED_STRING` before extracting and use `null` sentinel to preserve empty-string messages.
- refactor(db)!: remove PostgreSQL `< 12` dead code drop `oldUpsert()` CTE workaround for `< 9.5`, remove identity column version check for `< 12`, and remove test version guards; minimum supported version is now PostgreSQL `12`.
- refactor(db)!: remove SQLite `< 3.40.0` dead code drop `batchInsert()` UNION SELECT fallback for `< 3.7.11`, remove `testUpsert()` version guard for `< 3.8.3`, remove `DbSessionTest` version guard, and update Schema PHPDoc from `SQLite (2/3)` to `SQLite 3`; minimum supported version is now SQLite `3.40.0`.
- fix(db): add Oracle BLOB `dbTypecast()` to `ColumnSchema` wrapping string values in `TO_BLOB(UTL_RAW.CAST_TO_RAW(:placeholder))` expressions to avoid direct string binding errors on BLOB columns.
- fix(widgets): clear stale client-side errors for conditional (`whenClient`) validators after related field changes.
- refactor(js): remove all ESLint warnings in core JS assets/tests and enforce `npm run lint` with `--max-warnings=0`.
- refactor(db): consolidate MSSQL data type conversions into `ColumnSchema` absorb `(NULL)` and `CURRENT_TIMESTAMP` default handling into `defaultPhpTypecast()`, move OUTPUT clause type declarations into new `getOutputColumnDeclaration()`, and simplify `Schema::loadColumnSchema()` and `QueryBuilder::insert()`.
- refactor(db): consolidate MySQL data type conversions into `ColumnSchema` absorb `CURRENT_TIMESTAMP` and bit default handling into `defaultPhpTypecast()`, simplify `Schema::loadColumnSchema()` to store raw defaults, and resolve defaults in `findColumns()`.
- refactor(db): consolidate Oracle data type conversions into `ColumnSchema` absorb `CURRENT_TIMESTAMP` and timestamp default handling into `defaultPhpTypecast()`, simplify `Schema::createColumn()` to store raw defaults, and resolve defaults in `loadTableSchema()` after `findConstraints()` sets `isPrimaryKey`; fixes `ts_default` returning `null` instead of `Expression('CURRENT_TIMESTAMP')`.
- refactor(db): consolidate PostgreSQL data type conversions into `ColumnSchema` absorb default handling into `defaultPhpTypecast()`, simplify `Schema::findColumns()`, and fix `phpTypecastValue()` strict-types `TypeError`.
- refactor(db): consolidate SQLite data type conversions into `ColumnSchema` absorb default handling into `defaultPhpTypecast()`, simplify `Schema::loadColumnSchema()` to store raw defaults, and resolve defaults in `loadTableSchema()` after `findConstraints()` sets `isPrimaryKey`.
- fix(db): replace MSSQL `INFORMATION_SCHEMA.COLUMNS` with `sys.*` catalog views in `findColumns()` fix `= NULL` bug, add `decimal`/`numeric` precision/scale, eliminate SQL injection via parameterized `OBJECT_ID()`, and quote `[]` name parts for special-character table support.
- perf(db): replace MSSQL `INFORMATION_SCHEMA.KEY_COLUMN_USAGE` + `TABLE_CONSTRAINTS` with `sys.key_constraints` in `findTableConstraints()` use `OBJECT_ID()` for integer-based filtering and add `ORDER BY [ic].[key_ordinal]` for correct composite key column ordering.
- refactor(db): replace MSSQL `INFORMATION_SCHEMA.TABLES` with `sys.objects` + `sys.views` in `findTableNames()` and `findViewNames()`, remove legacy SQL Server 2000 doc link from `findForeignKeys()`.
- fix(db): add `quoteSimpleTableName()` quoting and cross-database `catalogPrefix` to MSSQL `loadTableIndexes()`, `findForeignKeys()`, and `loadTableConstraints()`; remove `static $sql` and normalize heredoc format across all Schema methods.
- refactor(db)!: remove redundant `resolveTableNames()` from MSSQL, MySQL, PostgreSQL, and Oracle Schema classes; `loadTableSchema()` now uses `resolveTableName()` directly; simplify `resolveTableName()` in all drivers.
