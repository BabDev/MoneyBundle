<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Validator\Constraints;

use Money\Money;

/**
 * Validator ensuring a Money object has a value less than or equal to the compared value.
 */
final class MoneyLessThanOrEqualValidator extends AbstractMoneyComparisonValidator
{
    protected function compareValues(Money $value1, ?Money $value2): bool
    {
        return null === $value2 || $value1->lessThanOrEqual($value2);
    }

    protected function getErrorCode(): ?string
    {
        return MoneyLessThanOrEqual::TOO_HIGH_ERROR;
    }
}
