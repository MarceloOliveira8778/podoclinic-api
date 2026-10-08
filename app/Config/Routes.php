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
            'customers/lookup',
            'Api\CustomerController::lookup',
            ['filter' => 'permission:customer.lookup']
        );

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

        /*
        * Financial Category
        */

        $routes->get(
            'financial-categories',
            'Api\FinancialCategoryController::index',
            ['filter' => 'permission:financial_category.view']
        );

        $routes->get(
            'financial-movements/summary',
            'Api\FinancialMovementController::summary',
            ['filter' => 'permission:financial_movement.view']
        );

        $routes->get(
            'financial-categories/(:num)',
            'Api\FinancialCategoryController::show/$1',
            ['filter' => 'permission:financial_category.manage']
        );

        $routes->post(
            'financial-categories',
            'Api\FinancialCategoryController::create',
            ['filter' => 'permission:financial_category.manage']
        );

        $routes->put(
            'financial-categories/(:num)',
            'Api\FinancialCategoryController::update/$1',
            ['filter' => 'permission:financial_category.manage']
        );

        $routes->patch(
            'financial-categories/(:num)/activate',
            'Api\FinancialCategoryController::activate/$1',
            ['filter' => 'permission:financial_category.manage']
        );

        $routes->patch(
            'financial-categories/(:num)/deactivate',
            'Api\FinancialCategoryController::deactivate/$1',
            ['filter' => 'permission:financial_category.manage']
        );

        /*
        * Financial Movement
        */

        $routes->get(
            'financial-movements',
            'Api\FinancialMovementController::index',
            ['filter' => 'permission:financial_movement.view']
        );

        $routes->get(
            'financial-movements/(:num)',
            'Api\FinancialMovementController::show/$1',
            ['filter' => 'permission:financial_movement.view']
        );

        $routes->post(
            'financial-movements',
            'Api\FinancialMovementController::create',
            ['filter' => 'permission:financial_movement.create']
        );

        /*
        * Stock Movements
        */

        $routes->post(
            'stock-movements/entries',
            'Api\StockMovementController::entry',
            ['filter' => 'permission:stock.entry']
        );

        $routes->post(
            'stock-movements/exits',
            'Api\StockMovementController::exit',
            ['filter' => 'permission:stock.exit']
        );

        $routes->get(
            'stock',
            'Api\StockController::index',
            ['filter' => 'permission:stock.view']
        );

        $routes->get(
            'stock-movements',
            'Api\StockMovementController::index',
            ['filter' => 'permission:stock.view']
        );

        $routes->post(
            'purchases',
            'Api\PurchaseController::create',
            ['filter' => 'permission:purchase.create']
        );

        $routes->get(
            'purchases',
            'Api\PurchaseController::index',
            ['filter' => 'permission:purchase.view']
        );

        /*
        * Sales
        */
        $routes->post(
            'sales',
            'Api\SaleController::create',
            ['filter' => 'permission:sale.create']
        );

        $routes->get(
            'sales',
            'Api\SaleController::index',
            ['filter' => 'permission:sale.view']
        );

        /*
        * Customer reminders
        */

        $routes->get(
            'customer-reminders',
            'Api\CustomerReminderController::index',
            ['filter' => 'permission:customer_reminder.view']
        );

        $routes->patch(
            'customer-reminders/(:num)/contacted',
            'Api\CustomerReminderController::markAsContacted/$1',
            ['filter' => 'permission:customer_reminder.update']
        );

        $routes->patch(
            'customer-reminders/(:num)/cancelled',
            'Api\CustomerReminderController::cancel/$1',
            ['filter' => 'permission:customer_reminder.update']
        );

        $routes->get(
            'customer-reminders/(:num)/whatsapp-link',
            'Api\CustomerReminderController::whatsAppLink/$1',
            ['filter' => 'permission:customer_reminder.view']
        );
    });
});