<?php

namespace TDW\Test\ACiencia\Controller\Asociaciones;

use Doctrine\ORM\EntityManagerInterface;
use JsonException;
use PHPUnit\Framework\Attributes as TestsAttr;
use TDW\ACiencia\Controller\Asociacion\AsociacionRelationsController;
use TDW\ACiencia\Controller\Element\ElementRelationsBaseController;
use TDW\ACiencia\Entity\{Asociacion, Entity};
use TDW\ACiencia\Factory\{AsociacionFactory, EntityFactory};
use TDW\ACiencia\Utility\DoctrineConnector;
use TDW\ACiencia\Utility\Utils;
use TDW\Test\ACiencia\Controller\BaseTestCase;

#[TestsAttr\CoversClass(AsociacionRelationsController::class)]
#[TestsAttr\CoversClass(ElementRelationsBaseController::class)]
final class AsociacionesRelationsControllerTest extends BaseTestCase
{
    protected const RUTA_API = '/api/v1/asociaciones';

    protected static array $writer;
    protected static array $reader;

    protected static ?EntityManagerInterface $entityManager = null;

    private static Asociacion $asociacion;
    private static Entity $entity;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$writer = [
            'username' => (string) getenv('ADMIN_USER_NAME'),
            'email'    => (string) getenv('ADMIN_USER_EMAIL'),
            'password' => (string) getenv('ADMIN_USER_PASSWD'),
        ];

        self::$reader = [
            'username' => self::$faker->userName(),
            'email'    => self::$faker->email(),
            'password' => self::$faker->password(),
        ];

        self::$writer['id'] = Utils::loadUserData(
            self::$writer['username'],
            self::$writer['email'],
            self::$writer['password'],
            true
        );
        self::$reader['id'] = Utils::loadUserData(
            self::$reader['username'],
            self::$reader['email'],
            self::$reader['password'],
            false
        );

        self::$entityManager = DoctrineConnector::getEntityManager();

        self::$entity = EntityFactory::createElement(self::$faker->company());
        self::$asociacion = AsociacionFactory::createElement(self::$faker->company(),self::$faker->url());

        self::$entityManager->persist(self::$entity);
        self::$entityManager->persist(self::$asociacion);
        self::$entityManager->flush();
    }

    public function testOptionsRelationship204(): void
    {
        $response = $this->runApp(
            'OPTIONS',
            self::RUTA_API . '/' . self::$asociacion->getId() . '/entities'
        );
        self::assertSame(204, $response->getStatusCode());
        self::assertNotEmpty($response->getHeader('Allow'));
        self::assertEmpty($response->getBody()->getContents());

        $response = $this->runApp(
            'OPTIONS',
            self::RUTA_API . '/' . self::$asociacion->getId() . '/entities/add/' . self::$entity->getId()
        );
        self::assertSame(204, $response->getStatusCode());
        self::assertNotEmpty($response->getHeader('Allow'));
        self::assertEmpty($response->getBody()->getContents());
    }

    public function testAddEntity209(): void
    {
        self::$writer['authHeader'] = $this->getTokenHeaders(self::$writer['username'], self::$writer['password']);
        $response = $this->runApp(
            'PUT',
            self::RUTA_API . '/' . self::$asociacion->getId() . '/entities/add/' . self::$entity->getId(),
            null,
            self::$writer['authHeader']
        );
        self::assertSame(209, $response->getStatusCode());
        self::assertJson($response->getBody()->getContents());
    }

    #[TestsAttr\Depends('testAddEntity209')]
    public function testGetEntities200OkWithElements(): void
    {
        self::$reader['authHeader'] = $this->getTokenHeaders(self::$reader['username'], self::$reader['password']);
        $response = $this->runApp(
            'GET',
            self::RUTA_API . '/' . self::$asociacion->getId() . '/entities',
            null,
            self::$reader['authHeader']
        );
        self::assertSame(200, $response->getStatusCode());
        $r_body = $response->getBody()->getContents();
        self::assertJson($r_body);
        $r_data = json_decode($r_body, true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('entities', $r_data);
        self::assertSame(
            self::$entity->getName(),
            $r_data['entities'][0]['entity']['name']
        );
    }

    #[TestsAttr\Depends('testGetEntities200OkWithElements')]
    public function testRemoveEntity209(): void
    {
        $response = $this->runApp(
            'PUT',
            self::RUTA_API . '/' . self::$asociacion->getId() . '/entities/rem/' . self::$entity->getId(),
            null,
            self::$writer['authHeader']
        );
        self::assertSame(209, $response->getStatusCode());
        $r_body = $response->getBody()->getContents();
        self::assertJson($r_body);
        $r_data = json_decode($r_body, true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('entities', $r_data['asociacion']);
        self::assertEmpty($r_data['asociacion']['entities']);
    }

    #[TestsAttr\Depends('testRemoveEntity209')]
    public function testGetEntities200OkEmpty(): void
    {
        $response = $this->runApp(
            'GET',
            self::RUTA_API . '/' . self::$asociacion->getId() . '/entities',
            null,
            self::$reader['authHeader']
        );
        self::assertSame(200, $response->getStatusCode());
        $r_body = $response->getBody()->getContents();
        self::assertJson($r_body);
        $r_data = json_decode($r_body, true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('entities', $r_data);
        self::assertEmpty($r_data['entities']);
    }


    /**
     * @param string $method
     * @param string $uri
     * @param int $status
     * @param string $user
     *
     * @return void
     */
    #[TestsAttr\DataProvider('routeExceptionProvider')]
    public function testAsociacionRelationshipErrors(string $method, string $uri, int $status, string $user = ''): void
    {
        $requestingUser = match ($user) {
            'admin'  => self::$writer,
            'reader' => self::$reader,
            default  => ['username' => '', 'password' => '']
        };

        $response = $this->runApp(
            $method,
            $uri,
            null,
            $this->getTokenHeaders($requestingUser['username'], $requestingUser['password'])
        );
        $this->internalTestError($response, $status);
    }

    // --------------
    // DATA PROVIDERS
    // --------------

    /**
     * Route provider (expected statuses: 401, 403, 404, 406)
     *
     * @return array<string,mixed> [ method, url, status, user ]
     */
    public static function routeExceptionProvider(): array
    {
        return [
            // 401 Unauthorized
            'putAddEntity401' => ['PUT', self::RUTA_API . '/1/entities/add/1', 401],
            'putRemoveEntity401' => ['PUT', self::RUTA_API . '/1/entities/rem/1', 401],

            // 403 Forbidden
            'putAddEntity403' => ['PUT', self::RUTA_API . '/1/entities/add/1', 403, 'reader'],
            'putRemoveEntity403' => ['PUT', self::RUTA_API . '/1/entities/rem/1', 403, 'reader'],

            // 404 Not Found
            'getEntities404' => ['GET', self::RUTA_API . '/0/entities', 404, 'admin'],
            'putAddEntity404' => ['PUT', self::RUTA_API . '/0/entities/add/1', 404, 'admin'],
            'putRemoveEntity404' => ['PUT', self::RUTA_API . '/0/entities/rem/1', 404, 'admin'],

            // 406 Not Acceptable
            'putAddEntity406' => ['PUT', self::RUTA_API . '/1/entities/add/100', 406, 'admin'],
            'putRemoveEntity406' => ['PUT', self::RUTA_API . '/1/entities/rem/100', 406, 'admin'],
        ];
    }
}
