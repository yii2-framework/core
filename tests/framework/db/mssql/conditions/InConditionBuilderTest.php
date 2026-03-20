<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\mssql\conditions;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Group;
use yii\base\InvalidArgumentException;
use yii\base\NotSupportedException;
use yii\db\conditions\InCondition;
use yii\db\mssql\conditions\InConditionBuilder;
use yii\db\Query;
use yiiunit\base\db\BaseDatabase;
use yiiunit\framework\db\mssql\conditions\providers\InConditionBuilderProvider;

/**
 * Unit test for {@see \yii\db\conditions\InConditionBuilder} with MSSQL driver.
 *
 * {@see InConditionBuilderProvider} for test case data providers.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
#[Group('db')]
#[Group('condition')]
#[Group('mssql')]
final class InConditionBuilderTest extends BaseDatabase
{
    protected $driverName = 'sqlsrv';

    #[DataProviderExternal(InConditionBuilderProvider::class, 'buildCondition')]
    public function testBuildCondition(array|object $condition, string $expected, array $expectedParams): void
    {
        $db = $this->getConnection(false, false);

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

    public function testThrowNotSupportedExceptionWhenBuildSubqueryInConditionIsCompositeColumns(): void
    {
        $db = $this->getConnection(false, false);

        $inConditionBuilder = new InConditionBuilder($db->getQueryBuilder());

        $inCondition = new InCondition(
            [
                'id',
                'name',
            ],
            'in',
            (new Query())->select(['id', 'name'])->from('users')->where(['active' => 1]),
        );

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'yii\db\mssql\conditions\InConditionBuilder::buildSubqueryInCondition is not supported by MSSQL.',
        );

        $inConditionBuilder->build($inCondition);
    }

    public function testThrowInvalidArgumentExceptionWhenFromArrayDefinitionHasMissingOperands(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "Operator 'IN' requires two operands.",
        );

        InCondition::fromArrayDefinition('IN', []);
    }
}
