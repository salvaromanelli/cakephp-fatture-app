<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Controller\AppController;

class InvoicesController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->viewBuilder()->setClassName("Json");
    }

    public function index()
    {
        try {
            $invoices = $this->Invoices->find("all")
                ->orderBy(["created" => "DESC"])
                ->toArray();
            
            $this->set([
                "success" => true,
                "data" => $invoices,
                "count" => count($invoices)
            ]);
        } catch (\Exception $e) {
            $this->set([
                "success" => false,
                "message" => $e->getMessage(),
                "data" => [],
                "count" => 0
            ]);
        }
        
        $this->viewBuilder()->setOption("serialize", ["success", "data", "count", "message"]);
    }

    public function view($id = null)
    {
        try {
            $invoice = $this->Invoices->get($id);
            
            $this->set([
                "success" => true,
                "message" => "Factura encontrada",
                "data" => $invoice
            ]);
        } catch (\Exception $e) {
            $this->response = $this->response->withStatus(404);
            $this->set([
                "success" => false,
                "message" => "Factura no encontrada",
                "data" => null
            ]);
        }
        
        $this->viewBuilder()->setOption("serialize", ["success", "message", "data"]);
    }

    public function add()
    {
        $this->request->allowMethod(["post"]);
        
        $invoice = $this->Invoices->newEmptyEntity();
        
        try {
            $data = $this->request->getData();
            
            if (isset($data["subtotal"])) {
                $subtotal = (float)$data["subtotal"];
                $data["iva"] = $subtotal * 0.21;
                $data["total"] = $subtotal + $data["iva"];
                $data["estado"] = $data["estado"] ?? "pendiente";
            }
            
            $invoice = $this->Invoices->patchEntity($invoice, $data);
            
            if ($this->Invoices->save($invoice)) {
                $this->response = $this->response->withStatus(201);
                $this->set([
                    "success" => true,
                    "message" => "Factura creada correctamente",
                    "data" => $invoice
                ]);
            } else {
                $this->response = $this->response->withStatus(400);
                $this->set([
                    "success" => false,
                    "message" => "No se pudo crear la factura",
                    "data" => null,
                    "errors" => $invoice->getErrors()
                ]);
            }
        } catch (\Exception $e) {
            $this->response = $this->response->withStatus(500);
            $this->set([
                "success" => false,
                "message" => "Error interno: " . $e->getMessage(),
                "data" => null
            ]);
        }
        
        $this->viewBuilder()->setOption("serialize", ["success", "message", "data", "errors"]);
    }

}
