<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\caching;

use PHPUnit\Framework\Attributes\Group;
use yii\caching\ApcuCache;
use yiiunit\base\caching\BaseCache;

/**
 * Unit tests for {@see ApcuCache}.
 */
#[Group('apcu')]
#[Group('caching')]
final class ApcuCacheTest extends BaseCache
{
    private ApcuCache|null $_cacheInstance = null;

    /**
     * @return ApcuCache
     */
    protected function getCacheInstance(): ApcuCache
    {
        if ('cli' === PHP_SAPI && !ini_get('apc.enable_cli')) {
            $this->markTestSkipped('APCu CLI is not enabled. Skipping.');
        }

        if ($this->_cacheInstance === null) {
            $this->_cacheInstance = new ApcuCache();
        }

        return $this->_cacheInstance;
    }

    public function testExpire(): void
    {
        $this->markTestSkipped('APCu keys are expiring only on the next request.');
    }

    public function testExpireAdd(): void
    {
        $this->markTestSkipped('APCu keys are expiring only on the next request.');
    }
}
