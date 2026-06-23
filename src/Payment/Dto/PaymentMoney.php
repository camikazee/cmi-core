<?php

declare(strict_types=1);

namespace Core\Payment\Dto;

use Core\Exception\InvalidDataException;

/**
 * Kwota w formacie major units jako string (np. "49.00") + waluta ISO 4217.
 */
final readonly class PaymentMoney
{
    public function __construct(
        public string $amount,
        public string $currency = 'PLN',
    ) {
        if (preg_match('/^\d+\.\d{2}$/', $amount) !== 1) {
            throw new InvalidDataException(sprintf('Niepoprawny format kwoty: "%s" (oczekiwano \\d+\\.\\d{2}).', $amount));
        }
        if (preg_match('/^[A-Z]{3}$/', $currency) !== 1) {
            throw new InvalidDataException(sprintf('Niepoprawny kod waluty: "%s" (oczekiwano ISO 4217).', $currency));
        }
    }

    /** Buduje z kwoty w groszach/minor units. */
    public static function fromMinorUnits(int $minor, string $currency = 'PLN'): self
    {
        return new self(number_format($minor / 100, 2, '.', ''), $currency);
    }

    /** Kwota w groszach/minor units. */
    public function minorUnits(): int
    {
        return (int) str_replace('.', '', $this->amount);
    }
}
