<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Validator\Constraints;

use Money\Money;

/**
 * Validator ensuring a Money object has a value equal to the compared value.
 */
final class MoneyEqualToValidator extends AbstractMoneyComparisonValidator
{
    protected function compareValues(Money $value1, ?Money $value2): bool
    {
        return null === $value2 || $value1->equals($value2);
    }

    protected function getErrorCode(): ?string
    {
        return MoneyEqualTo::NOT_EQUAL_ERROR;
    }
}
