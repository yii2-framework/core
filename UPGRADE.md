## Upgrade notes

### Yii autoloader removal

- `Yii::autoload()` has been removed.
- `Yii::$classMap` has been removed.
- Do not rely on runtime autoload mappings via Yii internals.
- Use Composer autoload configuration instead:
  - `autoload.psr-4` for namespace mapping.
  - `autoload.classmap` for explicit class-to-file overrides.
  - `autoload.exclude-from-classmap` when overriding vendor classes.
  - `autoload-dev` for development and test-only classes.
- If you change autoload configuration, regenerate autoload files with `composer dump-autoload`.
