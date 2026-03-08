# Changelog

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
- fix(console): `getActionArgsHelp()` crashes on PHP `8.2+` DNF/intersection types.
- feat(widgets): enhance `ActiveField::label()` with `tag` option and fix `labelOptions` for `checkbox`/`radio`.
