<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Factory\Exception;

use BabDev\MoneyBundle\Format;

final class UnsupportedFormatException extends \InvalidArgumentException
{
    /**
     * @var string[]
     * @phpstan-var array<Format::*>
     */
    private array $formats;

    /**
     * @param string[] $formats
     * @phpstan-param array<Format::*> $formats
     */
    public function __construct(array $formats, string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->formats = $formats;
    }

    /**
     * @return string[]
     * @phpstan-return array<Format::*>
     */
    public function getFormats(): array
    {
        return $this->formats;
    }
}
