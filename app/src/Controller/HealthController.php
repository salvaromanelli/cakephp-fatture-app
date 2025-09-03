<?php
declare(strict_types=1);
namespace App\Controller;

class HealthController extends AppController
{
    public function index()
    {
        $this->viewBuilder()->setClassName('Json');
        
        $this->response = $this->response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Content-Type', 'application/json');

        $this->set([
            'status' => 'healthy',
            'service' => 'CakePHP Fatture API',
            'version' => '1.0.0',
            'framework' => 'CakePHP 4.x',
            'timestamp' => date('c'),
            'php_version' => phpversion(),
            'server' => 'AWS Elastic Beanstalk',
            'deployment' => 'EB CLI',
            'endpoints' => [
                'GET /health - Health check',
                'GET /api/invoices.json - Lista facturas',
                'GET /api/invoices/{id}.json - Factura específica',
                'GET /api/invoices/stats.json - Estadísticas'
            ]
        ]);
        
        $this->viewBuilder()->setOption('serialize', true);
    }
}
