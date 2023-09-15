<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Factory\Exception;

use BabDev\MoneyBundle\Format;

final class UnsupportedFormatException extends \InvalidArgumentException
{
    /**
     * @param list<string> $formats
     *
     * @phpstan-param list<Format::*> $formats
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
     * @return list<string>
     *
     * @phpstan-return list<Format::*>
     */
    public function getFormats(): array
    {
        return $this->formats;
    }
}
