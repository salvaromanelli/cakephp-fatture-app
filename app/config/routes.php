<?php
use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;

return static function (RouteBuilder $routes): void {
    $routes->setRouteClass(DashedRoute::class);

    $routes->scope('/', function (RouteBuilder $builder): void {
        // Health check
        $builder->connect('/health', ['controller' => 'Health', 'action' => 'index']);
        
        // API Routes con prefijo
        $builder->prefix('Api', function (RouteBuilder $builder): void {
            $builder->setExtensions(['json']);
            
            // Invoices endpoints
            $builder->connect('/invoices', ['controller' => 'Invoices', 'action' => 'index']);
            $builder->connect('/invoices/stats', ['controller' => 'Invoices', 'action' => 'stats']);
            $builder->connect('/invoices/{id}', ['controller' => 'Invoices', 'action' => 'view'])
                ->setPass(['id'])
                ->setPatterns(['id' => '\d+']);
        });

        // Home page
        $builder->connect('/', ['controller' => 'Pages', 'action' => 'display', 'home']);
        $builder->connect('/pages/*', ['controller' => 'Pages', 'action' => 'display']);

        $builder->fallbacks();
    });
};
