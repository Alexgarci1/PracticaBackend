<?php

namespace TDW\ACiencia\Factory;

use DateTime;
use TDW\ACiencia\Entity\Asociacion;

class AsociacionFactory
{
    public static function createElement(
        string $name,
        ?DateTime $birthDate = null,
        ?DateTime $deathDate = null,
        ?string $imageUrl = null,
        ?string $wikiUrl = null
    ): Asociacion {
        assert($name !== '');
        return self::newAsociacion($name, $birthDate, $deathDate, $imageUrl, $wikiUrl);
    }

    private static function newAsociacion(
        string $name,
        ?DateTime $birthDate,
        ?DateTime $deathDate,
        ?string $imageUrl,
        ?string $wikiUrl
    ): Asociacion {
        return new Asociacion($name, '', $birthDate, $deathDate, $imageUrl, $wikiUrl);
    }
}


