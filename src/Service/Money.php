<?php

declare(strict_types=1);

namespace App\Service;

final class Money
{
    public static function toCents(string $amount): int
    {
        if (!preg_match('/^\d{1,8}(?:\.\d{1,2})?$/D', $amount)) {
            throw new \DomainException('Invalid monetary amount.');
        }
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');

        return (int) $whole * 100 + (int) str_pad($fraction, 2, '0');
    }

    public static function fromCents(int $amount): string
    {
        if ($amount < 0 || $amount > 9999999999) {
            throw new \DomainException('Order amount is outside the supported range.');
        }

        return sprintf('%d.%02d', intdiv($amount, 100), $amount % 100);
    }
}
