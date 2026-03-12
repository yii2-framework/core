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

**No action required** for existing applications — the default behavior is fully backward compatible.

#### New interfaces

- `\yii\validators\client\ClientValidatorScriptInterface` — strategy for validator client scripts.
- `\yii\web\client\ClientScriptInterface` — strategy for widget client scripts.

#### New properties

- `\yii\base\Application::$useJquery` — master switch for jQuery client scripts (default: `true`).
- `Validator::$clientScript` — on all 13 validators that support client validation (`BooleanValidator`,
  `CompareValidator`, `EmailValidator`, `FileValidator`, `ImageValidator`, `IpValidator`, `NumberValidator`,
  `RangeValidator`, `RegularExpressionValidator`, `RequiredValidator`, `StringValidator`, `TrimValidator`,
  `UrlValidator`).
- `ActiveForm::$clientScript`, `GridView::$clientScript`, `CheckboxColumn::$clientScript` — widget-level overrides.

#### New method

- `Validator::getFormattedClientMessage(string, array): string` — public wrapper around the protected
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

### `InCondition` — typed constructor and return types (#27)

`InCondition` now uses constructor promotion with explicit union types. All public methods declare return types.

If you instantiate `InCondition` directly, ensure the arguments match the new parameter types:

- `$column`: `array|string|ExpressionInterface|Traversable` (Traversable is normalized to array on `getColumn()`)
- `$operator`: `string`
- `$values`: `array|int|string|ExpressionInterface|Traversable` (Traversable is normalized to array on `getValues()`)

Return types added: `getOperator(): string`, `getColumn(): array|string|ExpressionInterface`,
`getValues(): array|int|string|ExpressionInterface`, `fromArrayDefinition(): static`.

> **Note:** `getColumn()` and `getValues()` now convert any `Traversable` (including Generators) to `array` on first
> access. Subsequent calls return the cached array. This means the return types no longer include `Traversable`.

### `InConditionBuilder` — typed protected methods (#27)

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

### `oci\InConditionBuilder` — typed `build()` and `splitCondition()` (`#27`)

`build()` now returns `string` explicitly. `splitCondition()` now returns `string|null` explicitly. If you extend the Oracle
builder, update your overrides accordingly.

### Composite `IN`/`NOT IN` conditions — `IS NULL`/`IS NOT NULL` generation (`#27`)

Composite `IN`/`NOT IN` conditions now generate `IS NULL`/`IS NOT NULL` expressions for `NULL` values in the value list,
instead of literal `NULL` comparisons. This aligns with SQL semantics where `column = NULL` always evaluates to `UNKNOWN`.

**Before:** `(col1 = :p0 AND col2 = NULL)` — always fails due to SQL NULL semantics  
**After:** `(col1 = :p0 AND col2 IS NULL)` — correctly matches NULL values
