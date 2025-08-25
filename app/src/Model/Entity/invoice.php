<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Invoice Entity
 *
 * @property int $id
 * @property string $numero_factura
 * @property \Cake\I18n\DateTime $fecha
 * @property string $cliente
 * @property string|null $email_cliente
 * @property string $subtotal
 * @property string $iva
 * @property string $total
 * @property string $estado
 * @property string|null $descripcion
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 */
class Invoice extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'numero_factura' => true,
        'fecha' => true,
        'cliente' => true,
        'email_cliente' => true,
        'subtotal' => true,
        'iva' => true,
        'total' => true,
        'estado' => true,
        'descripcion' => true,
        'created' => true,
        'modified' => true,
    ];

    /**
     * Fields that are excluded from JSON versions of the entity.
     *
     * @var array<string>
     */
    protected array $_hidden = []; // Cambiar de $_hidden = [] a array $_hidden = []
}