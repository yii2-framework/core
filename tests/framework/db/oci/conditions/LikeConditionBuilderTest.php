<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\oci\conditions;

use Exception;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Group;
use yii\db\Query;
use yiiunit\base\db\BaseDatabase;
use yiiunit\framework\db\oci\conditions\providers\LikeConditionBuilderProvider;

use function is_string;

/**
 * Unit test for {@see \yii\db\oci\conditions\LikeConditionBuilder} with Oracle driver.
 *
 * {@see LikeConditionBuilderProvider} for test case data providers.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
#[Group('db')]
#[Group('condition')]
#[Group('oci')]
final class LikeConditionBuilderTest extends BaseDatabase
{
    protected $driverName = 'oci';

    #[DataProviderExternal(LikeConditionBuilderProvider::class, 'buildCondition')]
    public function testBuildCondition(array|object $condition, string $expected, array $expectedParams): void
    {
        $db = $this->getConnection(false, false);

        /**
         * Different `pdo_oci8` versions may or may not implement `PDO::quote()`, so `yii\db\Schema::quoteValue()` may
         * or may not quote `\`.
         */
        try {
            $encodedBackslash = substr($db->quoteValue('\\\\'), 1, -1);

            foreach ($expectedParams as $name => $value) {
                if (is_string($value)) {
                    $expectedParams[$name] = strtr($value, [$encodedBackslash => '\\']);
                }
            }
        } catch (Exception $e) {
            $this->markTestSkipped('Could not execute Connection::quoteValue() method: ' . $e->getMessage());
        }

        $query = (new Query())->where($condition);

        [$sql, $params] = $db->getQueryBuilder()->build($query);

        self::assertSame(
            'SELECT *' . ($expected === '' ? '' : ' WHERE ' . $this->replaceQuotes($expected)),
            $sql,
            'Generated SQL does not match expected SQL.',
        );
        self::assertSame(
            $expectedParams,
            $params,
            'Bound parameters do not match expected parameters.',
        );
    }
}
