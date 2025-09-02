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
        
        // CORS headers
        $this->response = $this->response->withHeader('Access-Control-Allow-Origin', '*');
        $this->response = $this->response->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $this->response = $this->response->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        
        // Disable CSRF for API
        if (isset($this->Csrf)) {
            $this->getEventManager()->off($this->Csrf);
        }
    }
    
    public function index()
    {
        $invoices = [
            [
                'id' => 1,
                'numero' => 'FACT-2024-001',
                'cliente' => 'Empresa Demo SL',
                'total' => '1250.00',
                'fecha' => '2024-08-26',
                'descripcion' => 'Desarrollo aplicación CakePHP + React',
                'estado' => 'pagada'
            ],
            [
                'id' => 2,
                'numero' => 'FACT-2024-002', 
                'cliente' => 'StartupTech Inc',
                'total' => '890.50',
                'fecha' => '2024-08-25',
                'descripcion' => 'Consultoría AWS + deployment',
                'estado' => 'pagada'
            ],
            [
                'id' => 3,
                'numero' => 'FACT-2024-003',
                'cliente' => 'Digital Solutions',
                'total' => '2100.00',
                'fecha' => '2024-08-24',
                'descripcion' => 'API REST + MySQL RDS',
                'estado' => 'pendiente'
            ]
        ];

        $this->set([
            'success' => true,
            'data' => $invoices,
            'meta' => [
                'total_facturas' => count($invoices),
                'total_importe' => '4240.50',
                'moneda' => 'EUR'
            ]
        ]);
        $this->viewBuilder()->setOption('serialize', ['success', 'data', 'meta']);
    }
    
    public function view($id = null)
    {
        $invoice = [
            'id' => (int)$id,
            'numero' => 'FACT-2024-' . str_pad($id, 3, '0', STR_PAD_LEFT),
            'cliente' => 'Cliente Demo ' . $id,
            'total' => number_format(rand(500, 3000), 2),
            'fecha' => date('Y-m-d'),
            'estado' => 'pagada'
        ];

        $this->set([
            'success' => true,
            'data' => $invoice
        ]);
        $this->viewBuilder()->setOption('serialize', ['success', 'data']);
    }
    
    public function stats()
    {
        $this->set([
            'success' => true,
            'data' => [
                'total_facturas' => 248,
                'total_importe' => '54290.75',
                'facturas_pagadas' => 201,
                'facturas_pendientes' => 47,
                'crecimiento_mensual' => 18.5
            ]
        ]);
        $this->viewBuilder()->setOption('serialize', ['success', 'data']);
    }
}
