<?php
use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;

return static function (RouteBuilder $routes) {
    $routes->setRouteClass(DashedRoute::class);

    $routes->scope('/', function (RouteBuilder $builder) {
        // Home page
        $builder->connect('/', ['controller' => 'Pages', 'action' => 'display', 'home']);
        
        // Rutas tradicionales de CakePHP
        $builder->connect('/pages/*', 'Pages::display');
        
        // Rutas para facturas HTML
        $builder->connect('/invoices', ['controller' => 'Invoices', 'action' => 'index']);
        $builder->connect('/invoices/add', ['controller' => 'Invoices', 'action' => 'add']);
        $builder->connect('/invoices/view/*', ['controller' => 'Invoices', 'action' => 'view']);
        
        $builder->fallbacks();
    });

    // API Routes - usando el prefijo 'Api' que coincide con el namespace App\Controller\Api
    $routes->prefix('Api', function (RouteBuilder $routes) {
        $routes->setExtensions(['json']);
        
        // Ruta: /api/invoices.json -> App\Controller\Api\InvoicesController::index()
        $routes->get('/invoices', ['controller' => 'Invoices', 'action' => 'index']);
        
        // Ruta: /api/invoices/1.json -> App\Controller\Api\InvoicesController::view(1)
        $routes->get('/invoices/{id}', ['controller' => 'Invoices', 'action' => 'view'])
            ->setPass(['id'])
            ->setPatterns(['id' => '\d+']);
            
        // Ruta: POST /api/invoices.json -> App\Controller\Api\InvoicesController::add()
        $routes->post('/invoices', ['controller' => 'Invoices', 'action' => 'add']);
    });
};