<?php
use Cake\Routing\RouteBuilder;

return static function (RouteBuilder $routes) {
    $routes->setRouteClass('DashedRoute');
    
    // Health check endpoint
    $routes->connect('/health', ['controller' => 'Health', 'action' => 'index']);
    
    // API Routes with proper prefix
    $routes->prefix('Api', function (RouteBuilder $builder) {
        $builder->setExtensions(['json']);
        
        // Invoices endpoints
        $builder->connect('/invoices', ['controller' => 'Invoices', 'action' => 'index']);
        $builder->connect('/invoices/stats', ['controller' => 'Invoices', 'action' => 'stats']);
        $builder->connect('/invoices/{id}', ['controller' => 'Invoices', 'action' => 'view'])
            ->setPass(['id'])
            ->setPatterns(['id' => '\d+']);
    });
    
    // Default home page
    $routes->connect('/', ['controller' => 'Pages', 'action' => 'display', 'home']);
    
    $routes->fallbacks('DashedRoute');
};
