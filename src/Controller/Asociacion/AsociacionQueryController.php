<?php

/**
 * src/Controller/Asociacion/AsociacionQueryController.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace TDW\ACiencia\Controller\Asociacion;

use TDW\ACiencia\Controller\Element\ElementBaseQueryController;
use TDW\ACiencia\Entity\Asociacion;

/**
 * Class AsociacionQueryController
 */
class AsociacionQueryController extends ElementBaseQueryController
{
    /** @var string ruta api gestión asociaciones  */
    public const PATH_ASOCIACIONES = '/asociaciones';

    public static function getEntitiesTag(): string
    {
        return substr(self::PATH_ASOCIACIONES, 1);
    }

    public static function getEntityClassName(): string
    {
        return Asociacion::class;
    }

    public static function getEntityIdName(): string
    {
        return 'asociacionId';
    }
}
