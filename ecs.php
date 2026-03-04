<?php

declare(strict_types=1);

/** @var \Symplify\EasyCodingStandard\Configuration\ECSConfigBuilder $ecsConfigBuilder */
$ecsConfigBuilder = require __DIR__ . '/vendor/php-forge/coding-standard/config/ecs.php';

$ecsConfigBuilder = $ecsConfigBuilder->withPaths(
    [
        __DIR__ . '/tests',
    ],
);

return $ecsConfigBuilder->withSkip(
    [
        __DIR__ . '/tests/js',
        __DIR__ . '/tests/runtime',
    ],
);
