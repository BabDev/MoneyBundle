<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Validator\Constraints;

use Money\Money;

/**
 * Validator ensuring a Money object has a value less than the compared value.
 */
final class MoneyLessThanValidator extends AbstractMoneyComparisonValidator
{
    protected function compareValues(Money $value1, ?Money $value2): bool
    {
        return null === $value2 || $value1->lessThan($value2);
    }

    protected function getErrorCode(): ?string
    {
        return MoneyLessThan::TOO_HIGH_ERROR;
    }
}
