<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Tests;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata as OdmClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\Driver\SimplifiedXmlDriver as OdmXmlDriver;
use Doctrine\ORM\Mapping\ClassMetadata as OrmClassMetadata;
use Doctrine\ORM\Mapping\Driver\SimplifiedXmlDriver as OrmXmlDriver;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;

/**
 * Loads the bundle's mapping files with the Doctrine XML drivers, which validate them against the Doctrine XSDs.
 */
final class DoctrineMappingTest extends TestCase
{
    private const string MAPPING_DIR = __DIR__.'/../config/mapping';

    public function testOrmMappingDefinesMoneyAsAnEmbeddable(): void
    {
        if (!class_exists(OrmXmlDriver::class)) {
            self::markTestSkipped('Test requires the Doctrine ORM.');
        }

        $driver = new OrmXmlDriver([self::MAPPING_DIR => 'Money'], '.orm.xml');

        $money = new OrmClassMetadata(Money::class);
        $driver->loadMetadataForClass(Money::class, $money);

        self::assertTrue($money->isEmbeddedClass);
        self::assertSame('string', $money->getTypeOfField('amount'));
        self::assertSame(Currency::class, $money->embeddedClasses['currency']->class);

        $currency = new OrmClassMetadata(Currency::class);
        $driver->loadMetadataForClass(Currency::class, $currency);

        self::assertTrue($currency->isEmbeddedClass);
        self::assertSame('string', $currency->getTypeOfField('code'));
    }

    public function testOdmMappingDefinesMoneyAsAnEmbeddedDocument(): void
    {
        if (!class_exists(OdmXmlDriver::class)) {
            self::markTestSkipped('Test requires the Doctrine MongoDB ODM.');
        }

        $driver = new OdmXmlDriver([self::MAPPING_DIR => 'Money'], '.mongodb.xml');

        $money = new OdmClassMetadata(Money::class);
        $driver->loadMetadataForClass(Money::class, $money);

        self::assertTrue($money->isEmbeddedDocument);
        self::assertSame('string', $money->getTypeOfField('amount'));
        self::assertSame(Currency::class, $money->getAssociationTargetClass('currency'));

        $currency = new OdmClassMetadata(Currency::class);
        $driver->loadMetadataForClass(Currency::class, $currency);

        self::assertTrue($currency->isEmbeddedDocument);
        self::assertSame('string', $currency->getTypeOfField('code'));
    }
}
