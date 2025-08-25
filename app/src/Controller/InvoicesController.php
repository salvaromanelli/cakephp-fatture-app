<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * Invoices Controller
 *
 * @property \App\Model\Table\InvoicesTable $Invoices
 */
class InvoicesController extends AppController
{
    /**
     * Index method - Lista todas las facturas
     */
    public function index()
    {
        // Si no existe la tabla todavía, usar datos dummy
        try {
            $query = $this->Invoices->find('all')
                ->orderBy(['created' => 'DESC']); // Cambiar order() por orderBy()
            $invoices = $this->paginate($query);
        } catch (\Exception $e) {
            // Si hay error de BD, usar array vacío
            $invoices = [];
        }

        $this->set(compact('invoices'));
        
        // Para respuestas JSON
        if ($this->request->is('json') || $this->request->getQuery('_ext') === 'json') {
            $this->viewBuilder()->setClassName('Json');
            $this->viewBuilder()->setOption('serialize', ['invoices']);
        }
    }

    /**
     * View method - Ver una factura específica
     */
    public function view($id = null)
    {
        try {
            $invoice = $this->Invoices->get($id);
        } catch (\Exception $e) {
            throw new \Cake\Datasource\Exception\RecordNotFoundException('Factura no encontrada');
        }

        $this->set(compact('invoice'));
        
        if ($this->request->is('json') || $this->request->getQuery('_ext') === 'json') {
            $this->viewBuilder()->setClassName('Json');
            $this->viewBuilder()->setOption('serialize', ['invoice']);
        }
    }

    /**
     * Add method - Crear nueva factura
     */
    public function add()
    {
        $invoice = $this->Invoices->newEmptyEntity();
        
        if ($this->request->is('post')) {
            $data = $this->request->getData();
            
            // Calcular IVA y total automáticamente
            if (isset($data['subtotal'])) {
                $subtotal = (float)$data['subtotal'];
                $data['iva'] = $subtotal * 0.21; // 21% IVA
                $data['total'] = $subtotal + $data['iva'];
            }
            
            $invoice = $this->Invoices->patchEntity($invoice, $data);
            
            if ($this->Invoices->save($invoice)) {
                $message = 'Factura guardada correctamente.';
                $this->Flash->success($message);

                // Respuesta para JSON
                if ($this->request->is('json') || $this->request->getQuery('_ext') === 'json') {
                    $this->viewBuilder()->setClassName('Json');
                    $this->set([
                        'success' => true,
                        'message' => $message,
                        'invoice' => $invoice
                    ]);
                    $this->viewBuilder()->setOption('serialize', ['success', 'message', 'invoice']);
                    return;
                }

                return $this->redirect(['action' => 'index']);
            }
            
            $message = 'No se pudo guardar la factura. Inténtalo de nuevo.';
            $this->Flash->error($message);
        }
        
        $this->set(compact('invoice'));
    }

    /**
     * API method - Endpoint para JSON
     */
    public function api()
    {
        $this->viewBuilder()->setClassName('Json');
        
        try {
            $invoices = $this->Invoices->find('all')
                ->orderBy(['created' => 'DESC']) // Cambiar order() por orderBy()
                ->toArray();
        } catch (\Exception $e) {
            $invoices = [];
        }
        
        $this->set(compact('invoices'));
        $this->viewBuilder()->setOption('serialize', ['invoices']);
    }
}