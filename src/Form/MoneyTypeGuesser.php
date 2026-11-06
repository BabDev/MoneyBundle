<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Form;

use BabDev\MoneyBundle\Form\Type\MoneyType;
use Money\Money;
use Symfony\Component\Form\FormTypeGuesserInterface;
use Symfony\Component\Form\Guess\Guess;
use Symfony\Component\Form\Guess\TypeGuess;
use Symfony\Component\Form\Guess\ValueGuess;

/**
 * Guesses the {@see MoneyType} form type for properties declared with the {@see Money} type.
 */
final class MoneyTypeGuesser implements FormTypeGuesserInterface
{
    /**
     * Whether each property is declared with the {@see Money} type, and if so, whether it accepts null.
     *
     * @var array<string, array<string, array{bool, bool}>>
     */
    private array $cache = [];

    public function guessType(string $class, string $property): ?TypeGuess
    {
        [$isMoney] = $this->getPropertyType($class, $property);

        return $isMoney ? new TypeGuess(MoneyType::class, [], Guess::HIGH_CONFIDENCE) : null;
    }

    public function guessRequired(string $class, string $property): ?ValueGuess
    {
        [$isMoney, $nullable] = $this->getPropertyType($class, $property);

        // Only a nullable property tells us something; a field is required by default
        return $isMoney && $nullable ? new ValueGuess(false, Guess::LOW_CONFIDENCE) : null;
    }

    public function guessMaxLength(string $class, string $property): ?ValueGuess
    {
        return null;
    }

    public function guessPattern(string $class, string $property): ?ValueGuess
    {
        return null;
    }

    /**
     * @return array{bool, bool}
     */
    private function getPropertyType(string $class, string $property): array
    {
        if (isset($this->cache[$class][$property])) {
            return $this->cache[$class][$property];
        }

        if (!class_exists($class)) {
            return $this->cache[$class][$property] = [false, false];
        }

        try {
            $reflector = new \ReflectionClass($class);

            // A child class does not list the private properties of its parent classes
            while (!$reflector->hasProperty($property) && $reflector->getParentClass()) {
                $reflector = $reflector->getParentClass();
            }

            $type = $reflector->getProperty($property)->getType();
        } catch (\ReflectionException) {
            return $this->cache[$class][$property] = [false, false];
        }

        // A union type such as `Money|int` is not guessed
        if (!$type instanceof \ReflectionNamedType || Money::class !== $type->getName()) {
            return $this->cache[$class][$property] = [false, false];
        }

        return $this->cache[$class][$property] = [true, $type->allowsNull()];
    }
}
