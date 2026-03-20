<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\oci\conditions;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Group;
use yii\db\Query;
use yiiunit\base\db\BaseDatabase;
use yiiunit\base\db\conditions\providers\ConjunctionConditionBuilderProvider;

/**
 * Unit test for {@see \yii\db\conditions\ConjunctionConditionBuilder} with Oracle driver.
 *
 * {@see ConjunctionConditionBuilderProvider} for test case data providers.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
#[Group('db')]
#[Group('condition')]
#[Group('oci')]
final class ConjunctionConditionBuilderTest extends BaseDatabase
{
    protected $driverName = 'oci';

    #[DataProviderExternal(ConjunctionConditionBuilderProvider::class, 'buildCondition')]
    public function testBuildCondition(array|object $condition, string $expected, array $expectedParams): void
    {
        $query = (new Query())->where($condition);

        $db = $this->getConnection(true, false);

        [$sql, $params] = $db->queryBuilder->build($query);

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
