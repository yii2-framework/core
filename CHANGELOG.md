# Changelog

## 0.1.0 Under development

- chore: initial commit.
- feat!: remove Yii runtime autoloader and rely on Composer autoload configuration.
- fix: avoid Composer ambiguous autoloading and remove PSR-4 autoload warnings in tests and legacy files.
- refactor(tests): remove legacy test configurations and scripts; add new Docker Compose files for various databases.
- refactor(db)!: remove CUBRID database driver, tests, fixture, configuration entries, and PHPStan baseline suppressions.
