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
        // Configurar paginación por defecto SIEMPRE
        $this->paginate = [
            'limit' => 20,
            'order' => ['created' => 'DESC']
        ];

        try {
            // Usar paginate() en lugar de find()
            $invoices = $this->paginate($this->Invoices);
        } catch (\Exception $e) {
            // Si hay error de BD, crear un query vacío y paginarlo
            $emptyQuery = $this->Invoices->find()->where(['1 = 0']); // Query que no retorna nada
            $invoices = $this->paginate($emptyQuery);
        }

        $this->set(compact('invoices'));
        
        // Para respuestas JSON
        if ($this->request->is('json') || $this->request->getParam('_ext') === 'json') {
            $this->viewBuilder()->setClassName('Json');
            $this->viewBuilder()->setOption('serialize', ['invoices']);
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
                $data['estado'] = $data['estado'] ?? 'pendiente';
            }
            
            $invoice = $this->Invoices->patchEntity($invoice, $data);
            
            if ($this->Invoices->save($invoice)) {
                $message = 'Factura guardada correctamente.';
                $this->Flash->success($message);

                // Respuesta para JSON
                if ($this->request->is('json') || $this->request->getParam('_ext') === 'json') {
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
            
            if ($this->request->is('json') || $this->request->getParam('_ext') === 'json') {
                $this->viewBuilder()->setClassName('Json');
                $this->set([
                    'success' => false,
                    'message' => $message,
                    'errors' => $invoice->getErrors()
                ]);
                $this->viewBuilder()->setOption('serialize', ['success', 'message', 'errors']);
                return;
            }
        }
        
        $this->set(compact('invoice'));
    }


        /**
     * View method - Ver una factura específica
     */
    public function view($id = null)
    {
        try {
            $invoice = $this->Invoices->get($id);
        } catch (\Exception $e) {
            $this->Flash->error('Factura no encontrada.');
            return $this->redirect(['action' => 'index']);
        }

        $this->set(compact('invoice'));
    }

    /**
     * Edit method - Editar una factura existente
     */
    public function edit($id = null)
    {
        try {
            $invoice = $this->Invoices->get($id);
        } catch (\Exception $e) {
            $this->Flash->error('Factura no encontrada.');
            return $this->redirect(['action' => 'index']);
        }

        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();
            
            // Calcular IVA y total automáticamente
            if (isset($data['subtotal'])) {
                $subtotal = (float)$data['subtotal'];
                $data['iva'] = $subtotal * 0.21; // 21% IVA
                $data['total'] = $subtotal + $data['iva'];
            }
            
            $invoice = $this->Invoices->patchEntity($invoice, $data);
            
            if ($this->Invoices->save($invoice)) {
                $this->Flash->success('La factura ha sido actualizada.');
                return $this->redirect(['action' => 'view', $id]);
            }
            $this->Flash->error('No se pudo actualizar la factura.');
        }

        $this->set(compact('invoice'));
    }

    /**
     * Delete method - Eliminar una factura
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        
        try {
            $invoice = $this->Invoices->get($id);
        } catch (\Exception $e) {
            $this->Flash->error('Factura no encontrada.');
            return $this->redirect(['action' => 'index']);
        }

        if ($this->Invoices->delete($invoice)) {
            $this->Flash->success('La factura ha sido eliminada.');
        } else {
            $this->Flash->error('No se pudo eliminar la factura.');
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * API method - Endpoint específico para JSON
     */
    public function api()
    {
        // Forzar respuesta JSON
        $this->viewBuilder()->setClassName('Json');
        
        try {
            // Obtener todas las facturas sin paginación para API
            $invoices = $this->Invoices->find('all')
                ->orderBy(['created' => 'DESC'])
                ->toArray();
            
            $this->set([
                'success' => true,
                'message' => 'Facturas obtenidas correctamente',
                'data' => $invoices,
                'count' => count($invoices)
            ]);
        } catch (\Exception $e) {
            $this->set([
                'success' => false,
                'message' => 'Error al obtener facturas: ' . $e->getMessage(),
                'data' => [],
                'count' => 0
            ]);
        }
        
        $this->viewBuilder()->setOption('serialize', ['success', 'message', 'data', 'count']);
    }

    
}