<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Validator\Constraints;

use Money\Money;

/**
 * Validator ensuring a Money object has a value greater than or equal to the compared value.
 */
final class MoneyGreaterThanOrEqualValidator extends AbstractMoneyComparisonValidator
{
    protected function compareValues(Money $value1, ?Money $value2): bool
    {
        return null === $value2 || $value1->greaterThanOrEqual($value2);
    }

    protected function getErrorCode(): ?string
    {
        return MoneyGreaterThanOrEqual::TOO_LOW_ERROR;
    }
}
