<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Factory\Exception;

final class UnsupportedFormatException extends \InvalidArgumentException
{
    /**
     * @param list<non-empty-string> $formats The formats supported by the factory
     */
    public function __construct(
        private readonly array $formats,
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return list<non-empty-string>
     */
    public function getFormats(): array
    {
        return $this->formats;
    }
}
