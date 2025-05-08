<?php

/**
 * tests/Controller/Asociaciones/AsociacionesControllerTest.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace TDW\Test\ACiencia\Controller\Asociaciones;

use Fig\Http\Message\StatusCodeInterface as StatusCode;
use PHPUnit\Framework\Attributes as TestsAttr;
use TDW\ACiencia\Controller\Asociacion\{ AsociacionCommandController, AsociacionQueryController };
use TDW\ACiencia\Controller\Element\{ ElementBaseCommandController, ElementBaseQueryController };
use TDW\ACiencia\Utility\Utils;
use TDW\Test\ACiencia\Controller\BaseTestCase;

/**
 * Class AsociacionesControllerTest
 */
#[TestsAttr\CoversClass(AsociacionCommandController::class)]
#[TestsAttr\CoversClass(AsociacionQueryController::class)]
#[TestsAttr\CoversClass(ElementBaseCommandController::class)]
#[TestsAttr\CoversClass(ElementBaseQueryController::class)]
class AsociacionesControllerTest extends BaseTestCase
{
    /** @var string Path para la gestión de asociaciones */
    protected const RUTA_API = '/api/v1/asociaciones';

    /** @var array<string,mixed> Admin data */
    protected static array $writer;

    /** @var array<string,mixed> reader user data */
    protected static array $reader;

    /**
     * Se ejecuta una vez al inicio de las pruebas de la clase
     */
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

        // load user admin fixtures
        self::$writer['id'] = Utils::loadUserData(
            self::$writer['username'],
            self::$writer['email'],
            self::$writer['password'],
            true
        );

        // load user reader fixtures
        self::$reader['id'] = Utils::loadUserData(
            self::$reader['username'],
            self::$reader['email'],
            self::$reader['password'],
            false
        );
    }

    /**
     * Test GET /asociaciones 404 NOT FOUND
     */
    public function testCGetAsociacion404NotFound(): void
    {
        self::$writer['authHeader'] =
            $this->getTokenHeaders(self::$writer['username'], self::$writer['password']);
        $response = $this->runApp(
            'GET',
            self::RUTA_API,
            null,
            self::$writer['authHeader']
        );
        $this->internalTestError($response, StatusCode::STATUS_NOT_FOUND);
    }

    /**
     * Test POST /asociaciones 201 CREATED
     *
     * @return array<string,string|int> ProductData
     * @throws \JsonException
     */
    #[TestsAttr\Depends('testCGetAsociacion404NotFound')]
    public function testPostAsociacion201Created(): array
    {
        $p_data = [
            'name'       => self::$faker->words(3, true),
            'websiteUrl' => self::$faker->url(),
            'birthDate'  => self::$faker->date(),
            'deathDate'  => self::$faker->date(),
            'imageUrl'   => self::$faker->url(),
            'wikiUrl'    => self::$faker->url(),
        ];
        $response = $this->runApp(
            'POST',
            self::RUTA_API,
            $p_data,
            self::$writer['authHeader']
        );
        self::assertSame(201, $response->getStatusCode());
        self::assertNotEmpty($response->getHeader('Location'));
        $r_body = $response->getBody()->getContents();
        self::assertJson($r_body);
        $responseAsociacion = json_decode($r_body, true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('asociacion', $responseAsociacion);
        $AsociacionData = $responseAsociacion['asociacion'];
        self::assertNotEquals(0, $AsociacionData['id']);
        self::assertSame($p_data['name'], $AsociacionData['name']);
        self::assertSame($p_data['birthDate'], $AsociacionData['birthDate']);
        self::assertSame($p_data['deathDate'], $AsociacionData['deathDate']);
        self::assertSame($p_data['imageUrl'], $AsociacionData['imageUrl']);
        self::assertSame($p_data['wikiUrl'], $AsociacionData['wikiUrl']);

        return $AsociacionData;
    }

    /**
     * Test POST /users 422 UNPROCESSABLE ENTITY
     */
    #[TestsAttr\Depends('testCGetAsociacion404NotFound')]
    public function testPostAsociacion422UnprocessableEntity(): void
    {
        $p_data = [
            // 'name'      => self::$faker->words(3, true),
            // 'websiteUrl' => self::$faker->url(),
            'birthDate' => self::$faker->date(),
            'deathDate' => self::$faker->date(),
            'imageUrl'  => self::$faker->url(), // imageUrl(),
            'wikiUrl'   => self::$faker->url()
        ];
        $response = $this->runApp(
            'POST',
            self::RUTA_API,
            $p_data,
            self::$writer['authHeader']
        );
        $this->internalTestError($response, StatusCode::STATUS_UNPROCESSABLE_ENTITY);
    }

    /**
     * Test POST /asociaciones 400 BAD REQUEST
     *
     * @param array<string,string|int> $asociaciones data returned by testPostAsociacion201Created()
     */
    #[TestsAttr\Depends('testPostAsociacion201Created')]
    public function testPostAsociacion400BadRequest(array $asociaciones): void
    {

        $p_data = ['name' => $asociaciones['name'], 'websiteUrl' => self::$faker->url()];

        $response = $this->runApp(
            'POST',
            self::RUTA_API,
            $p_data,
            self::$writer['authHeader']
        );
        $this->internalTestError($response, StatusCode::STATUS_BAD_REQUEST);
    }

    /**
     * Test GET /asociaciones 200 OK
     *
     * @param array<string,string|int> $asociaciones data returned by testPostProduct201Created()
     * @return array<string> ETag header
     * @throws \JsonException
     */
    #[TestsAttr\Depends('testPostAsociacion201Created')]
    public function testCGetAsociacion200Ok(array $asociaciones): array
    {
        self::assertIsString($asociaciones['name']);
        $response = $this->runApp(
            'GET',
            self::RUTA_API . '?name=' . substr($asociaciones['name'], 0, -2),
            null,
            self::$writer['authHeader']
        );
        self::assertSame(200, $response->getStatusCode());
        $etag = $response->getHeader('ETag');
        self::assertNotEmpty($etag);
        $r_body = $response->getBody()->getContents();
        self::assertJson($r_body);
        $r_data = json_decode($r_body, true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('asociaciones', $r_data);
        self::assertIsArray($r_data['asociaciones']);

        return $etag;
    }

    /**
     * Test GET /asociaciones 304 NOT MODIFIED
     *
     * @param array<string> $etag returned by testCGetAsociaciones200Ok
     */
    #[TestsAttr\Depends('testCGetAsociacion200Ok')]
    public function testCGetAsociacion304NotModified(array $etag): void
    {
        $headers = array_merge(
            self::$writer['authHeader'],
            [ 'If-None-Match' => $etag ]
        );
        $response = $this->runApp(
            'GET',
            self::RUTA_API,
            null,
            $headers
        );
        self::assertSame(StatusCode::STATUS_NOT_MODIFIED, $response->getStatusCode());
    }

    /**
     * Test GET /asociaciones/{asociacionId} 200 OK
     *
     * @param array<string,string|int> $asociacion data returned by testPostAsociaciones201Created()
     *
     * @return array<string> ETag header
     * @throws \JsonException
     */
    #[TestsAttr\Depends('testPostAsociacion201Created')]
    public function testGetAsociacion200Ok(array $asociacion): array
    {
        $response = $this->runApp(
            'GET',
            self::RUTA_API . '/' . $asociacion['id'],
            null,
            self::$writer['authHeader']
        );
        self::assertSame(200, $response->getStatusCode());
        self::assertNotEmpty($response->getHeader('ETag'));
        $r_body = $response->getBody()->getContents();
        self::assertJson($r_body);
        $product_aux = json_decode($r_body, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame($asociacion, $product_aux['asociacion']);

        return $response->getHeader('ETag');
    }

    /**
     * Test GET /asociaciones/{asociacionId} 304 NOT MODIFIED
     *
     * @param array<string,string|int> $asociacion data returned by testPostAsociacion201Created()
     * @param array<string> $etag returned by testGetAsociacion200Ok
     *
     * @return string Entity Tag
     */
    #[TestsAttr\Depends('testPostAsociacion201Created')]
    #[TestsAttr\Depends('testGetAsociacion200Ok')]
    public function testGetAsociacion304NotModified(array $asociacion, array $etag): string
    {
        $headers = array_merge(
            self::$writer['authHeader'],
            [ 'If-None-Match' => $etag ]
        );
        $response = $this->runApp(
            'GET',
            self::RUTA_API . '/' . $asociacion['id'],
            null,
            $headers
        );
        self::assertSame(StatusCode::STATUS_NOT_MODIFIED, $response->getStatusCode());

        return $etag[0];
    }

    /**
     * Test GET /asociaciones/asociacionname/{asociacionname} 204 NO CONTENT
     *
     * @param array<string,string|int> $asociacion data returned by testPostAsociacion201()
     */
    #[TestsAttr\Depends('testPostAsociacion201Created')]
    public function testGetAsociacionname204NoContent(array $asociacion): void
    {
        $response = $this->runApp(
            'GET',
            self::RUTA_API . '/asociacionname/' . $asociacion['name']
        );
        self::assertSame(204, $response->getStatusCode());
        self::assertEmpty($response->getBody()->getContents());
    }


    /**
     * Test PUT /asociaciones/{asociacionId}   209 UPDATED
     *
     * @param array<string,string|int> $asociacion data returned by testPostAsociacion201Created()
     * @param string $etag returned by testGetAsociacion304NotModified
     *
     * @return array<string,string|int> modified product data
     * @throws \JsonException
     */
    #[TestsAttr\Depends('testPostAsociacion201Created')]
    #[TestsAttr\Depends('testGetAsociacion304NotModified')]
    #[TestsAttr\Depends('testPostAsociacion400BadRequest')]
    #[TestsAttr\Depends('testCGetAsociacion304NotModified')]
    #[TestsAttr\Depends('testGetAsociacionname204NoContent')]
    public function testPutAsociacion209Updated(array $asociacion, string $etag): array
    {
        $p_data = [
            'name'  => self::$faker->words(3, true),
            'websiteUrl'  => self::$faker->url(),
            'birthDate' => self::$faker->date(),
            'deathDate' => self::$faker->date(),
            'imageUrl'  => self::$faker->url(), // imageUrl(),
            'wikiUrl'   => self::$faker->url()
        ];

        $response = $this->runApp(
            'PUT',
            self::RUTA_API . '/' . $asociacion['id'],
            $p_data,
            array_merge(
                self::$writer['authHeader'],
                [ 'If-Match' => $etag ]
            )
        );
        self::assertSame(209, $response->getStatusCode());
        $r_body = $response->getBody()->getContents();
        self::assertJson($r_body);
        $product_aux = json_decode($r_body, true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('asociacion', $product_aux);
        self::assertSame($asociacion['id'], $product_aux['asociacion']['id']);
        self::assertSame($p_data['name'], $product_aux['asociacion']['name']);
        self::assertSame($p_data['birthDate'], $product_aux['asociacion']['birthDate']);
        self::assertSame($p_data['deathDate'], $product_aux['asociacion']['deathDate']);
        self::assertSame($p_data['imageUrl'], $product_aux['asociacion']['imageUrl']);
        self::assertSame($p_data['wikiUrl'], $product_aux['asociacion']['wikiUrl']);

        return $product_aux['asociacion'];
    }

    /**
     * Test PUT /asociaciones/{asociacionId} 400 BAD REQUEST
     *
     * @param array<string,string|int> $asociacion data returned by testPutAsociacion209Updated()
     */
    #[TestsAttr\Depends('testPutAsociacion209Updated')]
    public function testPutAsociacion400BadRequest(array $asociacion): void
    {
        $p_data = ['name' => self::$faker->words(3, true), 'websiteUrl' => self::$faker->url()];

        $this->runApp(
            'POST',
            self::RUTA_API,
            $p_data,
            self::$writer['authHeader']
        );
        $r1 = $this->runApp( // Obtains etag header
            'HEAD',
            self::RUTA_API . '/' . $asociacion['id'],
            [],
            self::$writer['authHeader']
        );

        $response = $this->runApp(
            'PUT',
            self::RUTA_API . '/' . $asociacion['id'],
            $p_data,
            array_merge(
                self::$writer['authHeader'],
                [ 'If-Match' => $r1->getHeader('ETag') ]
            )
        );
        $this->internalTestError($response, StatusCode::STATUS_BAD_REQUEST);
    }

    /**
     * Test PUT /asociaciones/{asociacionId} 428 PRECONDITION REQUIRED
     *
     * @param array<string,string|int> $asociacion data returned by testPutAsociacion209Updated()
     */
    #[TestsAttr\Depends('testPutAsociacion209Updated')]
    public function testPutAsociacion428PreconditionRequired(array $asociacion): void
    {
        $response = $this->runApp(
            'PUT',
            self::RUTA_API . '/' . $asociacion['id'],
            [],
            self::$writer['authHeader']
        );
        $this->internalTestError($response, StatusCode::STATUS_PRECONDITION_REQUIRED);
    }

    /**
     * Test OPTIONS /asociaciones[/{asociacionId}] NO CONTENT
     */
    public function testOptionsAsociacion204NoContent(): void
    {
        $response = $this->runApp(
            'OPTIONS',
            self::RUTA_API
        );
        self::assertSame(204, $response->getStatusCode());
        self::assertNotEmpty($response->getHeader('Allow'));
        self::assertEmpty($response->getBody()->getContents());

        $response = $this->runApp(
            'OPTIONS',
            self::RUTA_API . '/' . self::$faker->randomDigitNotNull()
        );
        self::assertSame(204, $response->getStatusCode());
        self::assertNotEmpty($response->getHeader('Allow'));
        self::assertEmpty($response->getBody()->getContents());
    }

    /**
     * Test DELETE /asociaiones/{asociacioId} 204 NO CONTENT
     *
     * @param array<string,string|int> $asociacion data returned by testPostAsociacion201Created()
     *
     * @return int asociacionId
     */
    #[TestsAttr\Depends('testPostAsociacion201Created')]
    #[TestsAttr\Depends('testPostAsociacion400BadRequest')]
    #[TestsAttr\Depends('testPostAsociacion422UnprocessableEntity')]
    #[TestsAttr\Depends('testPutAsociacion400BadRequest')]
    #[TestsAttr\Depends('testPutAsociacion428PreconditionRequired')]
    #[TestsAttr\Depends('testGetAsociacionname204NoContent')]
    public function testDeleteAsociacion204NoContent(array $asociacion): int
    {
        $response = $this->runApp(
            'DELETE',
            self::RUTA_API . '/' . $asociacion['id'],
            null,
            self::$writer['authHeader']
        );
        self::assertSame(204, $response->getStatusCode());
        self::assertEmpty($response->getBody()->getContents());

        return (int) $asociacion['id'];
    }

    /**
     * Test GET /asociaciones/asociacionname/{asociacionname} 404 NOT FOUND
     *
     * @param array<string,string|int> $asociacion data returned by testPutAsociacion209Updated()
     */
    #[TestsAttr\Depends('testPutAsociacion209Updated')]
    #[TestsAttr\Depends('testDeleteAsociacion204NoContent')]
    public function testGetAsociacionname404NotFound(array $asociacion): void
    {
        $response = $this->runApp(
            'GET',
            self::RUTA_API . '/asociacionname/' . $asociacion['name']
        );
        $this->internalTestError($response, StatusCode::STATUS_NOT_FOUND);
    }

    /**
     * Test GET    /asociaciones/{asociacionId} 404 NOT FOUND
     * Test PUT    /asociaciones/{asociacionId} 404 NOT FOUND
     * Test DELETE /asociaciones/{asociacionID} 404 NOT FOUND
     *
     * @param int $asociacionId product id. returned by testDeleteProduct204NoContent()
     * @param string $method
     *
     * @return void
     */
    #[TestsAttr\DataProvider('routeProvider404')]
    #[TestsAttr\Depends('testDeleteAsociacion204NoContent')]
    public function testAsociacionStatus404NotFound(string $method, int $asociacionId): void
    {
        $response = $this->runApp(
            $method,
            self::RUTA_API . '/' . $asociacionId,
            null,
            self::$writer['authHeader']
        );
        $this->internalTestError($response, StatusCode::STATUS_NOT_FOUND);
    }

    /**
     * Test GET    /asociaciones 401 UNAUTHORIZED
     * Test POST   /asociaciones 401 UNAUTHORIZED
     * Test PUT    /asociaciones/{asociacionId} 401 UNAUTHORIZED
     * Test DELETE /asociaciones/{asociacionId} 401 UNAUTHORIZED
     *
     * @param string $method
     * @param string $uri
     *
     * @return void
     */
    #[TestsAttr\DataProvider('routeProvider401')]
    public function testAsociacionStatus401Unauthorized(string $method, string $uri): void
    {
        $response = $this->runApp(
            $method,
            $uri
        );
        $this->internalTestError($response, StatusCode::STATUS_UNAUTHORIZED);
    }

    /**
     * Test POST   /asociaciones 403 FORBIDDEN
     * Test PUT    /asociaciones/{asociacionId} 403 FORBIDDEN => 404 NOT FOUND
     * Test DELETE /asociaciones/{asociacionId} 403 FORBIDDEN => 404 NOT FOUND
     *
     * @param string $method
     * @param string $uri
     * @param int $statusCode
     *
     * @return void
     */
    #[TestsAttr\DataProvider('routeProvider403')]
    public function testAsociacionesStatus403Forbidden(string $method, string $uri, int $statusCode): void
    {
        self::$reader['authHeader'] = $this->getTokenHeaders(self::$reader['username'], self::$reader['password']);
        $response = $this->runApp(
            $method,
            $uri,
            null,
            self::$reader['authHeader']
        );
        $this->internalTestError($response, $statusCode);
    }

    // --------------
    // DATA PROVIDERS
    // --------------

    /**
     * Route provider (expected status: 401 UNAUTHORIZED)
     *
     * @return array<string,mixed> [ method, url ]
     */
    #[ArrayShape([
        'postAction401' => "string[]",
        'putAction401' => "string[]",
        'deleteAction401' => "string[]",
    ])]
    public static function routeProvider401(): array
    {
        return [
            // 'cgetAction401'   => [ 'GET',    self::RUTA_API ],
            // 'getAction401'    => [ 'GET',    self::RUTA_API . '/1' ],
            'postAction401'   => [ 'POST',   self::RUTA_API ],
            'putAction401'    => [ 'PUT',    self::RUTA_API . '/1' ],
            'deleteAction401' => [ 'DELETE', self::RUTA_API . '/1' ],
        ];
    }

    /**
     * Route provider (expected status: 404 NOT FOUND)
     *
     * @return array<string,mixed> [ method ]
     */
    #[ArrayShape([
        'getAction404' => "string[]",
        'putAction404' => "string[]",
        'deleteAction404' => "string[]",
    ])]
    public static function routeProvider404(): array
    {
        return [
            'getAction404'    => [ 'GET' ],
            'putAction404'    => [ 'PUT' ],
            'deleteAction404' => [ 'DELETE' ],
        ];
    }

    /**
     * Route provider (expected status: 403 FORBIDDEN (security) => 404 NOT FOUND)
     *
     * @return array<string,mixed> [ method, url, statusCode ]
     */
    #[ArrayShape([
        'postAction403' => "array",
        'putAction403' => "array",
        'deleteAction403' => "array",
    ])]
    public static function routeProvider403(): array
    {
        return [
            'postAction403'   => [ 'POST',   self::RUTA_API, StatusCode::STATUS_FORBIDDEN ],
            'putAction403'    => [ 'PUT',    self::RUTA_API . '/1', StatusCode::STATUS_NOT_FOUND ],
            'deleteAction403' => [ 'DELETE', self::RUTA_API . '/1', StatusCode::STATUS_NOT_FOUND  ],
        ];
    }
}
