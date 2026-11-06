<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Tests\Form;

use BabDev\MoneyBundle\Factory\FormatterFactory;
use BabDev\MoneyBundle\Factory\ParserFactory;
use BabDev\MoneyBundle\Form\MoneyTypeGuesser;
use BabDev\MoneyBundle\Form\Type\MoneyType;
use Money\Money;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Guess\Guess;

final class MoneyTypeGuesserTest extends TestCase
{
    public static function dataMoneyProperties(): \Generator
    {
        yield 'non-nullable property' => ['price', null];
        yield 'nullable property' => ['discount', false];
        yield 'promoted constructor property' => ['shipping', null];
        yield 'private property of a parent class' => ['cost', false];
    }

    #[DataProvider('dataMoneyProperties')]
    public function testGuessesTheMoneyTypeForMoneyProperties(string $property, ?bool $required): void
    {
        $guesser = new MoneyTypeGuesser();

        $typeGuess = $guesser->guessType(GuesserFixture::class, $property);

        self::assertNotNull($typeGuess);
        self::assertSame(MoneyType::class, $typeGuess->getType());
        self::assertSame([], $typeGuess->getOptions());
        self::assertSame(Guess::HIGH_CONFIDENCE, $typeGuess->getConfidence());

        $requiredGuess = $guesser->guessRequired(GuesserFixture::class, $property);

        if (null === $required) {
            self::assertNull($requiredGuess);
        } else {
            self::assertNotNull($requiredGuess);
            self::assertSame($required, $requiredGuess->getValue());
            self::assertSame(Guess::LOW_CONFIDENCE, $requiredGuess->getConfidence());
        }

        self::assertNull($guesser->guessMaxLength(GuesserFixture::class, $property));
        self::assertNull($guesser->guessPattern(GuesserFixture::class, $property));
    }

    public static function dataOtherProperties(): \Generator
    {
        yield 'union type' => [GuesserFixture::class, 'amount'];
        yield 'other class' => [GuesserFixture::class, 'createdAt'];
        yield 'scalar type' => [GuesserFixture::class, 'name'];
        yield 'mixed type' => [GuesserFixture::class, 'mixed'];
        yield 'missing property' => [GuesserFixture::class, 'missing'];
        yield 'missing class' => ['App\Missing', 'price'];
    }

    /**
     * @param class-string $class
     */
    #[DataProvider('dataOtherProperties')]
    public function testDoesNotGuessOtherProperties(string $class, string $property): void
    {
        $guesser = new MoneyTypeGuesser();

        self::assertNull($guesser->guessType($class, $property));
        self::assertNull($guesser->guessRequired($class, $property));
    }

    public function testFieldsAddedWithoutATypeUseTheMoneyType(): void
    {
        $factory = Forms::createFormFactoryBuilder()
            ->addType(new MoneyType(new FormatterFactory('en_US'), new ParserFactory('en_US'), 'USD'))
            ->addTypeGuesser(new MoneyTypeGuesser())
            ->getFormFactory();

        $data = new GuesserFixture(Money::USD(0));

        $form = $factory->createBuilder(FormType::class, $data)
            ->add('price')
            ->add('discount')
            ->getForm();

        self::assertInstanceOf(MoneyType::class, $form->get('price')->getConfig()->getType()->getInnerType());
        self::assertTrue($form->get('price')->isRequired());
        self::assertFalse($form->get('discount')->isRequired());

        $form->submit(['price' => '12.34', 'discount' => '']);

        self::assertTrue($form->isSynchronized());
        self::assertEquals(Money::USD(1234), $data->price);
        self::assertNull($data->discount);
    }
}

abstract class GuesserFixtureParent
{
    private ?Money $cost = null;

    public function getCost(): ?Money
    {
        return $this->cost;
    }

    public function setCost(?Money $cost): void
    {
        $this->cost = $cost;
    }
}

final class GuesserFixture extends GuesserFixtureParent
{
    public Money $price;

    public ?Money $discount = null;

    public Money|int $amount = 0;

    public ?\DateTimeImmutable $createdAt = null;

    public string $name = '';

    public mixed $mixed = null;

    public function __construct(public Money $shipping) {}
}
