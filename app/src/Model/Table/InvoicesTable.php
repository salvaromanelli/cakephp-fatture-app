<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Invoices Model
 *
 * @method \App\Model\Entity\Invoice newEmptyEntity()
 * @method \App\Model\Entity\Invoice newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Invoice> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Invoice get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Invoice findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Invoice patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Invoice> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Invoice|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Invoice saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Invoice> saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Invoice> saveManyOrFail(iterable $entities, array $options = [])
 * @method \App\Model\Entity\Invoice[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Invoice> paginate(?object $object = null, array $settings = [])
 */
class InvoicesTable extends Table
{
    /**
     * Initialize method
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('invoices');
        $this->setDisplayField('numero_factura');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
    }

    /**
     * Default validation rules.
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('numero_factura')
            ->maxLength('numero_factura', 50)
            ->requirePresence('numero_factura', 'create')
            ->notEmptyString('numero_factura')
            ->add('numero_factura', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->date('fecha')
            ->requirePresence('fecha', 'create')
            ->notEmptyDate('fecha');

        $validator
            ->scalar('cliente')
            ->maxLength('cliente', 255)
            ->requirePresence('cliente', 'create')
            ->notEmptyString('cliente');

        $validator
            ->email('email_cliente')
            ->allowEmptyString('email_cliente');

        $validator
            ->decimal('subtotal')
            ->greaterThan('subtotal', 0)
            ->requirePresence('subtotal', 'create')
            ->notEmptyString('subtotal');

        $validator
            ->decimal('iva')
            ->greaterThanOrEqual('iva', 0)
            ->requirePresence('iva', 'create')
            ->notEmptyString('iva');

        $validator
            ->decimal('total')
            ->greaterThan('total', 0)
            ->requirePresence('total', 'create')
            ->notEmptyString('total');

        $validator
            ->scalar('estado')
            ->maxLength('estado', 20)
            ->requirePresence('estado', 'create')
            ->notEmptyString('estado')
            ->add('estado', 'inList', [
                'rule' => ['inList', ['pendiente', 'pagada', 'cancelada']],
                'message' => 'Estado debe ser: pendiente, pagada o cancelada'
            ]);

        $validator
            ->scalar('descripcion')
            ->allowEmptyString('descripcion');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['numero_factura']), ['errorField' => 'numero_factura']);

        return $rules;
    }

    /**
     * Método personalizado para obtener facturas por estado
     */
    public function findByEstado(SelectQuery $query, array $options): SelectQuery
    {
        return $query->where(['estado' => $options['estado']]);
    }

    /**
     * Método personalizado para obtener facturas del mes actual
     */
    public function findDelMesActual(SelectQuery $query, array $options): SelectQuery
    {
        return $query->where([
            'fecha >=' => date('Y-m-01'),
            'fecha <=' => date('Y-m-t')
        ]);
    }
}