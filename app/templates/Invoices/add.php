<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Invoice $invoice
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Acciones') ?></h4>
            <?= $this->Html->link(__('Listar Facturas'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="invoices form content">
            <?= $this->Form->create($invoice) ?>
            <fieldset>
                <legend><?= __('Agregar Factura') ?></legend>
                <?php
                    echo $this->Form->control('numero_factura', [
                        'required' => true, 
                        'placeholder' => 'FAC-001',
                        'label' => 'Número de Factura'
                    ]);
                    echo $this->Form->control('fecha', [
                        'type' => 'date', 
                        'required' => true,
                        'default' => date('Y-m-d')
                    ]);
                    echo $this->Form->control('cliente', [
                        'required' => true, 
                        'placeholder' => 'Nombre del cliente'
                    ]);
                    echo $this->Form->control('email_cliente', [
                        'type' => 'email', 
                        'placeholder' => 'cliente@email.com',
                        'label' => 'Email del Cliente'
                    ]);
                    echo $this->Form->control('subtotal', [
                        'type' => 'number', 
                        'step' => '0.01', 
                        'required' => true, 
                        'placeholder' => '100.00',
                        'label' => 'Subtotal (sin IVA)'
                    ]);
                    echo $this->Form->control('estado', [
                        'type' => 'select', 
                        'options' => [
                            'pendiente' => 'Pendiente',
                            'pagada' => 'Pagada',
                            'cancelada' => 'Cancelada'
                        ], 
                        'default' => 'pendiente'
                    ]);
                    echo $this->Form->control('descripcion', [
                        'type' => 'textarea', 
                        'placeholder' => 'Descripción de los servicios...',
                        'label' => 'Descripción'
                    ]);
                ?>
                <small>* El IVA (21%) y total se calcularán automáticamente</small>
            </fieldset>
            <?= $this->Form->button(__('Guardar Factura'), ['class' => 'button']) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>