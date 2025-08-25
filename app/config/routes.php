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
        
        // Rutas para facturas
        $builder->connect('/invoices', ['controller' => 'Invoices', 'action' => 'index']);
        $builder->connect('/invoices/add', ['controller' => 'Invoices', 'action' => 'add']);
        $builder->connect('/invoices/view/*', ['controller' => 'Invoices', 'action' => 'view']);
        $builder->connect('/invoices/edit/*', ['controller' => 'Invoices', 'action' => 'edit']);
        $builder->connect('/invoices/delete/*', ['controller' => 'Invoices', 'action' => 'delete']);

        $builder->fallbacks();
    });

    // API Routes para el frontend de Netlify
    $routes->prefix('/api', function (RouteBuilder $routes) {
        $routes->setExtensions(['json']);
        
        // Rutas de facturas API
        $routes->get('/invoices', ['controller' => 'Invoices', 'action' => 'api']);
        $routes->get('/invoices/{id}', ['controller' => 'Invoices', 'action' => 'view'])
            ->setPass(['id']);
        $routes->post('/invoices', ['controller' => 'Invoices', 'action' => 'add']);
        $routes->put('/invoices/{id}', ['controller' => 'Invoices', 'action' => 'edit'])
            ->setPass(['id']);
        $routes->delete('/invoices/{id}', ['controller' => 'Invoices', 'action' => 'delete'])
            ->setPass(['id']);
    });
};