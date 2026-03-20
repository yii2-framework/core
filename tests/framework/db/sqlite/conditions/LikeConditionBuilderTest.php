<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\sqlite\conditions;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Group;
use yii\base\InvalidArgumentException;
use yii\db\conditions\LikeCondition;
use yii\db\Query;
use yiiunit\base\db\BaseDatabase;
use yiiunit\framework\db\sqlite\conditions\providers\LikeConditionBuilderProvider;

/**
 * Unit test for {@see \yii\db\sqlite\conditions\LikeConditionBuilder} with SQLite driver.
 *
 * {@see LikeConditionBuilderProvider} for test case data providers.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
#[Group('db')]
#[Group('sqlite')]
#[Group('condition')]
final class LikeConditionBuilderTest extends BaseDatabase
{
    protected $driverName = 'sqlite';

    #[DataProviderExternal(LikeConditionBuilderProvider::class, 'buildCondition')]
    public function testBuildCondition(array|object $condition, string $expected, array $expectedParams): void
    {
        $db = $this->getConnection(false, false);

        $query = new Query();

        $query->where($condition);

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

    public function testThrowInvalidArgumentExceptionWhenFromArrayDefinitionHasMissingOperands(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Operator 'LIKE' requires two operands.");

        LikeCondition::fromArrayDefinition('LIKE', []);
    }

    public function testThrowInvalidArgumentExceptionWhenParseOperatorReceivesInvalidOperator(): void
    {
        $db = $this->getConnection(false, false);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid operator 'INVALID'.");

        $condition = new LikeCondition('name', 'INVALID', 'value');

        $db->queryBuilder->buildExpression($condition);
    }
}
