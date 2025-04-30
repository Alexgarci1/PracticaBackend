<?php

/**
 * src/Controller/Entity/EntityRelationsController.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace TDW\ACiencia\Controller\Entity;

use Doctrine\ORM;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Http\Response;
use TDW\ACiencia\Controller\Element\ElementRelationsBaseController;
use TDW\ACiencia\Controller\Person\PersonQueryController;
use TDW\ACiencia\Controller\Product\ProductQueryController;
use TDW\ACiencia\Entity\Entity;

/**
 * Class EntityRelationsController
 */
final class EntityRelationsController extends ElementRelationsBaseController
{
    public static function getEntityClassName(): string
    {
        return EntityQueryController::getEntityClassName();
    }

    public static function getEntitiesTag(): string
    {
        return EntityQueryController::getEntitiesTag();
    }

    public static function getEntityIdName(): string
    {
        return EntityQueryController::getEntityIdName();
    }

    /**
     * Summary: GET /entities/{entityId}/persons
     *
     * @param Request $request
     * @param Response $response
     * @param array<string,mixed> $args
     *
     * @return Response
     */
    public function getPersons(Request $request, Response $response, array $args): Response
    {
        // @TODO
        $entityId = $args[static::getEntityIdName()] ?? 0;
        if ($entityId <= 0 || $entityId > 2147483647) {   // 404
            return $this->getElements($request, $response, null, PersonQueryController::getEntitiesTag(), []);
        }

        /** @var Entity|null $entity */
        $entity = $this->entityManager
            ->getRepository(static::getEntityClassName())
            ->find($entityId);

        $persons = $entity?->getPersons()->getValues() ?? [];

        return $this->getElements($request, $response, $entity, PersonQueryController::getEntitiesTag(), $persons);

    }

    /**
     * PUT /entities/{entityId}/persons/add/{elementId}
     * PUT /entities/{entityId}/persons/rem/{elementId}
     *
     * @param Request $request
     * @param Response $response
     * @param array<string,mixed> $args
     *
     * @return Response
     * @throws ORM\Exception\ORMException
     */
    public function operationPerson(Request $request, Response $response, array $args): Response
    {
        // @TODO
        return $this->operationRelatedElements(
            $request,
            $response,
            $args,
            \TDW\ACiencia\Entity\Person::class
        );
    }

    /**
     * Summary: GET /entities/{entityId}/products
     *
     * @param Request $request
     * @param Response $response
     * @param array<string,mixed> $args
     *
     * @return Response
     */
    public function getProducts(Request $request, Response $response, array $args): Response
    {
        // @TODO
        $entityId = $args[static::getEntityIdName()] ?? 0;

        // Validar que el entityId es un valor válido
        if ($entityId <= 0 || $entityId > 2147483647) {
            // Si no es válido, devolver la respuesta con el formato adecuado
            return $this->getElements($request, $response, null, 'products', []);
        }

        // Buscar la entidad en la base de datos
        /** @var Entity|null $entity */
        $entity = $this->entityManager
            ->getRepository(static::getEntityClassName())
            ->find($entityId);

        // Obtener los productos relacionados con la entidad (si existen)
        $products = $entity?->getProducts()->toArray() ?? [];

        // Devolver los productos relacionados con la entidad a través de getElements
        return $this->getElements($request, $response, $entity, 'products', $products);
    }

    /**
     * PUT /entities/{entityId}/products/add/{elementId}
     * PUT /entities/{entityId}/products/rem/{elementId}
     *
     * @param Request $request
     * @param Response $response
     * @param array<string,mixed> $args
     *
     * @return Response
     * @throws ORM\Exception\ORMException
     */
    public function operationProduct(Request $request, Response $response, array $args): Response
    {
        // @TODO
        return $this->operationRelatedElements(
            $request,
            $response,
            $args,
            \TDW\ACiencia\Entity\Product::class
        );
    }
}
