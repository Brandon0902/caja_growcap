<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Checkbox extends Component
{
    /**
     * The name (and id) attribute of the checkbox.
     *
     * @var string
     */
    public $name;

    /**
     * Whether the checkbox is checked.
     *
     * @var bool
     */
    public $checked;

    /**
     * Create a new component instance.
     *
     * @param  string  $name
     * @param  bool    $checked
     * @return void
     */
    public function __construct(string $name = '', bool $checked = false)
    {
        // Si no te pasan $name, queda vacío en lugar de romper.
        $this->name    = $name;
        $this->checked = old($name, $checked);
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return View|Closure|string
     */
    public function render(): View|Closure|string
    {
        return view('components.checkbox');
    }
}
