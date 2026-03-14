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
- refactor(base)!: remove all HHVM support — drop `E_HHVM_FATAL_ERROR` constant, `handleHhvmError()` method, `$_hhvmException` property, and all HHVM-specific conditionals and test skips.
- refactor!: remove `PHP < 8.2` version guards and dead fallbacks across `src/` and `tests`.
- feat(widgets): enhance `ActiveField::label()` with `tag` option and fix `labelOptions` for `checkbox`/`radio`.
- feat: make jQuery optional via strategy pattern — introduce `Application::$useJquery`, `ClientValidatorScriptInterface`, `ClientScriptInterface`, and extracted jQuery client script classes for all validators and widgets.
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
- refactor(db)!: remove MSSQL `< 2017` dead code drop `isOldMssql()`, `oldBuildOrderByAndLimit()`, `newBuildOrderByAndLimit()`, ROW_NUMBER() pagination, SQL Server 2005 version checks, and pre-2017 boolean type heuristics; `bit` now maps directly to `boolean` in `typeMap`; minimum supported version is now SQL Server 2017.
- refactor(db)!: modernize Oracle support for 19c+ replace `ROWNUM`/CTE pagination with standard `OFFSET x ROWS FETCH NEXT y ROWS ONLY`, fix `buildWithQueries()` emitting unsupported `WITH RECURSIVE` keyword (same issue as MSSQL #34), and update documentation links; minimum supported version is now Oracle 19c.
