<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

$routes->group('api', static function ($routes) {
    $routes->get(
        'customers',
        'Api\CustomerController::index'
    );

    $routes->get(
        'customers/(:num)',
        'Api\CustomerController::show/$1'
    );

    $routes->post(
        'customers',
        'Api\CustomerController::create'
    );

    $routes->put(
        'customers/(:num)',
        'Api\CustomerController::update/$1'
    );

    $routes->patch(
        'customers/(:num)/activate',
        'Api\CustomerController::activate/$1'
    );

    $routes->patch(
        'customers/(:num)/deactivate',
        'Api\CustomerController::deactivate/$1'
    );
});