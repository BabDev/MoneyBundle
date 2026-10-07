<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Tests\Integration\Fixtures;

use BabDev\MoneyBundle\Validator\Constraints as MoneyAssert;
use Doctrine\ORM\Mapping as ORM;
use Money\Money;

#[ORM\Entity]
class Invoice
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    public ?int $id = null;

    public function __construct(
        #[ORM\Embedded(class: Money::class)]
        #[MoneyAssert\MoneyPositive]
        #[MoneyAssert\MoneyRange(max: '1000.00')]
        public Money $total,
    ) {}
}
