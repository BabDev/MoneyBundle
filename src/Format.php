<?php declare(strict_types=1);

namespace BabDev\MoneyBundle;

/**
 * Enumeration class for supported formats.
 */
abstract class Format
{
    public const string AGGREGATE = 'aggregate';
    public const string BITCOIN = 'bitcoin';
    public const string DECIMAL = 'decimal';
    public const string INTL_LOCALIZED_DECIMAL = 'intl_localized_decimal';
    public const string INTL_MONEY = 'intl_money';
}
