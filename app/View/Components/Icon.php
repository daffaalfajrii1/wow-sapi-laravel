<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Icon extends Component
{
    public function __construct(public string $name = 'home', public string $class = 'w-5 h-5')
    {
    }

    public function render(): View|Closure|string
    {
        return view('components.icon');
    }
}
