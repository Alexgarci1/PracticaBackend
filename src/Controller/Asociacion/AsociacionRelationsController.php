<?php

/**
 * src/Controller/Asociacion/AsociacionRelationsController.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace TDW\ACiencia\Controller\Asociacion;

use Doctrine\ORM;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Http\Response;
use TDW\ACiencia\Controller\Element\ElementRelationsBaseController;
use TDW\ACiencia\Entity\Entity;

/**
 * Class AsociacionRelationsController
 */
final class AsociacionRelationsController extends ElementRelationsBaseController
{
    public static function getEntityClassName(): string
    {
        return AsociacionQueryController::getEntityClassName();
    }

    public static function getEntitiesTag(): string
    {
        return AsociacionQueryController::getEntitiesTag();
    }

    public static function getEntityIdName(): string
    {
        return AsociacionQueryController::getEntityIdName();
    }

    /**
     * GET /asociaciones/{asociacionId}/entities
     */
    public function getEntities(Request $request, Response $response, array $args): Response
    {
        $asociacionId = (int) ($args[static::getEntityIdName()] ?? 0);

        if ($asociacionId <= 0 || $asociacionId > 2147483647) {
            return $this->getElements($request, $response, null, 'entities', []);
        }

        $asociacion = $this->entityManager
            ->getRepository(static::getEntityClassName())
            ->find($asociacionId);

        $entities = $asociacion?->getEntities()->toArray() ?? [];

        return $this->getElements($request, $response, $asociacion, 'entities', $entities);
    }

    /**
     * PUT /asociaciones/{asociacionId}/entities/add/{stuffId}
     * PUT /asociaciones/{asociacionId}/entities/rem/{stuffId}
     */
    public function operationEntity(Request $request, Response $response, array $args): Response
    {
        return $this->operationRelatedElements(
            $request,
            $response,
            $args,
            Entity::class
        );
    }
}
