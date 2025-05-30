<?php

/**
 * tests/Controller/Product/ProductRelationsControllerTest.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace TDW\Test\ACiencia\Controller\Product;

use Doctrine\ORM\EntityManagerInterface;
use JsonException;
use PHPUnit\Framework\Attributes as TestsAttr;
use TDW\ACiencia\Controller\Element\ElementRelationsBaseController;
use TDW\ACiencia\Controller\Product\{ ProductQueryController, ProductRelationsController };
use TDW\ACiencia\Entity\{ Entity, Person, Product };
use TDW\ACiencia\Factory\{ EntityFactory, PersonFactory, ProductFactory };
use TDW\ACiencia\Utility\{ DoctrineConnector, Utils };
use TDW\Test\ACiencia\Controller\BaseTestCase;

/**
 * Class ProductRelationsControllerTest
 */
#[TestsAttr\CoversClass(ProductRelationsController::class)]
#[TestsAttr\CoversClass(ElementRelationsBaseController::class)]
final class ProductRelationsControllerTest extends BaseTestCase
{
    /** @var string Path para la gestión de usuarios */
    protected const RUTA_API = '/api/v1/products';

    /** @var array<string,mixed> Admin data */
    protected static array $writer;

    /** @var array<string,mixed> reader user data */
    protected static array $reader;

    protected static ?EntityManagerInterface $entityManager;

    private static Product $product;
    private static Entity $entity;
    private static Person $person;

    /**
     * Se ejecuta una vez al inicio de las pruebas de la clase UserControllerTest
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$writer = [
            'username' => (string) getenv('ADMIN_USER_NAME'),
            'email'    => (string) getenv('ADMIN_USER_EMAIL'),
            'password' => (string) getenv('ADMIN_USER_PASSWD'),
        ];

        // load user admin fixtures
        self::$writer['id'] = Utils::loadUserData(
            self::$writer['username'],
            self::$writer['email'],
            self::$writer['password'],
            true
        );

        // load user reader fixtures
        self::$reader = [
            'username' => self::$faker->userName(),
            'email'    => self::$faker->email(),
            'password' => self::$faker->password(),
        ];
        self::$reader['id'] = Utils::loadUserData(
            self::$reader['username'],
            self::$reader['email'],
            self::$reader['password'],
            false
        );

        // create and insert fixtures
        $entityName = self::$faker->company();
        self::assertNotEmpty($entityName);
        self::$entity  = EntityFactory::createElement($entityName);

        $personName = self::$faker->name();
        self::assertNotEmpty($personName);
        self::$person  = PersonFactory::createElement($personName);

        $productName = self::$faker->slug();
        self::assertNotEmpty($productName);
        self::$product  = ProductFactory::createElement($productName);

        self::$entityManager = DoctrineConnector::getEntityManager();
        self::$entityManager->persist(self::$product);
        self::$entityManager->persist(self::$entity);
        self::$entityManager->persist(self::$person);
        self::$entityManager->flush();
    }

    public function testGetEntitiesTag(): void
    {
        self::assertSame(
            ProductQueryController::getEntitiesTag(),
            ProductRelationsController::getEntitiesTag()
        );
    }

    // *******************
    // Product -> Entities
    // *******************
    /**
     * OPTIONS /products/{productId}/entities
     * OPTIONS /products/{productId}/entities/add/{idEntity}
     */
    public function testOptionsRelationship204(): void
    {
        $response = $this->runApp(
            'OPTIONS',
            self::RUTA_API . '/' . self::$product->getId() . '/entities'
        );
        self::assertSame(204, $response->getStatusCode());
        self::assertNotEmpty($response->getHeader('Allow'));
        self::assertEmpty($response->getBody()->getContents());

        $response = $this->runApp(
            'OPTIONS',
            self::RUTA_API . '/' . self::$product->getId()
            . '/entities/add/' . self::$entity->getId()
        );
        self::assertSame(204, $response->getStatusCode());
        self::assertNotEmpty($response->getHeader('Allow'));
        self::assertEmpty($response->getBody()->getContents());
    }

    /**
     * PUT /products/{productId}/entities/add/{idEntity}
     */
    public function testAddEntity209(): void
    {
        self::$writer['authHeader'] = $this->getTokenHeaders(self::$writer['username'], self::$writer['password']);
        $response = $this->runApp(
            'PUT',
            self::RUTA_API . '/' . self::$product->getId()
                . '/entities/add/' . self::$entity->getId(),
            null,
            self::$writer['authHeader']
        );
        self::assertSame(209, $response->getStatusCode());
        self::assertJson($response->getBody()->getContents());
    }

    /**
     * GET /products/{productId}/entities 200 Ok
     *
     * @return void
     * @throws JsonException
     */
    #[TestsAttr\Depends('testAddEntity209')]
    public function testGetEntities200OkWithElements(): void
    {
        self::$reader['authHeader'] = $this->getTokenHeaders(self::$reader['username'], self::$reader['password']);
        $response = $this->runApp(
            'GET',
            self::RUTA_API . '/' . self::$product->getId() . '/entities',
            null,
            self::$reader['authHeader']
        );
        self::assertSame(200, $response->getStatusCode());
        $r_body = $response->getBody()->getContents();
        self::assertJson($r_body);
        $responseEntities = json_decode($r_body, true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('entities', $responseEntities);
        self::assertSame(
            self::$entity->getName(),
            $responseEntities['entities'][0]['entity']['name']
        );
    }

    /**
     * PUT /products/{productId}/entities/rem/{idEntity}
     *
     * @return void
     * @throws JsonException
     */
    #[TestsAttr\Depends('testGetEntities200OkWithElements')]
    public function testRemoveEntity209(): void
    {
        $response = $this->runApp(
            'PUT',
            self::RUTA_API . '/' . self::$product->getId()
            . '/entities/rem/' . self::$entity->getId(),
            null,
            self::$writer['authHeader']
        );
        self::assertSame(209, $response->getStatusCode());
        $r_body = $response->getBody()->getContents();
        self::assertJson($r_body);
        $responseProduct = json_decode($r_body, true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('entities', $responseProduct['product']);
        self::assertEmpty($responseProduct['product']['entities']);
    }

    /**
     * GET /products/{productId}/entities 200 Ok - Empty
     *
     * @return void
     * @throws JsonException
     */
    #[TestsAttr\Depends('testRemoveEntity209')]
    public function testGetEntities200OkEmpty(): void
    {
        $response = $this->runApp(
            'GET',
            self::RUTA_API . '/' . self::$product->getId() . '/entities',
            null,
            self::$reader['authHeader']
        );
        self::assertSame(200, $response->getStatusCode());
        $r_body = $response->getBody()->getContents();
        self::assertJson($r_body);
        $responseEntities = json_decode($r_body, true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('entities', $responseEntities);
        self::assertEmpty($responseEntities['entities']);
    }

    // ******************
    // Product -> Persons
    // ******************
    /**
     * PUT /products/{productId}/persons/add/{idPerson}
     */
    public function testAddPerson209(): void
    {
        self::$writer['authHeader'] = $this->getTokenHeaders(self::$writer['username'], self::$writer['password']);
        $response = $this->runApp(
            'PUT',
            self::RUTA_API . '/' . self::$product->getId()
            . '/persons/add/' . self::$person->getId(),
            null,
            self::$writer['authHeader']
        );
        self::assertSame(209, $response->getStatusCode());
        self::assertJson($response->getBody()->getContents());
    }

    /**
     * GET /products/{productId}/persons 200 Ok
     *
     * @return void
     * @throws JsonException
     */
    #[TestsAttr\Depends('testAddPerson209')]
    public function testGetPersons200OkWithElements(): void
    {
        self::$reader['authHeader'] = $this->getTokenHeaders(self::$reader['username'], self::$reader['password']);
        $response = $this->runApp(
            'GET',
            self::RUTA_API . '/' . self::$product->getId() . '/persons',
            null,
            self::$reader['authHeader']
        );
        self::assertSame(200, $response->getStatusCode());
        $r_body = $response->getBody()->getContents();
        self::assertJson($r_body);
        $responsePersons = json_decode($r_body, true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('persons', $responsePersons);
        self::assertSame(
            self::$person->getName(),
            $responsePersons['persons'][0]['person']['name']
        );
    }

    /**
     * PUT /products/{productId}/persons/rem/{idPerson}
     *
     * @return void
     * @throws JsonException
     */
    #[TestsAttr\Depends('testGetPersons200OkWithElements')]
    public function testRemovePerson209(): void
    {
        $response = $this->runApp(
            'PUT',
            self::RUTA_API . '/' . self::$product->getId()
            . '/persons/rem/' . self::$person->getId(),
            null,
            self::$writer['authHeader']
        );
        self::assertSame(209, $response->getStatusCode());
        $r_body = $response->getBody()->getContents();
        self::assertJson($r_body);
        $responseProduct = json_decode($r_body, true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('persons', $responseProduct['product']);
        self::assertEmpty($responseProduct['product']['persons']);
    }

    /**
     * GET /products/{productId}/persons 200 Ok - Empty
     *
     * @return void
     * @throws JsonException
     */
    #[TestsAttr\Depends('testRemovePerson209')]
    public function testGetPersons200OkEmpty(): void
    {
        $response = $this->runApp(
            'GET',
            self::RUTA_API . '/' . self::$product->getId() . '/persons',
            null,
            self::$reader['authHeader']
        );
        self::assertSame(200, $response->getStatusCode());
        $r_body = $response->getBody()->getContents();
        self::assertJson($r_body);
        $responsePersons = json_decode($r_body, true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('persons', $responsePersons);
        self::assertEmpty($responsePersons['persons']);
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
    public function testProductRelationshipErrors(string $method, string $uri, int $status, string $user = ''): void
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
     * Route provider (expected status: 404 NOT FOUND)
     *
     * @return array<string,mixed> [ method, url, path, status ]
     */
    public static function routeExceptionProvider(): array
    {
        return [
            // 401
            // 'getEntities401'     => [ 'GET', self::RUTA_API . '/1/entities',       401],
            'putAddEntity401'    => [ 'PUT', self::RUTA_API . '/1/entities/add/1', 401],
            'putRemoveEntity401' => [ 'PUT', self::RUTA_API . '/1/entities/rem/1', 401],
            // 'getPersons401'      => [ 'GET', self::RUTA_API . '/1/persons',        401],
            'putAddPerson401'    => [ 'PUT', self::RUTA_API . '/1/persons/add/1',  401],
            'putRemovePerson401' => [ 'PUT', self::RUTA_API . '/1/persons/rem/1',  401],

            // 403
            'putAddEntity403'    => [ 'PUT', self::RUTA_API . '/1/entities/add/1', 403, 'reader'],
            'putRemoveEntity403' => [ 'PUT', self::RUTA_API . '/1/entities/rem/1', 403, 'reader'],
            'putAddPerson403'    => [ 'PUT', self::RUTA_API . '/1/persons/add/1',  403, 'reader'],
            'putRemovePerson403' => [ 'PUT', self::RUTA_API . '/1/persons/rem/1',  403, 'reader'],

            // 404
            'getEntities404'     => [ 'GET', self::RUTA_API . '/0/entities',       404, 'admin'],
            'putAddEntity404'    => [ 'PUT', self::RUTA_API . '/0/entities/add/1', 404, 'admin'],
            'putRemoveEntity404' => [ 'PUT', self::RUTA_API . '/0/entities/rem/1', 404, 'admin'],
            'getPersons404'      => [ 'GET', self::RUTA_API . '/0/persons',        404, 'admin'],
            'putAddPerson404'    => [ 'PUT', self::RUTA_API . '/0/persons/add/1',  404, 'admin'],
            'putRemovePerson404' => [ 'PUT', self::RUTA_API . '/0/persons/rem/1',  404, 'admin'],

            // 406
            'putAddEntity406'    => [ 'PUT', self::RUTA_API . '/1/entities/add/100', 406, 'admin'],
            'putRemoveEntity406' => [ 'PUT', self::RUTA_API . '/1/entities/rem/100', 406, 'admin'],
            'putAddPerson406'    => [ 'PUT', self::RUTA_API . '/1/persons/add/100',  406, 'admin'],
            'putRemovePerson406' => [ 'PUT', self::RUTA_API . '/1/persons/rem/100',  406, 'admin'],
        ];
    }
}
