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
            <?= $this->Form->postLink(
                __('Eliminar'),
                ['action' => 'delete', $invoice->id],
                ['confirm' => __('¿Estás seguro de eliminar la factura {0}?', $invoice->numero_factura), 'class' => 'side-nav-item']
            ) ?>
            <?= $this->Html->link(__('Listar Facturas'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="invoices form content">
            <?= $this->Form->create($invoice) ?>
            <fieldset>
                <legend><?= __('Editar Factura') ?></legend>
                <?php
                    echo $this->Form->control('numero_factura', [
                        'required' => true, 
                        'label' => 'Número de Factura'
                    ]);
                    echo $this->Form->control('fecha', [
                        'type' => 'date', 
                        'required' => true
                    ]);
                    echo $this->Form->control('cliente', [
                        'required' => true
                    ]);
                    echo $this->Form->control('email_cliente', [
                        'type' => 'email',
                        'label' => 'Email del Cliente'
                    ]);
                    echo $this->Form->control('subtotal', [
                        'type' => 'number', 
                        'step' => '0.01', 
                        'required' => true,
                        'label' => 'Subtotal (sin IVA)'
                    ]);
                    echo $this->Form->control('estado', [
                        'type' => 'select', 
                        'options' => [
                            'pendiente' => 'Pendiente',
                            'pagada' => 'Pagada',
                            'cancelada' => 'Cancelada'
                        ]
                    ]);
                    echo $this->Form->control('descripcion', [
                        'type' => 'textarea',
                        'label' => 'Descripción'
                    ]);
                ?>
                <small>* El IVA (21%) y total se recalcularán automáticamente</small>
            </fieldset>
            <?= $this->Form->button(__('Actualizar Factura'), ['class' => 'button']) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>