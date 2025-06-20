<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Button extends Component
{
    /**
     * El tipo de botón (submit, button, reset, etc.).
     *
     * @var string
     */
    public $type;

    /**
     * Crea una nueva instancia del componente.
     *
     * @param  string  $type
     * @return void
     */
    public function __construct(string $type = 'submit')
    {
        $this->type = $type;
    }

    /**
     * Devuelve la vista del componente.
     *
     * @return View|Closure|string
     */
    public function render(): View|Closure|string
    {
        return view('components.button');
    }
}
