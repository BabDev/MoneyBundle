<?php declare(strict_types=1);

namespace BabDev\MoneyBundle;

use BabDev\MoneyBundle\DependencyInjection\BabDevMoneyExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class BabDevMoneyBundle extends Bundle
{
    public function getContainerExtension()
    {
        if (null === $this->extension) {
            $this->extension = new BabDevMoneyExtension();
        }

        return $this->extension ?: null;
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
