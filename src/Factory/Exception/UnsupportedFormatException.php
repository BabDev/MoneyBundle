<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Factory\Exception;

final class UnsupportedFormatException extends \InvalidArgumentException
{
    /**
     * @var string[]
     */
    private array $formats;

    /**
     * @param string[] $formats
     */
    public function __construct(array $formats, string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->formats = $formats;
    }

    /**
     * @return string[]
     */
    public function getFormats(): array
    {
        return $this->formats;
    }
}
