<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Invoice> $invoices
 */
?>
<div class="invoices index content">
    <?= $this->Html->link(__("Nueva Factura"), ["action" => "add"], ["class" => "button float-right"]) ?>
    <h3><?= __("Facturas") ?></h3>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort("numero_factura", "Número") ?></th>
                    <th><?= $this->Paginator->sort("fecha", "Fecha") ?></th>
                    <th><?= $this->Paginator->sort("cliente", "Cliente") ?></th>
                    <th><?= $this->Paginator->sort("subtotal", "Subtotal") ?></th>
                    <th><?= $this->Paginator->sort("iva", "IVA") ?></th>
                    <th><?= $this->Paginator->sort("total", "Total") ?></th>
                    <th><?= $this->Paginator->sort("estado", "Estado") ?></th>
                    <th class="actions"><?= __("Acciones") ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($invoices)): ?>
                    <?php foreach ($invoices as $invoice): ?>
                    <tr>
                        <td><?= h($invoice->numero_factura) ?></td>
                        <td><?= h($invoice->fecha->format("d/m/Y")) ?></td>
                        <td><?= h($invoice->cliente) ?></td>
                        <td>€<?= $this->Number->format($invoice->subtotal, ["precision" => 2]) ?></td>
                        <td>€<?= $this->Number->format($invoice->iva, ["precision" => 2]) ?></td>
                        <td><strong>€<?= $this->Number->format($invoice->total, ["precision" => 2]) ?></strong></td>
                        <td>
                            <span class="badge <?= $invoice->estado === "pagada" ? "success" : ($invoice->estado === "pendiente" ? "warning" : "danger") ?>">
                                <?= h($invoice->estado) ?>
                            </span>
                        </td>
                        <td class="actions">
                            <?= $this->Html->link(__("Ver"), ["action" => "view", $invoice->id], ["class" => "button button-outline"]) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center">
                            <p>No hay facturas disponibles.</p>
                            <p><?= $this->Html->link("Crear Primera Factura", ["action" => "add"], ["class" => "button"]) ?></p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
