<?php

/**
 * src/Controller/Asociacion/AsociacionCommandController.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace TDW\ACiencia\Controller\Asociacion;

use TDW\ACiencia\Controller\Element\ElementBaseCommandController;
use TDW\ACiencia\Entity\Asociacion;
use TDW\ACiencia\Factory\AsociacionFactory;
use Doctrine\Common\Collections\Criteria;
use Fig\Http\Message\StatusCodeInterface as StatusCode;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Http\Response;
use TDW\ACiencia\Utility\Error;

/**
 * Class AsociacionCommandController
 */
class AsociacionCommandController extends ElementBaseCommandController
{
    /** @var string ruta api gestión asociaciones  */
    public const PATH_ASOCIACIONES = '/asociaciones';

    public static function getEntityClassName(): string
    {
        return Asociacion::class;
    }

    protected static function getFactoryClassName(): string
    {
        return AsociacionFactory::class;
    }

    public static function getEntityIdName(): string
    {
        return 'asociacionId';
    }

    public function post(Request $request, Response $response): Response
    {
        assert($request->getMethod() === 'POST');
        if (!$this->checkWriterScope($request)) {
            return Error::createResponse($response, StatusCode::STATUS_FORBIDDEN);
        }

        $req_data = (array) $request->getParsedBody();

        if (!isset($req_data['name'], $req_data['websiteUrl'])) {
            return Error::createResponse($response, StatusCode::STATUS_UNPROCESSABLE_ENTITY);
        }

        $criteria = new Criteria(Criteria::expr()->eq('name', $req_data['name']));
        if ($this->entityManager->getRepository(static::getEntityClassName())->matching($criteria)->count() !== 0) {
            return Error::createResponse($response, StatusCode::STATUS_BAD_REQUEST);
        }

        $elementFactory = static::getFactoryClassName();
        $element = $elementFactory::createElement($req_data['name']);
        $this->updateAsociacionElement($element, $req_data);
        $this->entityManager->persist($element);
        $this->entityManager->flush();

        return $response
            ->withAddedHeader(
                'Location',
                $request->getUri() . '/' . $element->getId()
            )
            ->withJson($element, StatusCode::STATUS_CREATED);
    }

    // Sobreescribimos SOLO put
    public function put(Request $request, Response $response, array $args): Response
    {
        assert($request->getMethod() === 'PUT');
        if (!$this->checkWriterScope($request)) {
            return Error::createResponse($response, StatusCode::STATUS_NOT_FOUND);
        }

        $req_data = (array) $request->getParsedBody();
        $idName = static::getEntityIdName();
        if ($args[$idName] <= 0 || $args[$idName] > 2147483647) {
            return Error::createResponse($response, StatusCode::STATUS_NOT_FOUND);
        }

        $this->entityManager->beginTransaction();
        /** @var Asociacion|null $element */
        $element = $this->entityManager->getRepository(static::getEntityClassName())->find($args[$idName]);

        if (!$element instanceof Asociacion) {
            $this->entityManager->rollback();
            return Error::createResponse($response, StatusCode::STATUS_NOT_FOUND);
        }

        $etag = md5((string) json_encode($element));
        if (!in_array($etag, $request->getHeader('If-Match'), true)) {
            $this->entityManager->rollback();
            return Error::createResponse($response, StatusCode::STATUS_PRECONDITION_REQUIRED);
        }

        if (isset($req_data['name'])) {
            $elementId = $this->findIdByName(static::getEntityClassName(), $req_data['name']);
            if (($elementId !== 0) && (intval($args[$idName]) !== $elementId)) {
                $this->entityManager->rollback();
                return Error::createResponse($response, StatusCode::STATUS_BAD_REQUEST);
            }
            $element->setName($req_data['name']);
        }

        $this->updateAsociacionElement($element, $req_data);
        $this->entityManager->flush();
        $this->entityManager->commit();

        return $response
            ->withStatus(209, 'Content Returned')
            ->withJson($element);
    }

    private function findIdByName(string $entityName, string $value): int
    {
        /** @var ?Asociacion $element */
        $element = $this->entityManager->getRepository($entityName)->findOneBy(['name' => $value]);
        return (int) $element?->getId();
    }

    private function updateAsociacionElement(Asociacion $asociacion, array $data): void
    {
        foreach ($data as $attr => $datum) {
            switch ($attr) {
                case 'birthDate':
                    $date = \DateTime::createFromFormat('!Y-m-d', $datum);
                    if ($date instanceof \DateTime) {
                        $asociacion->setBirthDate($date);
                    }
                    break;
                case 'deathDate':
                    $date = \DateTime::createFromFormat('!Y-m-d', $datum);
                    if ($date instanceof \DateTime) {
                        $asociacion->setDeathDate($date);
                    }
                    break;
                case 'imageUrl':
                    $asociacion->setImageUrl($datum);
                    break;
                case 'wikiUrl':
                    $asociacion->setWikiUrl($datum);
                    break;
                case 'websiteUrl':
                    $asociacion->setWebsiteUrl($datum);
                    break;
            }
        }
    }
}
