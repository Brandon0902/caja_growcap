<?php
namespace App\View\Components;

use Illuminate\View\Component;

class Label extends Component
{
    public string $for;
    public string $value;

    public function __construct(string $for, string $value)
    {
        $this->for   = $for;
        $this->value = $value;
    }

    public function render()
    {
        return view('components.label');
    }
}
