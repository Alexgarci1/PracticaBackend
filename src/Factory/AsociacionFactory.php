<?php

namespace TDW\ACiencia\Factory;

use DateTime;
use TDW\ACiencia\Entity\Asociacion;

class AsociacionFactory extends ElementFactory
{
    public static function createElement(
        string $name,
        ?DateTime $birthDate = null,
        ?DateTime $deathDate = null,
        ?string $imageUrl = null,
        ?string $wikiUrl = null
    ): Asociacion {
        assert($name !== '');
        return new Asociacion($name, '', $birthDate, $deathDate, $imageUrl, $wikiUrl);

    }
}
