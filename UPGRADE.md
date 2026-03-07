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

The `yii\caching\ApcCache` class has been renamed to `yii\caching\ApcuCache`. The legacy APC extension (`ext-apc`) is
not available in PHP >= 8.0, so the dual-mode `$useApcu` property has been removed.

- Replace all references to `yii\caching\ApcCache` with `yii\caching\ApcuCache`.
- Remove any `'useApcu' => true` configuration, as it is no longer needed.
- Migration example:

```php
// before
'cache' => [
    'class' => 'yii\caching\ApcCache',
    'useApcu' => true,
],

// after
'cache' => [
    'class' => 'yii\caching\ApcuCache',
],
```
### CUBRID database driver removal

The CUBRID database driver has been removed from the framework.

### HHVM support removed

All HHVM-specific code has been removed from the framework. The framework targets PHP 8.1+ on the Zend engine only.

- `yii\base\ErrorException::E_HHVM_FATAL_ERROR` constant has been removed.
- `yii\base\ErrorHandler::handleHhvmError()` method has been removed.
- `yii\base\ErrorHandler` no longer registers `handleHhvmError` as the error handler when `HHVM_VERSION` is defined.
- The `PhpManager` HHVM incompatibility note has been removed from its docblock.
- All test skips guarded by `defined('HHVM_VERSION')` have been removed.

If you referenced `ErrorException::E_HHVM_FATAL_ERROR` or `ErrorHandler::handleHhvmError()` in your application code,
remove those references.
