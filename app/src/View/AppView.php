<?php
declare(strict_types=1);

namespace App\View;

use Cake\View\View;

/**
 * Application View
 *
 * Your application's default view class
 *
 * @link https://book.cakephp.org/5/en/views.html#the-app-view
 */
class AppView extends View
{
    /**
     * Initialization hook method.
     *
     * @return void
     */
    public function initialize(): void
    {
        // En CakePHP 5.x no necesitamos cargar el CSRF helper manualmente
        // Se maneja automáticamente con el FormHelper cuando es necesario
        
        // Cargar helpers básicos
        $this->loadHelper('Html');
        $this->loadHelper('Form');
        $this->loadHelper('Flash');
        $this->loadHelper('Number');
        $this->loadHelper('Paginator');
    }
}