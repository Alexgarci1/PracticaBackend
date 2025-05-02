<?php

/**
 * config/routesAsociaciones.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

use Slim\App;
use TDW\ACiencia\Controller\Asociacion\{ AsociacionCommandController, AsociacionQueryController, AsociacionRelationsController };
use TDW\ACiencia\Middleware\JwtMiddleware;

/**
 * ############################################################
 * routes /api/v1/asociaciones
 * ############################################################
 * @param App $app
 */
return function (App $app) {

    $REGEX_ASOCIACION_ID = '/{asociacionId:[0-9]+}';
    $REGEX_ELEMENT_ID = '/{elementId:[0-9]+}';
    $REGEX_ELEMENT_NAME = '{name:[ a-zA-Z0-9()áéíóúÁÉÍÓÚñÑ %$\.+-]+}';
    $UNLIMITED_OPTIONAL_PARAMETERS = '/[{params:.*}]';

    // CGET|HEAD: Returns all asociaciones
    $app->map(
        [ 'GET', 'HEAD' ],
        $_ENV['RUTA_API'] . AsociacionQueryController::PATH_ASOCIACIONES,
        AsociacionQueryController::class . ':cget'
    )->setName('readAsociaciones');

    // GET|HEAD: Returns a asociacion based on a single ID
    $app->map(
        [ 'GET', 'HEAD' ],
        $_ENV['RUTA_API'] . AsociacionQueryController::PATH_ASOCIACIONES . $REGEX_ASOCIACION_ID,
        AsociacionQueryController::class . ':get'
    )->setName('readAsociacion');

    // GET: Returns status code 204 if asociacion name exists
    $app->get(
        $_ENV['RUTA_API'] . AsociacionQueryController::PATH_ASOCIACIONES . '/asociacionname/' . $REGEX_ELEMENT_NAME,
        AsociacionQueryController::class . ':getElementByName'
    )->setName('existsAsociacion');

    // DELETE: Deletes an asociacion
    $app->delete(
        $_ENV['RUTA_API'] . AsociacionCommandController::PATH_ASOCIACIONES . $REGEX_ASOCIACION_ID,
        AsociacionCommandController::class . ':delete'
    )->setName('deleteAsociacion')
        ->add(JwtMiddleware::class);

    // OPTIONS: Provides the list of HTTP supported methods
    $app->options(
        $_ENV['RUTA_API'] . AsociacionQueryController::PATH_ASOCIACIONES . '[' . $REGEX_ASOCIACION_ID . ']',
        AsociacionQueryController::class . ':options'
    )->setName('optionsAsociacion');

    // POST: Creates a new asociacion
    $app->post(
        $_ENV['RUTA_API'] . AsociacionCommandController::PATH_ASOCIACIONES,
        AsociacionCommandController::class . ':post'
    )->setName('createAsociacion')
        ->add(JwtMiddleware::class);

    // PUT: Updates an asociacion
    $app->put(
        $_ENV['RUTA_API'] . AsociacionCommandController::PATH_ASOCIACIONES . $REGEX_ASOCIACION_ID,
        AsociacionCommandController::class . ':put'
    )->setName('updateAsociacion')
        ->add(JwtMiddleware::class);

    // RELATIONSHIPS
    // OPTIONS /asociaciones/{asociacionId}[/{params:.*}]
    $app->options(
        $_ENV['RUTA_API'] . AsociacionQueryController::PATH_ASOCIACIONES . $REGEX_ASOCIACION_ID . $UNLIMITED_OPTIONAL_PARAMETERS,
        AsociacionRelationsController::class . ':optionsElements'
    )->setName('optionsAsociacionesRelationships');

    // GET /asociaciones/{asociacionId}/entities
    $app->get(
        $_ENV['RUTA_API'] . AsociacionQueryController::PATH_ASOCIACIONES . $REGEX_ASOCIACION_ID . '/entities',
        AsociacionRelationsController::class . ':getEntities'
    )->setName('readAsociacionEntities');

    // PUT /asociaciones/{asociacionId}/entities/add/{elementId}
    $app->put(
        $_ENV['RUTA_API'] . AsociacionCommandController::PATH_ASOCIACIONES . $REGEX_ASOCIACION_ID . '/entities/add' . $REGEX_ELEMENT_ID,
        AsociacionRelationsController::class . ':operationEntity'
    )->setName('tdw_asociaciones_add_entity')
        ->add(JwtMiddleware::class);

    // PUT /asociaciones/{asociacionId}/entities/rem/{elementId}
    $app->put(
        $_ENV['RUTA_API'] . AsociacionCommandController::PATH_ASOCIACIONES . $REGEX_ASOCIACION_ID . '/entities/rem' . $REGEX_ELEMENT_ID,
        AsociacionRelationsController::class . ':operationEntity'
    )->setName('tdw_asociaciones_rem_entity')
        ->add(JwtMiddleware::class);
};