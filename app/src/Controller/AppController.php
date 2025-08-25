<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Controller\Controller;

class AppController extends Controller
{
    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('Flash');
    }

    public function beforeFilter(\Cake\Event\EventInterface $event)
    {
        parent::beforeFilter($event);
        
        // Configurar respuesta JSON automáticamente si se solicita
        if ($this->request->getParam('_ext') === 'json') {
            $this->viewBuilder()->setClassName('Json');
        }
    }
}