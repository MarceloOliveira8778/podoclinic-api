<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

$routes->group('api', static function ($routes) {

    /*
     * Rotas públicas
     */
    $routes->post(
        'auth/login',
        'Api\AuthenticationController::login'
    );

    /*
     * Rotas autenticadas
     */
    $routes->group('', ['filter' => 'auth'], static function ($routes) {

        $routes->post(
            'auth/logout',
            'Api\AuthenticationController::logout'
        );

        // Customer
        $routes->get(
            'customers',
            'Api\CustomerController::index',
            ['filter' => 'permission:customer.view']
        );

        $routes->get(
            'customers/(:num)',
            'Api\CustomerController::show/$1',
            ['filter' => 'permission:customer.view']
        );

        $routes->post(
            'customers',
            'Api\CustomerController::create',
            ['filter' => 'permission:customer.create']
        );

        $routes->put(
            'customers/(:num)',
            'Api\CustomerController::update/$1',
            ['filter' => 'permission:customer.update']
        );

        $routes->patch(
            'customers/(:num)/activate',
            'Api\CustomerController::activate/$1',
            ['filter' => 'permission:customer.update']
        );

        $routes->patch(
            'customers/(:num)/deactivate',
            'Api\CustomerController::deactivate/$1',
            ['filter' => 'permission:customer.update']
        );

        // Product
        $routes->get(
            'products',
            'Api\ProductController::index',
            ['filter' => 'permission:product.view']
        );

        $routes->get(
            'products/(:num)',
            'Api\ProductController::show/$1',
            ['filter' => 'permission:product.view']
        );

        $routes->post(
            'products',
            'Api\ProductController::create',
            ['filter' => 'permission:product.create']
        );

        $routes->put(
            'products/(:num)',
            'Api\ProductController::update/$1',
            ['filter' => 'permission:product.update']
        );

        $routes->patch(
            'products/(:num)/activate',
            'Api\ProductController::activate/$1',
            ['filter' => 'permission:product.update']
        );

        $routes->patch(
            'products/(:num)/deactivate',
            'Api\ProductController::deactivate/$1',
            ['filter' => 'permission:product.update']
        );


        /*
        * User
        */

        $routes->get(
            'users',
            'Api\UserController::index',
            ['filter' => 'permission:user.view']
        );

        $routes->get(
            'users/(:num)',
            'Api\UserController::show/$1',
            ['filter' => 'permission:user.view']
        );

        $routes->post(
            'users',
            'Api\UserController::create',
            ['filter' => 'permission:user.create']
        );

        $routes->put(
            'users/(:num)',
            'Api\UserController::update/$1',
            ['filter' => 'permission:user.update']
        );

        $routes->patch(
            'users/(:num)/activate',
            'Api\UserController::activate/$1',
            ['filter' => 'permission:user.activate']
        );

        $routes->patch(
            'users/(:num)/deactivate',
            'Api\UserController::deactivate/$1',
            ['filter' => 'permission:user.deactivate']
        );
    });
});