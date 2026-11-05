<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Service\Money;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    #[DataProvider('amounts')]
    public function testExactCentavoConversions(string $amount, int $cents, string $normalized): void
    {
        self::assertSame($cents, Money::toCents($amount));
        self::assertSame($normalized, Money::fromCents($cents));
    }

    public static function amounts(): array
    {
        return [['0.10', 10, '0.10'], ['145.35', 14535, '145.35'], ['9', 900, '9.00'], ['99999999.99', 9999999999, '99999999.99']];
    }

    #[DataProvider('invalidAmounts')]
    public function testRejectsInvalidAmounts(string $amount): void
    {
        $this->expectException(\DomainException::class);
        Money::toCents($amount);
    }

    public static function invalidAmounts(): array
    {
        return [['-1'], ['1e3'], ['1.001'], [''], ['NaN']];
    }

    public function testExactOrderArithmetic(): void
    {
        self::assertSame('100.30', Money::fromCents(Money::toCents('0.10') * 3 + Money::toCents('100.00')));
    }
}
