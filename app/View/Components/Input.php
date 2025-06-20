<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Input extends Component
{
    /**
     * The input type (text, email, etc.).
     *
     * @var string
     */
    public $type;

    /**
     * The name (and id) attribute of the input.
     *
     * @var string
     */
    public $name;

    /**
     * The initial value of the input.
     *
     * @var mixed
     */
    public $value;

    /**
     * Create a new component instance.
     *
     * @param  string     $type
     * @param  string     $name
     * @param  mixed|null $value
     * @return void
     */
    public function __construct(string $type = 'text', string $name = '', $value = null)
    {
        $this->type  = $type;
        $this->name  = $name;
        $this->value = old($name, $value);
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return View|Closure|string
     */
    public function render(): View|Closure|string
    {
        return view('components.input');
    }
}
