<?php
declare(strict_types=1);
namespace App\Controller\Api;
use App\Controller\AppController;

class InvoicesController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->viewBuilder()->setClassName('Json');
        
        // CORS para React
        $this->response = $this->response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
            
        if ($this->request->getMethod() === 'OPTIONS') {
            $this->response = $this->response->withStatus(200);
            return $this->response;
        }
    }
    
    public function index()
    {
        $facturas = [
            [
                'id' => 1,
                'numero' => 'FACT-2024-001',
                'cliente' => 'Empresa Demo SL',
                'total' => '1250.00',
                'fecha' => '2024-08-26',
                'descripcion' => 'Desarrollo aplicación web CakePHP + React',
                'estado' => 'pagada',
                'created' => '2024-08-26 10:30:00',
                'modified' => '2024-08-26 10:30:00'
            ],
            [
                'id' => 2,
                'numero' => 'FACT-2024-002',
                'cliente' => 'StartupTech Inc',
                'total' => '890.50',
                'fecha' => '2024-08-25',
                'descripcion' => 'Consultoría AWS + Elastic Beanstalk',
                'estado' => 'pagada',
                'created' => '2024-08-25 14:15:00',
                'modified' => '2024-08-25 14:15:00'
            ],
            [
                'id' => 3,
                'numero' => 'FACT-2024-003',
                'cliente' => 'Digital Solutions Agency',
                'total' => '2100.00',
                'fecha' => '2024-08-24',
                'descripcion' => 'API REST completa + integración',
                'estado' => 'pendiente',
                'created' => '2024-08-24 09:45:00',
                'modified' => '2024-08-24 09:45:00'
            ],
            [
                'id' => 4,
                'numero' => 'FACT-2024-004',
                'cliente' => 'Innovation Labs Corp',
                'total' => '1575.25',
                'fecha' => '2024-08-23',
                'descripcion' => 'Sistema gestión facturas completo',
                'estado' => 'pagada',
                'created' => '2024-08-23 16:20:00',
                'modified' => '2024-08-23 16:20:00'
            ]
        ];

        $this->set([
            'success' => true,
            'message' => 'Facturas obtenidas correctamente',
            'data' => $facturas,
            'meta' => [
                'total_facturas' => count($facturas),
                'total_importe' => '5815.75',
                'moneda' => 'EUR',
                'facturas_pagadas' => 3,
                'facturas_pendientes' => 1,
                'backend' => 'CakePHP + AWS Elastic Beanstalk',
                'framework' => 'CakePHP 4.x',
                'timestamp' => date('c')
            ]
        ]);
        
        $this->viewBuilder()->setOption('serialize', ['success', 'message', 'data', 'meta']);
    }
    
    public function view($id = null)
    {
        if (!$id || !is_numeric($id)) {
            $this->set([
                'success' => false,
                'message' => 'ID de factura requerido'
            ]);
            $this->viewBuilder()->setOption('serialize', ['success', 'message']);
            return;
        }

        $factura = [
            'id' => (int)$id,
            'numero' => 'FACT-2024-' . str_pad($id, 3, '0', STR_PAD_LEFT),
            'cliente' => 'Cliente Demo ' . $id,
            'total' => number_format((rand(500, 3000) / 100) * 100, 2),
            'fecha' => date('Y-m-d'),
            'descripcion' => 'Factura demo #' . $id . ' - CakePHP',
            'estado' => rand(0, 1) ? 'pagada' : 'pendiente',
            'created' => date('Y-m-d H:i:s'),
            'modified' => date('Y-m-d H:i:s')
        ];

        $this->set([
            'success' => true,
            'data' => $factura
        ]);
        
        $this->viewBuilder()->setOption('serialize', ['success', 'data']);
    }
    
    public function stats()
    {
        $stats = [
            'total_facturas' => 248,
            'total_importe' => '54290.75',
            'facturas_pagadas' => 201,
            'facturas_pendientes' => 47,
            'crecimiento_mensual' => 18.5,
            'promedio_factura' => '218.91',
            'ultimo_mes' => '4520.30',
            'mes_actual' => date('Y-m'),
            'framework' => 'CakePHP 4.x'
        ];

        $this->set([
            'success' => true,
            'data' => $stats,
            'meta' => [
                'periodo' => 'Últimos 12 meses',
                'moneda' => 'EUR',
                'generated_at' => date('c')
            ]
        ]);
        
        $this->viewBuilder()->setOption('serialize', ['success', 'data', 'meta']);
    }
}
