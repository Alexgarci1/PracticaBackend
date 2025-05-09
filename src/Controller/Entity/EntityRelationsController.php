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
use TDW\ACiencia\Entity\Asociacion;
use TDW\ACiencia\Entity\Entity;
use TDW\ACiencia\Entity\Person;
use TDW\ACiencia\Entity\Product;
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
            Person::class
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

        if ($entityId <= 0 || $entityId > 2147483647) {

            return $this->getElements($request, $response, null, 'products', []);
        }

        /** @var Entity|null $entity */
        $entity = $this->entityManager
            ->getRepository(static::getEntityClassName())
            ->find($entityId);

        $products = $entity?->getProducts()->toArray() ?? [];
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
            Product::class
        );
    }

    public function getAsociaciones(Request $request, Response $response, array $args): Response
    {
        $entityId = $args[static::getEntityIdName()] ?? 0;

        if ($entityId <= 0 || $entityId > 2147483647) {
            return $this->getElements($request, $response, null, 'asociaciones', []);
        }

        /** @var Entity|null $entity */
        $entity = $this->entityManager
            ->getRepository(static::getEntityClassName())
            ->find($entityId);

        $asociaciones = $entity?->getAsociaciones()->toArray() ?? [];
        return $this->getElements($request, $response, $entity, 'asociaciones', $asociaciones);
    }


    /**
     * PUT /entities/{entityId}/associations/add/{elementId}
     * PUT /entities/{entityId}/associations/rem/{elementId}
     *
     * @param Request $request
     * @param Response $response
     * @param array<string,mixed> $args
     *
     * @return Response
     * @throws ORM\Exception\ORMException
     */
    public function operationAsociacion(Request $request, Response $response, array $args): Response
    {
        return $this->operationRelatedElements(
            $request,
            $response,
            $args,
            Asociacion::class
        );
    }

}
