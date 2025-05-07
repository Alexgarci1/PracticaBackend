<?php

/**
 * tests/Entity/AsociacionTest.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace TDW\Test\ACiencia\Entity;

use PHPUnit\Framework\Attributes as TestsAttr;
use PHPUnit\Framework\TestCase;
use TDW\ACiencia\Entity\{ Asociacion, Element };
use TDW\ACiencia\Factory\{ AsociacionFactory, EntityFactory };
use function PHPUnit\Framework\assertNotEmpty;

/**
 * Class AsociacionTest
 */
#[TestsAttr\Group('asociaciones')]
#[TestsAttr\CoversClass(Asociacion::class)]
#[TestsAttr\CoversClass(Element::class)]
#[TestsAttr\CoversClass(AsociacionFactory::class)]
#[TestsAttr\UsesClass(EntityFactory::class)]
class AsociacionTest extends TestCase
{
    protected static Asociacion $asociacion;

    private static \Faker\Generator $faker;

    public static function setUpBeforeClass(): void
    {
        self::$faker = \Faker\Factory::create('es_ES');
        $name = self::$faker->company();
        $websiteUrl = self::$faker->url();
        self::assertNotEmpty($name);
        self::$asociacion = AsociacionFactory::createElement($name, $websiteUrl);
    }

    public function testConstructor(): void
    {
        $name = self::$faker->company();
        $websiteUrl = self::$faker->url();
        self::assertNotEmpty($name);
        self::$asociacion = AsociacionFactory::createElement($name, $websiteUrl);

        self::assertSame(0, self::$asociacion->getId());
        self::assertSame($name, self::$asociacion->getName());
        self::assertSame($websiteUrl, self::$asociacion->getWebsiteUrl());
        self::assertEmpty(self::$asociacion->getEntities());
    }

    public function testGetId(): void
    {
        self::assertSame(0, self::$asociacion->getId());
    }

    public function testGetSetWebsiteUrl(): void
    {
        $websiteUrl = self::$faker->url();
        self::$asociacion->setWebsiteUrl($websiteUrl);
        static::assertSame($websiteUrl, self::$asociacion->getWebsiteUrl());
    }

    public function testGetSetName(): void
    {
        $name = self::$faker->company();
        self::$asociacion->setName($name);
        static::assertSame($name, self::$asociacion->getName());
    }

    public function testGetSetBirthDate(): void
    {
        $birthDate = self::$faker->dateTime();
        self::$asociacion->setBirthDate($birthDate);
        static::assertSame($birthDate, self::$asociacion->getBirthDate());
    }

    public function testGetSetDeathDate(): void
    {
        $deathDate = self::$faker->dateTime();
        self::$asociacion->setDeathDate($deathDate);
        static::assertSame($deathDate, self::$asociacion->getDeathDate());
    }

    public function testGetSetImageUrl(): void
    {
        $imageUrl = self::$faker->url();
        self::$asociacion->setImageUrl($imageUrl);
        static::assertSame($imageUrl, self::$asociacion->getImageUrl());
    }

    public function testGetSetWikiUrl(): void
    {
        $wikiUrl = self::$faker->url();
        self::$asociacion->setWikiUrl($wikiUrl);
        static::assertSame($wikiUrl, self::$asociacion->getWikiUrl());
    }

    public function testGetAddContainsRemoveEntities(): void
    {
        self::assertEmpty(self::$asociacion->getEntities());
        $entityName = self::$faker->company();
        self::assertNotEmpty($entityName);
        $entity = EntityFactory::createElement($entityName);

        self::$asociacion->addEntity($entity);
        self::$asociacion->addEntity($entity);  // Añadir dos veces
        self::assertNotEmpty(self::$asociacion->getEntities());
        self::assertTrue(self::$asociacion->containsEntity($entity));

        self::$asociacion->removeEntity($entity);
        self::assertFalse(self::$asociacion->containsEntity($entity));
        self::assertCount(0, self::$asociacion->getEntities());
        self::assertFalse(self::$asociacion->removeEntity($entity));
    }

    public function testToString(): void
    {
        $name = self::$faker->company();
        $birthDate = self::$faker->dateTime();
        $deathDate = self::$faker->dateTime();

        self::$asociacion->setName($name);
        self::$asociacion->setBirthDate($birthDate);
        self::$asociacion->setDeathDate($deathDate);

        self::assertStringContainsString($name, self::$asociacion->__toString());
        self::assertStringContainsString($birthDate->format('Y-m-d'), self::$asociacion->__toString());
        self::assertStringContainsString($deathDate->format('Y-m-d'), self::$asociacion->__toString());
    }

    public function testJsonSerialize(): void
    {
        $jsonStr = (string) json_encode(self::$asociacion, JSON_PARTIAL_OUTPUT_ON_ERROR);
        self::assertJson($jsonStr);
    }
}
