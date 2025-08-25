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
            <?= $this->Html->link(__('Editar Factura'), ['action' => 'edit', $invoice->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Eliminar Factura'), ['action' => 'delete', $invoice->id], ['confirm' => __('¿Estás seguro de eliminar la factura {0}?', $invoice->numero_factura), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('Listar Facturas'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('Nueva Factura'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="invoices view content">
            <h3><?= h($invoice->numero_factura) ?></h3>
            <table class="vertical-table">
                <tr>
                    <th><?= __('Número de Factura') ?></th>
                    <td><?= h($invoice->numero_factura) ?></td>
                </tr>
                <tr>
                    <th><?= __('Cliente') ?></th>
                    <td><?= h($invoice->cliente) ?></td>
                </tr>
                <tr>
                    <th><?= __('Email Cliente') ?></th>
                    <td><?= h($invoice->email_cliente) ?></td>
                </tr>
                <tr>
                    <th><?= __('Estado') ?></th>
                    <td>
                        <span class="badge <?= $invoice->estado === 'pagada' ? 'success' : ($invoice->estado === 'pendiente' ? 'warning' : 'danger') ?>">
                            <?= h($invoice->estado) ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <th><?= __('Subtotal') ?></th>
                    <td><?= $this->Number->currency($invoice->subtotal, 'EUR') ?></td>
                </tr>
                <tr>
                    <th><?= __('IVA') ?></th>
                    <td><?= $this->Number->currency($invoice->iva, 'EUR') ?></td>
                </tr>
                <tr>
                    <th><?= __('Total') ?></th>
                    <td><strong><?= $this->Number->currency($invoice->total, 'EUR') ?></strong></td>
                </tr>
                <tr>
                    <th><?= __('Fecha') ?></th>
                    <td><?= h($invoice->fecha->format('d/m/Y')) ?></td>
                </tr>
                <tr>
                    <th><?= __('Creado') ?></th>
                    <td><?= h($invoice->created->format('d/m/Y H:i')) ?></td>
                </tr>
                <tr>
                    <th><?= __('Modificado') ?></th>
                    <td><?= h($invoice->modified->format('d/m/Y H:i')) ?></td>
                </tr>
            </table>
            <?php if (!empty($invoice->descripcion)): ?>
            <div class="text">
                <strong><?= __('Descripción') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($invoice->descripcion)); ?>
                </blockquote>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.vertical-table th {
    background: #f5f5f5;
    padding: 0.5rem;
    width: 200px;
    text-align: right;
    font-weight: bold;
}
.vertical-table td {
    padding: 0.5rem;
}
.badge {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    border-radius: 0.25rem;
    color: white;
}
.badge.success { background-color: #28a745; }
.badge.warning { background-color: #ffc107; color: #212529; }
.badge.danger { background-color: #dc3545; }
</style>