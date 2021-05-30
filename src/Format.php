<?php declare(strict_types=1);

namespace BabDev\MoneyBundle;

/**
 * Enumeration class for supported formats.
 */
abstract class Format
{
    public const AGGREGATE = 'aggregate';
    public const BITCOIN = 'bitcoin';
    public const DECIMAL = 'decimal';
    public const INTL_LOCALIZED_DECIMAL = 'intl_localized_decimal';
    public const INTL_MONEY = 'intl_money';
}
