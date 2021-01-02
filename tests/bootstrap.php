<?php declare(strict_types=1);

use Money\PHPUnit\Comparator;
use SebastianBergmann\Comparator\Factory;

require dirname(__DIR__).'/vendor/autoload.php';

Factory::getInstance()->register(new Comparator());
