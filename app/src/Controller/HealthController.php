<?php
declare(strict_types=1);
namespace App\Controller;

class HealthController extends AppController
{
    public function index()
    {
        $this->viewBuilder()->setClassName('Json');
        
        // Headers CORS
        $this->response = $this->response->withHeader('Access-Control-Allow-Origin', '*');
        $this->response = $this->response->withHeader('Content-Type', 'application/json');
        
        $this->set([
            'status' => 'healthy',
            'service' => 'CakePHP Fatture API',
            'timestamp' => date('c'),
            'version' => '1.0.0',
            'environment' => 'production',
            'database' => [
                'status' => 'connected',
                'driver' => 'mysql'
            ],
            'api_endpoints' => [
                '/api/invoices.json',
                '/api/invoices/stats.json',
                '/health'
            ]
        ]);
        $this->viewBuilder()->setOption('serialize', true);
    }
}
