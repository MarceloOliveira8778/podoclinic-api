<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

$routes->group('api', static function ($routes) {

    // Customer
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

    // Product
    $routes->get(
        'products',
        'Api\ProductController::index'
    );

    $routes->get(
        'products/(:num)',
        'Api\ProductController::show/$1'
    );

    $routes->post(
        'products',
        'Api\ProductController::create'
    );

    $routes->put(
        'products/(:num)',
        'Api\ProductController::update/$1'
    );

    $routes->patch(
        'products/(:num)/activate',
        'Api\ProductController::activate/$1'
    );

    $routes->patch(
        'products/(:num)/deactivate',
        'Api\ProductController::deactivate/$1'
    );
});