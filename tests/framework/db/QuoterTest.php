<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use yii\db\Quoter;
use yiiunit\framework\db\providers\QuoterProvider;

/**
 * Unit tests for {@see Quoter} static helper.
 *
 * {@see QuoterProvider} for test case data providers.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
#[Group('db')]
#[Group('quoter')]
final class QuoterTest extends TestCase
{
    #[DataProviderExternal(QuoterProvider::class, 'resolveQuoteCharacter')]
    public function testResolveQuoteCharacter(array|string $input, array $expected): void
    {
        self::assertSame(
            $expected,
            Quoter::resolveQuoteCharacter($input),
            "resolveQuoteCharacter() should return the correct [start, end] pair.",
        );
    }

    #[DataProviderExternal(QuoterProvider::class, 'unquoteIdentifier')]
    public function testUnquoteIdentifier(
        string $name,
        string $startQuote,
        string $endQuote,
        string $expected,
    ): void {
        self::assertSame(
            $expected,
            Quoter::unquoteIdentifier($name, $startQuote, $endQuote),
            "unquoteIdentifier('$name', '$startQuote', '$endQuote') should return '$expected'.",
        );
    }

    #[DataProviderExternal(QuoterProvider::class, 'splitQuotedName')]
    public function testSplitQuotedName(
        string $name,
        string $startQuote,
        string $endQuote,
        array $expected,
    ): void {
        self::assertSame(
            $expected,
            Quoter::splitQuotedName($name, $startQuote, $endQuote),
            "splitQuotedName('$name', '$startQuote', '$endQuote') should return the expected parts.",
        );
    }

    #[DataProviderExternal(QuoterProvider::class, 'unquoteAny')]
    public function testUnquoteAny(string $input, string $expected): void
    {
        self::assertSame(
            $expected,
            Quoter::unquoteAny($input),
            "unquoteAny('$input') should return '$expected'.",
        );
    }
}
