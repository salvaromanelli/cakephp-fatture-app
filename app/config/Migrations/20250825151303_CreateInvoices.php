<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateInvoices extends BaseMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/migrations/4/en/migrations.html#the-change-method
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('invoices');
        $table->addColumn('numero_factura', 'string', [
            'default' => null,
            'limit' => 255,
            'null' => false,
        ]);
        $table->addColumn('fecha', 'date', [
            'default' => null,
            'null' => false,
        ]);
        $table->addColumn('cliente', 'string', [
            'default' => null,
            'limit' => 255,
            'null' => false,
        ]);
        $table->addColumn('email_cliente', 'string', [
            'default' => null,
            'limit' => 255,
            'null' => false,
        ]);
        $table->addColumn('subtotal', 'decimal', [
            'default' => null,
            'null' => false,
            'precision' => 10,
            'scale' => 6,
        ]);
        $table->addColumn('iva', 'decimal', [
            'default' => null,
            'null' => false,
            'precision' => 10,
            'scale' => 6,
        ]);
        $table->addColumn('total', 'decimal', [
            'default' => null,
            'null' => false,
            'precision' => 10,
            'scale' => 6,
        ]);
        $table->addColumn('estado', 'string', [
            'default' => null,
            'limit' => 255,
            'null' => false,
        ]);
        $table->addColumn('descripcion', 'text', [
            'default' => null,
            'null' => false,
        ]);
        $table->addIndex([
            'numero_factura',
        
            ], [
            'name' => 'BY_NUMERO_FACTURA',
            'unique' => false,
        ]);
        $table->addIndex([
            'email_cliente',
        
            ], [
            'name' => 'BY_EMAIL_CLIENTE',
            'unique' => false,
        ]);
        $table->addIndex([
            'estado',
        
            ], [
            'name' => 'BY_ESTADO',
            'unique' => false,
        ]);
        $table->create();
    }
}
